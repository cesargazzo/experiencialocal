<?php

namespace App\Console\Commands;

use App\Enums\TeamRole;
use App\Models\User;
use App\Services\SecurityLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('tinku:admin {email : Email de la persona} {--name= : Nombre, si la cuenta todavía no existe} {--revoke : Quita a la persona del equipo}')]
#[Description('Da administración total o saca a la persona del equipo. Al darlo genera una contraseña de única vez que hay que cambiar en el primer ingreso.')]
class MakeAdmin extends Command
{
    public function handle(SecurityLog $securityLog): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->components->error('El email no es válido.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($this->option('revoke')) {
            if (! $user?->isTeamMember()) {
                $this->components->warn('Esa cuenta no es administradora.');

                return self::SUCCESS;
            }
            $user->forceFill(['team_role' => null])->save();
            $securityLog->record('admin.revoked', $user, ['via' => 'consola'], $email, 'warning');
            $this->components->info("{$email} ya no es administradora.");

            return self::SUCCESS;
        }

        if (! $user) {
            $name = $this->option('name') ?: $this->ask('Nombre y apellido');
            if (blank($name)) {
                $this->components->error('Hace falta un nombre para crear la cuenta.');

                return self::FAILURE;
            }
            $user = User::create(['name' => $name, 'email' => $email, 'password' => str()->random(64), 'country_code' => 'AR']);
        }

        $user->forceFill(['team_role' => TeamRole::Admin])->save();
        $temporary = $user->issueTemporaryPassword();
        $securityLog->record('admin.granted', $user, ['via' => 'consola'], $email, 'warning');
        $securityLog->record('password.temporary_issued', $user, ['expires_at' => $user->password_expires_at->toIso8601String()], $email, 'warning');

        $this->components->info("{$email} es administradora.");
        $this->components->twoColumnDetail('Contraseña de única vez', $temporary);
        $this->components->twoColumnDetail('Vence', $user->password_expires_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i'));
        $this->newLine();
        $this->line('  Entregala por un canal seguro. Al ingresar, Tinku pide elegir una contraseña nueva.');

        return self::SUCCESS;
    }
}
