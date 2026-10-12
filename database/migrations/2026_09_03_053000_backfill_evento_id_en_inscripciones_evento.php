<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La migración 2025_10_16_051512 añadió la columna `evento_id` a
 * `inscripciones_evento`, pero no asignó valor a las filas que ya existían y el
 * seeder tampoco lo llenaba. En consecuencia, todas las inscripciones quedaron
 * con `evento_id` nulo y el filtro por evento del módulo de reportes (RF-11)
 * devolvía siempre cero resultados para proyectos, inscripciones y evaluaciones.
 *
 * Esta migración asocia esas inscripciones huérfanas al Encuentro de Semilleros
 * de Investigación, que es el evento institucional sobre el que trata el sistema.
 * Si no existe, se usa el evento más antiguo disponible.
 */
return new class extends Migration
{
    public function up(): void
    {
        $huerfanas = DB::table('inscripciones_evento')->whereNull('evento_id')->count();

        if ($huerfanas === 0) {
            return;
        }

        $evento = DB::table('eventos')
            ->where('titulo', 'like', '%Encuentro de Semilleros%')
            ->orderBy('id')
            ->first();

        $evento ??= DB::table('eventos')->orderBy('fecha_inicio')->first();

        if (! $evento) {
            // Sin eventos registrados no hay nada a lo que asociar las inscripciones.
            return;
        }

        DB::table('inscripciones_evento')
            ->whereNull('evento_id')
            ->update(['evento_id' => $evento->id]);
    }

    public function down(): void
    {
        // No se puede distinguir qué inscripciones tenían evento antes de la
        // corrección, por lo que revertir borraría información legítima.
    }
};
