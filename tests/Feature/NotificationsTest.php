<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_host_gets_an_in_app_notice_when_their_experience_is_approved(): void
    {
        config(['queue.default' => 'database']);
        $experience = Experience::factory()->inReview()->create(['title' => 'Cocina criolla moderna']);
        $host = $experience->host->user;

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.experiencias.aprobar', $experience));

        // El aviso en Tinku no espera a la cola: está aunque el worker no corra.
        $this->assertSame(1, $host->unreadNotifications()->count());

        $this->actingAs($host)->get(route('home'))->assertSee('Avisos: 1 sin leer');
        $this->actingAs($host)->get(route('cuenta.avisos'))
            ->assertOk()
            ->assertSee('Ya está publicada: Cocina criolla moderna')
            ->assertSee('is-unread', false);

        $this->assertSame(0, $host->unreadNotifications()->count());
        $this->actingAs($host)->get(route('cuenta.avisos'))->assertDontSee('is-unread', false);
    }

    #[Test]
    public function without_notices_the_page_explains_what_will_show_up(): void
    {
        $this->actingAs(User::factory()->create())->get(route('cuenta.avisos'))
            ->assertOk()
            ->assertSee('No tenés avisos todavía');
    }
}
