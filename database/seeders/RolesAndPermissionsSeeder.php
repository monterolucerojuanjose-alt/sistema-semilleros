<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        // Crear permisos
        $permissions = [
            'usuarios.gestionar',
            'semilleros.gestionar',
            'proyectos.crear',
            'proyectos.aprobar',
            'evaluaciones.realizar',
            'reportes.ver',
            'certificados.generar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles
        $adminRole = Role::firstOrCreate(['name' => 'Administrador']);
        $coordinadorRole = Role::firstOrCreate(['name' => 'Coordinador']);
        $docenteRole = Role::firstOrCreate(['name' => 'Docente/Tutor']);
        $estudianteRole = Role::firstOrCreate(['name' => 'Estudiante']);
        $juradoRole = Role::firstOrCreate(['name' => 'Jurado']);

        // Asignar permisos a roles
        $adminRole->syncPermissions($permissions); // Admin tiene todos
        $coordinadorRole->syncPermissions(['semilleros.gestionar', 'proyectos.aprobar', 'reportes.ver', 'certificados.generar']);
        $docenteRole->syncPermissions([]); // Sin permisos específicos por ahora
        $estudianteRole->syncPermissions(['proyectos.crear']);
        $juradoRole->syncPermissions(['evaluaciones.realizar']);

        // Crear usuario administrador
        $admin = User::firstOrCreate(
            ['email' => 'admin@semilleros.edu'],
            [
                'name' => 'Admin General',
                'password' => Hash::make('admin123'),
                'rol_id' => $adminRole->id,
                'estado' => 'activo',
            ]
        );
        $admin->assignRole($adminRole);
    }
}
