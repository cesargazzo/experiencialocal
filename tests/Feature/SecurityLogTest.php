<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\PasswordPolicy;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class SecurityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function logins_failures_and_lockouts_are_recorded_with_email_and_ip(): void
    {
        $user = User::factory()->create(['email' => 'lucia@example.com', 'password' => 'Correcta2026']);
        $attempts = PasswordPolicy::current()->maxLoginAttempts;

        foreach (range(1, $attempts) as $i) {
            $this->post(route('login'), ['email' => 'lucia@example.com', 'password' => 'mala']);
        }
        $this->post(route('login'), ['email' => 'lucia@example.com', 'password' => 'Correcta2026']);

        $this->assertSame($attempts, SecurityEvent::where('type', 'login.failed')->where('email', 'lucia@example.com')->count());
        $locked = SecurityEvent::where('type', 'login.locked')->firstOrFail();
        $this->assertSame('danger', $locked->severity);
        $this->assertSame('127.0.0.1', $locked->ip);

        $this->travel(PasswordPolicy::current()->lockoutMinutes + 1)->minutes();
        $this->post(route('login'), ['email' => 'lucia@example.com', 'password' => 'Correcta2026']);
        $this->assertTrue(SecurityEvent::where('type', 'login.succeeded')->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function forbidden_access_probes_and_server_errors_are_recorded(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.verificaciones'))->assertForbidden();
        $this->assertTrue(SecurityEvent::where('type', 'access.forbidden')->where('path', 'admin/verificaciones')->exists());

        auth()->logout();
        $this->get('/wp-admin/setup-config.php')->assertNotFound();
        $this->get('/.env')->assertNotFound();
        $this->get('/experiencias-que-no-existen')->assertNotFound();
        $this->assertSame(2, SecurityEvent::where('type', 'probe.suspicious')->count());

        Route::get('/_explota', fn () => throw new RuntimeException('Falla de prueba'));
        $this->get('/_explota')->assertServerError();
        $error = SecurityEvent::where('type', 'error.server')->firstOrFail();
        $this->assertSame('Falla de prueba', $error->metadata['message']);
    }

    #[Test]
    public function password_reset_tokens_are_never_stored(): void
    {
        $this->post('/restablecer-contrasena', ['token' => 'x', 'email' => 'a@example.com', 'password' => 'Segura2026x', 'password_confirmation' => 'Segura2026x']);
        $this->get('/restablecer-contrasena/secreto-muy-largo-123');

        $this->assertFalse(SecurityEvent::where('path', 'like', '%secreto%')->exists());
    }

    #[Test]
    public function admins_see_the_log_with_filters_and_others_cannot(): void
    {
        SecurityEvent::create(['type' => 'login.failed', 'severity' => 'warning', 'email' => 'ataque@example.com', 'ip' => '203.0.113.9']);
        SecurityEvent::create(['type' => 'login.failed', 'severity' => 'warning', 'email' => 'otro@example.com', 'ip' => '198.51.100.7']);

        $this->actingAs(User::factory()->create())->get(route('admin.seguridad'))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.seguridad', ['ip' => '203.0.113.9']))
            ->assertOk()
            ->assertSee('ataque@example.com')
            ->assertDontSee('otro@example.com')
            ->assertSee('Direcciones IP con más señales de riesgo');
    }

    #[Test]
    public function old_events_are_pruned(): void
    {
        SecurityEvent::create(['type' => 'logout', 'created_at' => now()->subDays(config('tinku.security_log_days') + 1)]);
        SecurityEvent::create(['type' => 'logout']);

        Artisan::call('model:prune', ['--model' => [SecurityEvent::class]]);

        $this->assertSame(1, SecurityEvent::count());
    }

    #[Test]
    public function an_ip_that_keeps_probing_is_blocked_for_an_hour(): void
    {
        foreach (['/wp-login.php', '/.env', '/phpmyadmin', '/.git/config'] as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->get(route('home'))->assertOk();

        $this->get('/xmlrpc.php')->assertNotFound();
        $this->get(route('home'))->assertForbidden();
        $this->assertTrue(SecurityEvent::where('type', 'probe.banned')->exists());

        $this->travel(61)->minutes();
        $this->get(route('home'))->assertOk();
    }
}
