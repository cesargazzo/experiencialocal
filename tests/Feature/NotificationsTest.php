<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\User;
use App\Notifications\ExperienceReviewedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    #[Test]
    public function notice_emails_are_off_by_default_and_the_admin_can_turn_them_on(): void
    {
        Mail::fake();
        $experience = Experience::factory()->inReview()->create();
        $host = $experience->host->user;
        $admin = User::factory()->admin()->create();

        $notification = new ExperienceReviewedNotification($experience, ExperienceReviewedNotification::APPROVED);
        $this->assertSame(['database'], $notification->via($host), 'Por defecto no se mandan mails de avisos.');

        $this->actingAs($admin)->get(route('admin.configuracion'))->assertSee('Mandar por mail los avisos');
        $this->actingAs($admin)->put(route('admin.configuracion.update'), ['notification_emails' => '1'])->assertSessionHasNoErrors();

        $this->assertSame(['mail', 'database'], $notification->via($host));
    }
}
