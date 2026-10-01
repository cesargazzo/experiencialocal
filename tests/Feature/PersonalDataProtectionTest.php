<?php

namespace Tests\Feature;

use App\Enums\VerificationType;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersonalDataProtectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_phone_is_stored_encrypted_and_never_in_plain_text_in_the_audit_or_the_codes(): void
    {
        $user = User::factory()->create(['phone' => '+54 9 380 412-3456']);

        $stored = DB::table('users')->where('id', $user->id)->first();
        $this->assertStringNotContainsString('4123456', $stored->phone);
        $this->assertNotNull($stored->phone_hash);
        $this->assertSame('+54 9 380 412-3456', $user->fresh()->phone);

        $user->update(['phone' => '+54 380 499-0000']);
        $audit = AuditLog::query()->where('auditable_id', (string) $user->id)->where('event', 'updated')->latest('id')->first();
        $this->assertSame('*** (dato protegido)', $audit->new_values['phone']);
        $this->assertStringNotContainsString('4990000', json_encode([$audit->old_values, $audit->new_values]));

        $verification = app(VerificationService::class)->submit($user, VerificationType::Phone);
        $this->assertSame('•••• 0000', $verification->result['sent_to']);
    }

    #[Test]
    public function admins_find_an_account_by_phone_in_any_format_and_see_shared_phones(): void
    {
        $admin = User::factory()->admin()->create();
        $marta = User::factory()->create(['name' => 'Marta Quiroga', 'phone' => '+54 9 380 412-3456']);
        $other = User::factory()->create(['name' => 'Otra Cuenta', 'phone' => '0380 4123456']);
        User::factory()->create(['name' => 'Nadie Más', 'phone' => '+54 11 5555-0000']);

        $this->actingAs($admin)->get(route('admin.usuarios', ['q' => '380 412 3456']))
            ->assertOk()->assertSee('Marta Quiroga')->assertSee('Otra Cuenta')->assertDontSee('Nadie Más');

        $this->actingAs($admin)->get(route('admin.usuarios.show', $marta))
            ->assertSee('El mismo teléfono está en otra cuenta')->assertSee(route('admin.usuarios.show', $other), false);
    }

    #[Test]
    public function the_document_fingerprint_needs_the_secret_key(): void
    {
        $plain = hash('sha256', 'AR|30111222');

        $this->assertNotSame($plain, VerificationService::documentHash('AR', '30.111.222'));
        $this->assertSame(VerificationService::documentHash('AR', '30111222'), VerificationService::documentHash('ar', '30.111.222'));
    }
}
