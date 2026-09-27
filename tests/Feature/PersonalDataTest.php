<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\ExperienceDate;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersonalDataTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_person_downloads_everything_tinku_keeps_about_them(): void
    {
        $user = User::factory()->create(['first_name' => 'Ana', 'last_name' => 'Paz', 'food_allergies' => 'Maní']);
        $conversation = Conversation::factory()->create(['guest_id' => $user->id]);
        $conversation->messages()->create(['sender_id' => $user->id, 'body' => '¿Hay estacionamiento?']);

        $response = $this->actingAs($user)->get(route('cuenta.datos.descargar'))->assertOk()->assertDownload();
        $data = json_decode($response->streamedContent(), true);

        $this->assertSame('Ana', $data['cuenta']['nombre']);
        $this->assertSame('Maní', $data['cuenta']['alergias']);
        $this->assertSame('¿Hay estacionamiento?', $data['mensajes'][0]['mensajes'][0]['texto']);
        $this->assertTrue(SecurityEvent::where('type', 'account.exported')->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function deleting_the_account_erases_personal_data_and_keeps_the_rest_anonymous(): void
    {
        $user = User::factory()->create(['first_name' => 'Ana', 'last_name' => 'Paz', 'email' => 'ana@example.com', 'password' => 'Segura2026x', 'phone' => '+54 11 5555 4444']);
        $user->update(['city' => 'Rosario']);
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 8]);
        $booking = app(BookingService::class)->request($user, $date, 2);

        $this->actingAs($user)->delete(route('cuenta.eliminar'), ['password' => 'Segura2026x', 'confirm' => '1'])
            ->assertSessionHasErrorsIn('deletion', 'password');
        $booking->forceFill(['status' => BookingStatus::Cancelled])->save();

        $this->actingAs($user)->delete(route('cuenta.eliminar'), ['password' => 'Otra2026x', 'confirm' => '1'])->assertSessionHasErrorsIn('deletion', 'password');
        $this->actingAs($user)->delete(route('cuenta.eliminar'), ['password' => 'Segura2026x', 'confirm' => '1'])->assertRedirect(route('home'));

        $this->assertGuest();
        $user->refresh();
        $this->assertSame('Cuenta eliminada', $user->name);
        $this->assertNull($user->phone);
        $this->assertNull($user->city);
        $this->assertStringEndsWith('@tinku.invalid', $user->email);
        $this->assertNotNull($user->anonymized_at);
        $this->assertNotNull($booking->fresh(), 'La reserva queda, sin datos de la persona.');
        $this->assertFalse(AuditLog::where('auditable_type', User::class)->where('auditable_id', (string) $user->id)->whereNotNull('new_values')->exists(), 'La auditoría no conserva los datos borrados.');

        $this->post(route('login'), ['email' => 'ana@example.com', 'password' => 'Segura2026x'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function an_admin_account_cannot_be_deleted_from_the_account_page(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'Segura2026x', 'two_factor_secret' => null, 'two_factor_confirmed_at' => null]);

        $this->actingAs($admin)->delete(route('cuenta.eliminar'), ['password' => 'Segura2026x', 'confirm' => '1'])->assertSessionHasErrorsIn('deletion', 'password');
        $this->assertNull($admin->fresh()->anonymized_at);
    }
}
