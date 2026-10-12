<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La escala institucional de calificación pasó de 0-5 a 0-100
     * (Evaluacion::NOTA_MAXIMA). Las notas finales ya calculadas bajo la
     * escala vieja se multiplican ×20 para conservar su significado
     * (4.5 → 90.0); los puntajes por criterio no se tocan porque están
     * acotados por el máximo propio de cada criterio, no por la nota final.
     */
    public function up(): void
    {
        DB::table('evaluaciones')->whereNotNull('nota_final')->update([
            'nota_final' => DB::raw('nota_final * 20'),
        ]);

        DB::table('evento_participantes')->whereNotNull('nota_final')->update([
            'nota_final' => DB::raw('nota_final * 20'),
        ]);
    }

    public function down(): void
    {
        DB::table('evaluaciones')->whereNotNull('nota_final')->update([
            'nota_final' => DB::raw('nota_final / 20'),
        ]);

        DB::table('evento_participantes')->whereNotNull('nota_final')->update([
            'nota_final' => DB::raw('nota_final / 20'),
        ]);
    }
};
