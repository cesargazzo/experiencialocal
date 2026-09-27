<?php

namespace Tests\Feature;

use App\Enums\ExperienceStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Experience;
use App\Models\HostProfile;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function changes_record_who_what_before_and_after(): void
    {
        $admin = User::factory()->admin()->create();
        $experience = Experience::factory()->create(['price' => 40000]);

        $this->actingAs($admin);
        $experience->update(['price' => 45000, 'status' => ExperienceStatus::Paused]);

        $log = AuditLog::where('auditable_type', Experience::class)->where('auditable_id', (string) $experience->id)->where('event', 'updated')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('paused', $log->new_values['status']);
        $this->assertSame('published', $log->old_values['status']);
        $this->assertEquals(40000, $log->old_values['price']);
        $this->assertEquals(45000, $log->new_values['price']);
        $this->assertArrayNotHasKey('updated_at', $log->new_values);
    }

    #[Test]
    public function passwords_and_tokens_are_never_stored_and_protected_data_is_masked(): void
    {
        $user = User::factory()->create(['password' => 'Secreta2026x']);
        $user->update(['password' => 'OtraSecreta2026']);
        $profile = HostProfile::factory()->create(['address' => 'San Martín 123', 'payout_account' => '0000003100010000000001']);

        $all = AuditLog::all()->map(fn ($l) => json_encode([$l->old_values, $l->new_values]))->implode(' ');
        $this->assertStringNotContainsString('Secreta2026x', $all);
        $this->assertStringNotContainsString('OtraSecreta2026', $all);
        $this->assertStringNotContainsString('San Martín 123', $all);
        $this->assertStringNotContainsString('0000003100010000000001', $all);
        $this->assertSame('*** (dato protegido)', AuditLog::where('auditable_type', HostProfile::class)->where('auditable_id', (string) $profile->id)->firstOrFail()->new_values['address']);
        $this->assertFalse(AuditLog::where('auditable_type', User::class)->where('event', 'updated')->exists(), 'Un cambio solo de contraseña no deja valores.');
    }

    #[Test]
    public function deletions_and_interest_changes_are_recorded(): void
    {
        $user = User::factory()->create();
        $experience = Experience::factory()->create();
        $id = $experience->id;
        $experience->delete();
        $this->assertTrue(AuditLog::where('auditable_id', (string) $id)->where('event', 'deleted')->exists());

        $this->actingAs($user)->put(route('cuenta.intereses.update'), ['categories' => [Category::value('id')], 'interest_alerts' => '1']);
        $this->assertTrue(AuditLog::where('event', 'interests.updated')->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function admins_browse_and_filter_the_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['name' => 'Rosa Díaz']);
        $this->actingAs($admin);
        $target->update(['name' => 'Rosa María Díaz']);

        $this->get(route('admin.auditoria', ['tipo' => User::class, 'id' => $target->id]))
            ->assertOk()->assertSee('Rosa María Díaz')->assertSee($admin->email);
        $this->get(route('admin.usuarios.show', $target))->assertOk()->assertSee('Rosa María Díaz');
        $this->actingAs(User::factory()->create())->get(route('admin.auditoria'))->assertForbidden();
    }

    #[Test]
    public function old_audit_entries_are_pruned(): void
    {
        AuditLog::create(['auditable_type' => User::class, 'auditable_id' => '1', 'event' => 'updated', 'source' => 'web', 'created_at' => now()->subDays(config('tinku.audit_log_days') + 1)]);
        $recent = AuditLog::count();

        Artisan::call('model:prune', ['--model' => [AuditLog::class]]);

        $this->assertSame($recent - 1, AuditLog::count());
    }
}
