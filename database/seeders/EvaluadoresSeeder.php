<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class EvaluadoresSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        // Asegurar que existan los roles
        $juradoRole = Role::firstOrCreate(['name' => 'Jurado']);
        $docenteRole = Role::firstOrCreate(['name' => 'Docente/Tutor']);

        // Crear Jurados
        $jurados = [
            [
                'name' => 'Dr. Carlos Martínez',
                'email' => 'jurado1@semilleros.edu',
                'password' => Hash::make('jurado123'),
                'estado' => 'activo',
            ],
            [
                'name' => 'Dra. Ana Ramírez',
                'email' => 'jurado2@semilleros.edu',
                'password' => Hash::make('jurado123'),
                'estado' => 'activo',
            ],
            [
                'name' => 'Mg. Patricia Torres',
                'email' => 'jurado3@semilleros.edu',
                'password' => Hash::make('jurado123'),
                'estado' => 'activo',
            ],
        ];

        foreach ($jurados as $juradoData) {
            $jurado = User::firstOrCreate(
                ['email' => $juradoData['email']],
                $juradoData
            );
            $jurado->syncRoles([$juradoRole]);
            $this->command->info("Jurado creado: {$jurado->name} ({$jurado->email})");
        }

        // Crear Docentes/Tutores adicionales
        $docentes = [
            [
                'name' => 'Prof. Luis García',
                'email' => 'docente1@semilleros.edu',
                'password' => Hash::make('docente123'),
                'estado' => 'activo',
            ],
            [
                'name' => 'Prof. María Fernández',
                'email' => 'docente2@semilleros.edu',
                'password' => Hash::make('docente123'),
                'estado' => 'activo',
            ],
        ];

        foreach ($docentes as $docenteData) {
            $docente = User::firstOrCreate(
                ['email' => $docenteData['email']],
                $docenteData
            );
            $docente->syncRoles([$docenteRole]);
            $this->command->info("Docente creado: {$docente->name} ({$docente->email})");
        }

        $this->command->info("\n✅ Evaluadores creados exitosamente!");
        $this->command->info("\nCredenciales de acceso:");
        $this->command->info("======================");
        $this->command->info("\nJURADOS:");
        $this->command->info("- jurado1@semilleros.edu / jurado123");
        $this->command->info("- jurado2@semilleros.edu / jurado123");
        $this->command->info("- jurado3@semilleros.edu / jurado123");
        $this->command->info("\nDOCENTES:");
        $this->command->info("- docente1@semilleros.edu / docente123");
        $this->command->info("- docente2@semilleros.edu / docente123");
    }
}







