<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ExperienceStatus;
use App\Livewire\ManageExperience;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Services\BookingService;
use App\Services\Georef;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MapsAndAddressesTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://georef.test/api';

    protected function setUp(): void
    {
        parent::setUp();
        config(['tinku.georef.url' => self::API]);
        Queue::fake();
    }

    /** Respuesta de Georef para una calle encontrada. */
    private function fakeGeoref(): void
    {
        Http::fake([
            self::API.'/direcciones*' => Http::response(['cantidad' => 1, 'direcciones' => [[
                'nomenclatura' => 'SAN MARTIN 123, Chilecito, La Rioja',
                'ubicacion' => ['lat' => -29.1631, 'lon' => -67.4981],
            ]]]),
            self::API.'/localidades*' => Http::response(['localidades' => [['centroide' => ['lat' => -29.16, 'lon' => -67.49]]]]),
        ]);
    }

    #[Test]
    public function georef_normalizes_a_street(): void
    {
        $this->fakeGeoref();
        $found = app(Georef::class)->normalize('san martin 123', 'Chilecito', 'La Rioja');
        $this->assertSame(['address' => 'SAN MARTIN 123, Chilecito, La Rioja', 'lat' => -29.1631, 'lng' => -67.4981, 'normalized' => true], $found);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'direcciones') && $request['localidad'] === 'Chilecito' && $request['provincia'] === 'La Rioja');
    }

    #[Test]
    public function an_unknown_street_falls_back_to_the_center_of_the_locality(): void
    {
        Http::fake([
            self::API.'/direcciones*' => Http::response(['cantidad' => 0, 'direcciones' => []]),
            self::API.'/localidades*' => Http::response(['localidades' => [['centroide' => ['lat' => -29.16, 'lon' => -67.49]]]]),
        ]);
        $this->assertSame(['address' => 'Calle Inventada 1', 'lat' => -29.16, 'lng' => -67.49, 'normalized' => false], app(Georef::class)->normalize('Calle Inventada 1', 'Chilecito', 'La Rioja'));
    }

    #[Test]
    public function if_georef_is_down_nothing_breaks(): void
    {
        Http::fake([self::API.'/*' => Http::response('error', 500)]);

        $this->assertNull(app(Georef::class)->normalize('San Martín 123', 'Chilecito', 'La Rioja'));
    }

    #[Test]
    public function the_host_finds_the_address_marks_the_point_and_saves_it(): void
    {
        $this->fakeGeoref();
        $experience = Experience::factory()->create(['city' => 'Chilecito']);

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience])
            ->set('meeting_address', 'san martin 123')
            ->call('searchAddress')
            ->assertSet('meeting_address', 'SAN MARTIN 123, Chilecito, La Rioja')
            ->assertSet('latitude', -29.1631)
            ->assertDispatched('map-move')
            ->call('setPoint', -29.1635, -67.4990)
            ->call('saveLocation')
            ->assertHasNoErrors();

        $experience->refresh();
        $this->assertSame('SAN MARTIN 123, Chilecito, La Rioja', $experience->meeting_address);
        $this->assertSame(-29.1635, $experience->latitude);
        $this->assertNotNull($experience->address_normalized_at);
        $this->assertSame(ExperienceStatus::Published, $experience->status, 'El punto de encuentro no pasa por revisión.');
    }

    #[Test]
    public function the_public_page_shows_only_an_approximate_zone_and_confirmed_guests_get_the_address(): void
    {
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 8]);
        $experience = $date->experience;
        $experience->update(['meeting_address' => 'SAN MARTIN 123, Chilecito, La Rioja', 'latitude' => -29.1631234, 'longitude' => -67.4981234]);

        $this->get(route('experiencias.show', $experience))
            ->assertOk()
            ->assertSee('zona aproximada')
            ->assertDontSee('-29.1631234')
            ->assertDontSee('SAN MARTIN 123');
        $zone = $experience->approximateLocation();
        $this->assertLessThan(0.006, abs($zone['lat'] - $experience->latitude));
        $this->assertEquals($zone, $experience->fresh()->approximateLocation(), 'La zona es siempre la misma.');

        $guest = User::factory()->create();
        $booking = app(BookingService::class)->request($guest, $date, 2);
        $this->actingAs($guest)->get(route('cuenta.reservas'))->assertDontSee('SAN MARTIN 123');

        $booking->forceFill(['status' => BookingStatus::Confirmed])->save();
        $this->actingAs($guest)->get(route('cuenta.reservas'))->assertSee('SAN MARTIN 123, Chilecito, La Rioja')->assertSee('Cómo llegar');
    }

    #[Test]
    public function admins_normalize_one_address_or_the_pending_ones(): void
    {
        $this->fakeGeoref();
        $admin = User::factory()->admin()->create();
        $first = Experience::factory()->create(['meeting_address' => 'san martin 123', 'city' => 'Chilecito']);
        $second = Experience::factory()->create(['meeting_address' => 'san martin 123', 'city' => 'Chilecito']);

        $this->actingAs($admin)->post(route('admin.experiencias.normalizar', $first))->assertSessionHasNoErrors();
        $this->assertSame('SAN MARTIN 123, Chilecito, La Rioja', $first->fresh()->meeting_address);

        $this->actingAs($admin)->get(route('admin.experiencias'))->assertSee('1 dirección pendiente');
        $this->actingAs($admin)->post(route('admin.experiencias.direcciones'))
            ->assertSessionHas('status', 'Revisamos 1 direcciones: 1 normalizadas, 0 ubicadas solo por localidad, 0 sin encontrar.');
        $this->assertNotNull($second->fresh()->address_normalized_at);
        $this->assertSame(-29.1631, $second->fresh()->latitude);
    }
}
