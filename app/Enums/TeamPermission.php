<?php

namespace App\Enums;

/**
 * Qué puede hacer cada persona del equipo en la administración. Cada sección pide
 * un permiso: nadie ve datos personales que no necesita para su tarea.
 */
enum TeamPermission: string
{
    /** Lista y ficha de usuarios: nombre completo, email, teléfono, domicilio, edad. */
    case ViewUsers = 'team.users.view';

    /** Revisar documentos, validar niveles y revocar verificaciones. */
    case VerifyIdentity = 'team.users.verify';

    /** Suspender y reactivar cuentas. */
    case SuspendUsers = 'team.users.suspend';

    /** Revisar, aprobar, rechazar y pausar experiencias. */
    case ModerateExperiences = 'team.experiences.moderate';

    /** Leer mensajes denunciados. */
    case HandleReports = 'team.reports.handle';

    /** Registro de seguridad, auditoría de cambios y errores del servidor. */
    case ViewSecurity = 'team.security.view';

    /** Términos, política de contraseñas, configuración y roles del equipo. */
    case ManagePlatform = 'team.platform.manage';

    public function label(): string
    {
        return match ($this) {
            self::ViewUsers => 'Ver usuarios y sus datos personales',
            self::VerifyIdentity => 'Validar identidades',
            self::SuspendUsers => 'Suspender cuentas',
            self::ModerateExperiences => 'Moderar experiencias',
            self::HandleReports => 'Leer denuncias',
            self::ViewSecurity => 'Ver seguridad, auditoría y errores',
            self::ManagePlatform => 'Configurar la plataforma y el equipo',
        };
    }
}
