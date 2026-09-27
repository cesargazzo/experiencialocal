<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('tinku:admin {email : Email de la persona} {--name= : Nombre, si la cuenta todavía no existe} {--revoke : Quita el permiso de administración}')]
#[Description('Da o quita permiso de administración. Al darlo genera una contraseña de única vez que hay que cambiar en el primer ingreso.')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->components->error('El email no es válido.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($this->option('revoke')) {
            if (! $user?->is_admin) {
                $this->components->warn('Esa cuenta no es administradora.');

                return self::SUCCESS;
            }
            $user->forceFill(['is_admin' => false])->save();
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

        $user->forceFill(['is_admin' => true])->save();
        $temporary = $user->issueTemporaryPassword();

        $this->components->info("{$email} es administradora.");
        $this->components->twoColumnDetail('Contraseña de única vez', $temporary);
        $this->components->twoColumnDetail('Vence', $user->password_expires_at->timezone(config('tinku.timezone'))->format('d/m/Y H:i'));
        $this->newLine();
        $this->line('  Entregala por un canal seguro. Al ingresar, Tinku pide elegir una contraseña nueva.');

        return self::SUCCESS;
    }
}
