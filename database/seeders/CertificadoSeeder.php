<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Certificado;
use App\Models\User;
use App\Models\Proyecto;

class CertificadoSeeder extends Seeder
{
    /**
     * Inserta los datos iniciales en la base de datos.
     */
    public function run(): void
    {
        $this->command->info('Creando certificados de prueba...');

        // Obtener usuarios
        $estudiantes = User::whereHas('roles', function($q) {
            $q->where('name', 'Estudiante');
        })->get();

        $jurados = User::whereHas('roles', function($q) {
            $q->where('name', 'Jurado');
        })->get();

        $coordinadores = User::whereHas('roles', function($q) {
            $q->where('name', 'Coordinador');
        })->get();

        $proyectos = Proyecto::where('estado', 'Aprobado')->get();

        // Crear certificados para estudiantes (participantes)
        foreach ($estudiantes->take(3) as $estudiante) {
            $proyecto = $proyectos->random();

            Certificado::create([
                'usuario_id' => $estudiante->id,
                'proyecto_id' => $proyecto->id,
                'tipo' => 'Participante',
                'url_pdf' => 'certificados/demo-' . $estudiante->id . '.pdf',
                'codigo_qr' => Certificado::generarCodigoQR(),
                'fecha_emision' => now()->subDays(rand(1, 30)),
            ]);
        }

        // Crear certificados para jurados
        foreach ($jurados->take(2) as $jurado) {
            Certificado::create([
                'usuario_id' => $jurado->id,
                'proyecto_id' => null,
                'tipo' => 'Jurado',
                'url_pdf' => 'certificados/demo-jurado-' . $jurado->id . '.pdf',
                'codigo_qr' => Certificado::generarCodigoQR(),
                'fecha_emision' => now()->subDays(rand(1, 15)),
            ]);
        }

        // Crear certificados para coordinadores
        foreach ($coordinadores->take(1) as $coordinador) {
            Certificado::create([
                'usuario_id' => $coordinador->id,
                'proyecto_id' => null,
                'tipo' => 'Coordinador',
                'url_pdf' => 'certificados/demo-coordinador-' . $coordinador->id . '.pdf',
                'codigo_qr' => Certificado::generarCodigoQR(),
                'fecha_emision' => now()->subDays(rand(1, 10)),
            ]);
        }

        $this->command->info('✅ Certificados de prueba creados exitosamente!');
        $this->command->info('Total certificados: ' . Certificado::count());
    }
}
