<?php

namespace App\Providers;

use Anthropic\Client;
use App\Enums\TeamPermission;
use App\Models\User;
use App\Services\Moderation\ClaudeModerator;
use App\Services\Moderation\ContentModerator;
use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ContentModerator::class, fn (): ContentModerator => new ClaudeModerator(
            new Client(apiKey: (string) config('services.anthropic.key')),
            (string) config('tinku.moderation.model'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn (): Password => PasswordPolicy::current()->rule());

        // Un permiso del equipo por sección de la administración: las rutas piden "can:team.…".
        foreach (TeamPermission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user): bool => $user->hasTeamPermission($permission));
        }
    }
}
