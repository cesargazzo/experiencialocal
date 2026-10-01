<?php

namespace App\Support;

use App\Enums\TeamPermission;
use App\Models\User;

/**
 * Secciones de la administración con el permiso que pide cada una.
 * La barra muestra solo las que la persona puede abrir.
 */
class AdminSections
{
    /** @var list<array{route: string, label: string, active: string, permission: TeamPermission}> */
    private const SECTIONS = [
        ['route' => 'admin.usuarios', 'label' => 'Usuarios', 'active' => 'admin.usuarios*', 'permission' => TeamPermission::ViewUsers],
        ['route' => 'admin.verificaciones', 'label' => 'Verificaciones', 'active' => 'admin.verificaciones', 'permission' => TeamPermission::VerifyIdentity],
        ['route' => 'admin.experiencias', 'label' => 'Experiencias', 'active' => 'admin.experiencias*', 'permission' => TeamPermission::ModerateExperiences],
        ['route' => 'admin.denuncias', 'label' => 'Denuncias', 'active' => 'admin.denuncias', 'permission' => TeamPermission::HandleReports],
        ['route' => 'admin.equipo', 'label' => 'Equipo', 'active' => 'admin.equipo', 'permission' => TeamPermission::ManagePlatform],
        ['route' => 'admin.terminos', 'label' => 'Términos', 'active' => 'admin.terminos*', 'permission' => TeamPermission::ManagePlatform],
        ['route' => 'admin.contrasenas', 'label' => 'Contraseñas', 'active' => 'admin.contrasenas', 'permission' => TeamPermission::ManagePlatform],
        ['route' => 'admin.seguridad', 'label' => 'Seguridad', 'active' => 'admin.seguridad', 'permission' => TeamPermission::ViewSecurity],
        ['route' => 'admin.registro', 'label' => 'Errores', 'active' => 'admin.registro', 'permission' => TeamPermission::ViewSecurity],
        ['route' => 'admin.auditoria', 'label' => 'Auditoría', 'active' => 'admin.auditoria', 'permission' => TeamPermission::ViewSecurity],
        ['route' => 'admin.configuracion', 'label' => 'Configuración', 'active' => 'admin.configuracion', 'permission' => TeamPermission::ManagePlatform],
    ];

    /** @return list<array{route: string, label: string, active: string, permission: TeamPermission}> */
    public static function for(User $user): array
    {
        return array_values(array_filter(self::SECTIONS, fn (array $section): bool => $user->hasTeamPermission($section['permission'])));
    }
}
