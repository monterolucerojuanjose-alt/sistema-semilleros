<?php

namespace App\Helpers;

use App\Models\User;

class RoleHelper
{
    /**
     * Verificar si el usuario actual tiene un rol específico
     */
    public static function hasRole(string $role): bool
    {
        return auth()->check() && auth()->user()->hasRole($role);
    }

    /**
     * Verificar si el usuario actual tiene alguno de los roles especificados
     */
    public static function hasAnyRole(array $roles): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole($roles);
    }

    /**
     * Verificar si el usuario actual tiene un permiso específico
     */
    public static function hasPermission(string $permission): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo($permission);
    }

    /**
     * Verificar si el usuario actual tiene alguno de los permisos especificados
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        return auth()->check() && auth()->user()->hasAnyPermission($permissions);
    }

    /**
     * Obtener el rol principal del usuario actual
     */
    public static function getCurrentUserRole(): ?string
    {
        if (!auth()->check()) {
            return null;
        }

        $user = auth()->user();
        $roles = $user->getRoleNames();

        return $roles->first();
    }

    /**
     * Verificar si el usuario es administrador
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('Administrador');
    }

    /**
     * Verificar si el usuario es coordinador
     */
    public static function isCoordinador(): bool
    {
        return self::hasRole('Coordinador');
    }

    /**
     * Verificar si el usuario es docente/tutor
     */
    public static function isDocente(): bool
    {
        return self::hasRole('Docente/Tutor');
    }

    /**
     * Verificar si el usuario es estudiante
     */
    public static function isEstudiante(): bool
    {
        return self::hasRole('Estudiante');
    }

    /**
     * Verificar si el usuario es jurado
     */
    public static function isJurado(): bool
    {
        return self::hasRole('Jurado');
    }

    /**
     * Verificar si el usuario puede gestionar usuarios
     */
    public static function canManageUsers(): bool
    {
        return self::hasAnyPermission(['users.index', 'users.create', 'users.edit', 'users.delete']);
    }

    /**
     * Verificar si el usuario puede gestionar semilleros
     */
    public static function canManageSemilleros(): bool
    {
        return self::hasAnyPermission(['semilleros.index', 'semilleros.create', 'semilleros.edit', 'semilleros.manage']);
    }

    /**
     * Verificar si el usuario puede gestionar proyectos
     */
    public static function canManageProyectos(): bool
    {
        return self::hasAnyPermission(['proyectos.index', 'proyectos.create', 'proyectos.edit', 'proyectos.supervise']);
    }

    /**
     * Verificar si el usuario puede generar reportes
     */
    public static function canGenerateReports(): bool
    {
        return self::hasAnyPermission(['reportes.index', 'reportes.create', 'reportes.export']);
    }

    /**
     * Verificar si el usuario puede evaluar proyectos/ponencias
     */
    public static function canEvaluate(): bool
    {
        return self::hasAnyPermission(['proyectos.evaluate', 'ponencias.evaluate', 'posteres.evaluate']);
    }

    /**
     * Obtener el nombre del rol en español
     */
    public static function getRoleDisplayName(string $role): string
    {
        $roleNames = [
            'Administrador' => 'Administrador',
            'Coordinador' => 'Coordinador',
            'Docente/Tutor' => 'Docente/Tutor',
            'Estudiante' => 'Estudiante',
            'Jurado' => 'Jurado',
        ];

        return $roleNames[$role] ?? $role;
    }

    /**
     * Obtener todos los roles disponibles
     */
    public static function getAvailableRoles(): array
    {
        return [
            'Administrador' => 'Administrador',
            'Coordinador' => 'Coordinador',
            'Docente/Tutor' => 'Docente/Tutor',
            'Estudiante' => 'Estudiante',
            'Jurado' => 'Jurado',
        ];
    }
}

