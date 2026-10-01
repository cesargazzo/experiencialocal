<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\AdvertiserInquiry;
use App\Models\Experience;
use App\Models\User;
use App\Notifications\AdvertiserInquiryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaKitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_media_kit_shows_live_totals_and_hides_the_audience_while_it_is_small(): void
    {
        Experience::factory()->count(2)->create();
        User::factory()->count(3)->create(['nationality_code' => 'AR']);

        $this->get(route('mediakit'))->assertOk()
            ->assertSee('Tinku en números')
            ->assertViewHas('numbers', fn (array $numbers) => $numbers['experiences'] === 2)
            ->assertViewHas('origins', null)
            ->assertSee('Lo vamos a mostrar cuando la comunidad sea más grande');
    }

    #[Test]
    public function the_audience_only_shows_large_groups_and_folds_small_ones_into_other(): void
    {
        User::factory()->count(20)->create(['nationality_code' => 'AR']);
        User::factory()->count(3)->create(['nationality_code' => 'UY']);

        $origins = $this->get(route('mediakit'))->assertOk()->viewData('origins');
        $this->assertSame(['Argentina', 'Otros'], $origins->pluck('label')->all());
        $this->assertSame(87, $origins->first()['share']);
    }

    #[Test]
    public function an_advertiser_writes_and_the_platform_team_is_notified(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->team(TeamRole::Moderator)->create();

        $this->post(route('mediakit.store'), [
            'name' => 'Laura Díaz', 'company' => 'Bodega del Valle', 'email' => 'laura@bodega.test', 'phone' => '+54 380 400 1111',
            'formats' => ['presentada', 'contenido'], 'budget' => 'medio',
            'message' => 'Queremos presentar una colección de catas en La Rioja para la vendimia.',
        ])->assertRedirect(route('mediakit').'#contacto');

        $inquiry = AdvertiserInquiry::sole();
        $this->assertSame(['presentada', 'contenido'], $inquiry->formats);
        $this->assertStringNotContainsString('4001111', DB::table('advertiser_inquiries')->value('phone'));
        Notification::assertSentTo($admin, AdvertiserInquiryNotification::class);
        Notification::assertNotSentTo($moderator, AdvertiserInquiryNotification::class);

        $this->actingAs($admin)->get(route('admin.anunciantes'))->assertOk()->assertSee('Bodega del Valle');
        $this->actingAs($admin)->put(route('admin.anunciantes.update', $inquiry), ['status' => 'contacted']);
        $this->assertSame('contacted', $inquiry->fresh()->status);
        $this->actingAs($moderator)->get(route('admin.anunciantes'))->assertForbidden();
    }

    #[Test]
    public function robots_filling_the_hidden_field_are_ignored_and_the_page_is_translated(): void
    {
        $this->post(route('mediakit.store'), ['website' => 'http://spam.test', 'name' => 'x', 'company' => 'x', 'email' => 'x@x.test', 'message' => str_repeat('spam ', 10)])
            ->assertRedirect();
        $this->assertSame(0, AdvertiserInquiry::count());

        $this->withSession(['locale' => 'en'])->get(route('mediakit'))->assertSee('Ways to advertise')->assertSee('Tinku in numbers');
    }
}
