<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos mínimos para que el sistema funcione: roles y permisos.
     *
     * Una instalación nueva queda EN BLANCO (sin semilleros, proyectos ni
     * usuarios de ejemplo), lista para configurarse. Después:
     *
     *   php artisan semilleros:crear-administrador   # primer administrador
     *
     * Para ver el sistema con datos de ejemplo:
     *
     *   php artisan db:seed --class=DatosDemostracionSeeder
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
