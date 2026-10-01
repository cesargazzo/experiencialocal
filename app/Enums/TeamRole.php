<?php

namespace App\Enums;

/** Roles del equipo de Tinku. Cada uno tiene solo los permisos que necesita. */
enum TeamRole: string
{
    case Admin = 'admin';
    case Verifier = 'verifier';
    case Moderator = 'moderator';
    case Support = 'support';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administración total',
            self::Verifier => 'Verificación de identidad',
            self::Moderator => 'Moderación de contenido',
            self::Support => 'Soporte',
            self::Auditor => 'Auditoría',
        };
    }

    /** Para encabezados angostos. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Admin => 'Total',
            self::Verifier => 'Verificación',
            self::Moderator => 'Moderación',
            self::Support => 'Soporte',
            self::Auditor => 'Auditoría',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Todo, incluso configurar la plataforma y asignar roles.',
            self::Verifier => 'Revisa documentos y valida cuentas. Ve los datos personales.',
            self::Moderator => 'Aprueba, rechaza y pausa experiencias y lee denuncias. No ve datos personales de usuarios.',
            self::Support => 'Atiende a las personas: ve sus cuentas y puede suspenderlas. No valida identidades.',
            self::Auditor => 'Solo lectura del registro de seguridad, la auditoría y los errores.',
        };
    }

    /** @return list<TeamPermission> */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => TeamPermission::cases(),
            self::Verifier => [TeamPermission::ViewUsers, TeamPermission::VerifyIdentity],
            self::Moderator => [TeamPermission::ModerateExperiences, TeamPermission::HandleReports],
            self::Support => [TeamPermission::ViewUsers, TeamPermission::SuspendUsers],
            self::Auditor => [TeamPermission::ViewSecurity],
        };
    }

    public function grants(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
