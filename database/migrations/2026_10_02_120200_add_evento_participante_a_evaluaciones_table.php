<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hasta ahora una Evaluacion siempre calificaba un Proyecto de
     * catálogo. Para poder calificar también una propuesta de evento
     * (EventoParticipante) con el mismo sistema de rúbricas y criterios,
     * «proyecto_id» pasa a ser opcional y se agrega «evento_participante_id»:
     * cada fila llena exactamente uno de los dos, nunca ambos.
     */
    public function up(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropUnique(['proyecto_id', 'jurado_id']);
            $table->dropForeign(['proyecto_id']);
        });

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('proyecto_id')->nullable()->change();
        });

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->cascadeOnDelete();

            $table->foreignId('evento_participante_id')
                ->nullable()
                ->after('proyecto_id')
                ->constrained('evento_participantes')
                ->cascadeOnDelete();

            $table->unique(['proyecto_id', 'jurado_id']);
            $table->unique(['evento_participante_id', 'jurado_id']);
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropUnique(['evento_participante_id', 'jurado_id']);
            $table->dropUnique(['proyecto_id', 'jurado_id']);
            $table->dropForeign(['evento_participante_id']);
            $table->dropColumn('evento_participante_id');
            $table->dropForeign(['proyecto_id']);
        });

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('proyecto_id')->nullable(false)->change();
        });

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->cascadeOnDelete();
            $table->unique(['proyecto_id', 'jurado_id']);
        });
    }
};
