<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\LogReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminLogTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/logs-'.uniqid());
        File::ensureDirectoryExists($this->directory);
        File::put($this->directory.'/laravel-2026-09-27.log', implode("\n", [
            '[2026-09-27 10:00:00] production.INFO: Arrancó la cola',
            '[2026-09-27 10:05:00] production.ERROR: SQLSTATE[23505]: Unique violation {"exception":"[object] (PDOException)"}',
            '#0 /var/www/tinku/vendor/laravel/framework/src/Illuminate/Database/Connection.php(825): execute()',
            '#1 {main}',
            '[2026-09-27 10:06:00] production.WARNING: Foto sin procesar',
            '',
        ]));
        $this->app->instance(LogReader::class, new LogReader($this->directory));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    #[Test]
    public function admins_read_the_latest_entries_with_their_stack_trace_and_filter_them(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.registro'))
            ->assertOk()
            ->assertSeeInOrder(['Foto sin procesar', 'SQLSTATE[23505]: Unique violation', 'Connection.php(825)', 'Arrancó la cola']);

        $this->actingAs($admin)->get(route('admin.registro', ['nivel' => 'error']))
            ->assertSee('Unique violation')
            ->assertDontSee('Foto sin procesar');

        $this->actingAs($admin)->get(route('admin.registro', ['q' => 'cola']))
            ->assertSee('Arrancó la cola')
            ->assertDontSee('Unique violation');
    }

    #[Test]
    public function only_admins_see_or_download_logs_and_downloads_are_recorded(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.registro'))->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.registro.descargar', 'laravel-2026-09-27.log'))->assertOk()->assertDownload('laravel-2026-09-27.log');
        $this->assertSame(1, SecurityEvent::where('type', 'log.downloaded')->count());

        $this->actingAs($admin)->get('/admin/registro/..%2F.env/descargar')->assertNotFound();
        $this->actingAs($admin)->get(route('admin.registro.descargar', 'no-existe.log'))->assertNotFound();
    }
}
