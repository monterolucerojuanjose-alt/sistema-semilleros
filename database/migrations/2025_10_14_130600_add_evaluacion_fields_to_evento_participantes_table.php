<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aplica los cambios a la base de datos.
     */
    public function up(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            // Campo para asignar evaluador (Jurado o Docente)
            $table->foreignId('evaluador_id')->nullable()->after('revisado_por')->constrained('users')->nullOnDelete();

            // Campos de evaluación
            $table->decimal('nota_final', 5, 2)->nullable()->after('evaluador_id');
            $table->text('analisis_proyecto')->nullable()->after('nota_final');
            $table->text('fortalezas')->nullable()->after('analisis_proyecto');
            $table->text('debilidades')->nullable()->after('fortalezas');
            $table->text('recomendaciones')->nullable()->after('debilidades');

            // Estado de evaluación
            $table->enum('estado_evaluacion', ['sin_evaluar', 'en_revision', 'evaluado'])->default('sin_evaluar')->after('recomendaciones');
            $table->timestamp('fecha_evaluacion')->nullable()->after('estado_evaluacion');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            $table->dropForeign(['evaluador_id']);
            $table->dropColumn([
                'evaluador_id',
                'nota_final',
                'analisis_proyecto',
                'fortalezas',
                'debilidades',
                'recomendaciones',
                'estado_evaluacion',
                'fecha_evaluacion'
            ]);
        });
    }
};
