<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lleva las calificaciones de rúbrica a la escala institucional 0.0 – 5.0.
 *
 * `Evaluacion::calcularNotaFinal()` normalizaba cada criterio sobre 100, de modo
 * que las notas quedaban entre 0 y 100 (75 a 97 en los datos existentes),
 * mientras que la evaluación de proyectos de eventos ya usaba 0 a 5. Convivían
 * dos escalas distintas para lo mismo.
 *
 * Las notas ya guardadas se dividen entre 20 para conservar su significado:
 * 97 → 4.85 y 75 → 3.75.
 */
return new class extends Migration
{
    private const FACTOR = 20;

    public function up(): void
    {
        DB::table('evaluaciones')
            ->whereNotNull('nota_final')
            ->where('nota_final', '>', 5)
            ->update(['nota_final' => DB::raw('ROUND(nota_final / ' . self::FACTOR . ', 2)')]);
    }

    public function down(): void
    {
        DB::table('evaluaciones')
            ->whereNotNull('nota_final')
            ->where('nota_final', '<=', 5)
            ->update(['nota_final' => DB::raw('ROUND(nota_final * ' . self::FACTOR . ', 2)')]);
    }
};
