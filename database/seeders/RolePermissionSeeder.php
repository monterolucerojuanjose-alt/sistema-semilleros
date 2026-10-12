<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        // Limpiar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            // Gestión de usuarios
            'users.index',
            'users.create',
            'users.edit',
            'users.delete',
            'users.view',

            // Gestión de roles
            'roles.index',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.assign',

            // Gestión de semilleros
            'semilleros.index',
            'semilleros.create',
            'semilleros.edit',
            'semilleros.delete',
            'semilleros.view',
            'semilleros.approve',
            'semilleros.manage',

            // Gestión de proyectos
            'proyectos.index',
            'proyectos.create',
            'proyectos.edit',
            'proyectos.delete',
            'proyectos.view',
            'proyectos.supervise',
            'proyectos.evaluate',

            // Gestión de estudiantes
            'estudiantes.index',
            'estudiantes.create',
            'estudiantes.edit',
            'estudiantes.delete',
            'estudiantes.view',
            'estudiantes.approve',
            'estudiantes.assign',

            // Gestión de solicitudes
            'solicitudes.index',
            'solicitudes.create',
            'solicitudes.edit',
            'solicitudes.delete',
            'solicitudes.view',
            'solicitudes.approve',
            'solicitudes.reject',

            // Gestión de jurados
            'jurados.index',
            'jurados.create',
            'jurados.edit',
            'jurados.delete',
            'jurados.view',
            'jurados.assign',

            // Gestión de ponencias
            'ponencias.index',
            'ponencias.create',
            'ponencias.edit',
            'ponencias.delete',
            'ponencias.view',
            'ponencias.evaluate',
            'ponencias.approve',

            // Gestión de pósteres
            'posteres.index',
            'posteres.create',
            'posteres.edit',
            'posteres.delete',
            'posteres.view',
            'posteres.evaluate',
            'posteres.approve',

            // Reportes y certificados
            'reportes.index',
            'reportes.create',
            'reportes.view',
            'reportes.export',
            'certificados.generate',
            'certificados.view',

            // Configuraciones del sistema
            'configuraciones.index',
            'configuraciones.edit',
            'configuraciones.view',

            // Dashboard y estadísticas
            'dashboard.view',
            'estadisticas.view',
        ];

        // Crear permisos
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Crear roles y asignar permisos

        // 1. ADMINISTRADOR - Control total del sistema
        $adminRole = Role::create(['name' => 'Administrador']);
        $adminRole->givePermissionTo(Permission::all());

        // 2. COORDINADOR - Gestiona semilleros, aprueba solicitudes, asigna jurados, genera reportes
        $coordinadorRole = Role::create(['name' => 'Coordinador']);
        $coordinadorRole->givePermissionTo([
            // Gestión de semilleros
            'semilleros.index',
            'semilleros.create',
            'semilleros.edit',
            'semilleros.view',
            'semilleros.manage',

            // Gestión de proyectos
            'proyectos.index',
            'proyectos.view',

            // Gestión de estudiantes
            'estudiantes.index',
            'estudiantes.view',
            'estudiantes.approve',
            'estudiantes.assign',

            // Gestión de solicitudes
            'solicitudes.index',
            'solicitudes.view',
            'solicitudes.approve',
            'solicitudes.reject',

            // Gestión de jurados
            'jurados.index',
            'jurados.view',
            'jurados.assign',

            // Gestión de ponencias y pósteres
            'ponencias.index',
            'ponencias.view',
            'ponencias.approve',
            'posteres.index',
            'posteres.view',
            'posteres.approve',

            // Reportes y certificados
            'reportes.index',
            'reportes.create',
            'reportes.view',
            'reportes.export',
            'certificados.generate',
            'certificados.view',

            // Dashboard
            'dashboard.view',
            'estadisticas.view',
        ]);

        // 3. DOCENTE/TUTOR - Supervisa proyectos de sus semilleros, revisa entregas
        $docenteRole = Role::create(['name' => 'Docente/Tutor']);
        $docenteRole->givePermissionTo([
            // Gestión de semilleros (solo los suyos)
            'semilleros.index',
            'semilleros.view',

            // Gestión de proyectos (solo los de sus semilleros)
            'proyectos.index',
            'proyectos.view',
            'proyectos.supervise',

            // Gestión de estudiantes (solo los de sus semilleros)
            'estudiantes.index',
            'estudiantes.view',

            // Gestión de solicitudes (solo las de sus semilleros)
            'solicitudes.index',
            'solicitudes.view',
            'solicitudes.approve',
            'solicitudes.reject',

            // Gestión de ponencias y pósteres (solo las de sus semilleros)
            'ponencias.index',
            'ponencias.view',
            'posteres.index',
            'posteres.view',

            // Dashboard
            'dashboard.view',
        ]);

        // 4. ESTUDIANTE - Registra proyectos, solicita unirse a semilleros, inscribe ponencias/pósteres
        $estudianteRole = Role::create(['name' => 'Estudiante']);
        $estudianteRole->givePermissionTo([
            // Gestión de proyectos (solo los suyos)
            'proyectos.index',
            'proyectos.create',
            'proyectos.edit',
            'proyectos.view',

            // Gestión de solicitudes (solo las suyas)
            'solicitudes.index',
            'solicitudes.create',
            'solicitudes.view',

            // Gestión de ponencias y pósteres (solo las suyas)
            'ponencias.index',
            'ponencias.create',
            'ponencias.edit',
            'ponencias.view',
            'posteres.index',
            'posteres.create',
            'posteres.edit',
            'posteres.view',

            // Dashboard
            'dashboard.view',
        ]);

        // 5. JURADO - Evalúa proyectos y ponencias asignadas mediante rúbricas
        $juradoRole = Role::create(['name' => 'Jurado']);
        $juradoRole->givePermissionTo([
            // Gestión de proyectos (solo para evaluar)
            'proyectos.index',
            'proyectos.view',
            'proyectos.evaluate',

            // Gestión de ponencias (solo para evaluar)
            'ponencias.index',
            'ponencias.view',
            'ponencias.evaluate',

            // Gestión de pósteres (solo para evaluar)
            'posteres.index',
            'posteres.view',
            'posteres.evaluate',

            // Dashboard
            'dashboard.view',
        ]);

        $this->command->info('Roles y permisos creados exitosamente!');
        $this->command->info('Roles creados: Administrador, Coordinador, Docente/Tutor, Estudiante, Jurado');
        $this->command->info('Total de permisos creados: ' . count($permissions));
    }
}
