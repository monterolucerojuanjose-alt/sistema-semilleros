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
        Schema::create('detalle_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->onDelete('cascade');
            $table->foreignId('criterio_id')->constrained('criterio_rubricas')->onDelete('cascade');
            $table->integer('puntaje'); // Puntaje otorgado al criterio
            $table->timestamps();

            // Índices
            $table->index('evaluacion_id');
            $table->index('criterio_id');

            // Constraint único: una evaluación no puede tener dos puntajes para el mismo criterio
            $table->unique(['evaluacion_id', 'criterio_id']);
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_evaluaciones');
    }
};
