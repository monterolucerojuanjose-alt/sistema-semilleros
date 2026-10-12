<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * ============================================================================
 * COMANDO DE CONSOLA: ASIGNAR UN ROL A UN USUARIO
 * ============================================================================
 *
 * Asigna un rol desde la terminal, sin pasar por la interfaz web.
 *
 * Resuelve el problema del «primer administrador»: en una instalación nueva
 * no hay ninguna cuenta con permisos para entrar a la gestión de usuarios y
 * repartir roles. Este comando rompe ese círculo desde fuera del sistema.
 *
 * Uso:
 *   php artisan user:assign-role correo@ejemplo.com Administrador
 */
class AssignRoleCommand extends Command
{
    /**
     * Nombre del comando y argumentos que acepta.
     *
     * Lo que va entre llaves son argumentos obligatorios: primero el correo
     * del usuario y después el nombre exacto del rol.
     *
     * @var string
     */
    protected $signature = 'user:assign-role {email} {role}';

    /**
     * Descripción que aparece al ejecutar «php artisan list».
     *
     * @var string
     */
    protected $description = 'Asignar un rol a un usuario por email';

    /**
     * Ejecuta el comando.
     *
     * @return int 0 si el rol se asignó correctamente, 1 si hubo un error.
     *             La terminal usa ese número para saber si el comando funcionó.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        // ---------------------------------------------------------------
        // 1. Buscar al usuario por su correo
        // ---------------------------------------------------------------
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario con email '{$email}' no encontrado.");
            return 1;
        }

        // ---------------------------------------------------------------
        // 2. Comprobar que el rol existe
        // ---------------------------------------------------------------
        // Se comprueba antes de asignar porque assignRole lanzaría una
        // excepción con un mensaje técnico. Así el error es legible y
        // además se listan los roles válidos, que es lo que se necesita
        // cuando el fallo es una errata (por ejemplo «Docente» en lugar
        // de «Docente/Tutor»).
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Rol '{$roleName}' no encontrado.");
            $this->info("Roles disponibles: " . Role::pluck('name')->implode(', '));
            return 1;
        }

        // ---------------------------------------------------------------
        // 3. Asignar el rol
        // ---------------------------------------------------------------
        // assignRole suma el rol a los que ya tenga: no reemplaza ni elimina
        // los anteriores.
        $user->assignRole($roleName);

        $this->info("Rol '{$roleName}' asignado exitosamente al usuario '{$user->name}' ({$email}).");

        return 0;
    }
}
