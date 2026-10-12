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
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->foreignId('jurado_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rubrica_id')->constrained('rubricas')->onDelete('cascade');
            $table->text('observaciones')->nullable();
            $table->decimal('nota_final', 5, 2)->nullable(); // Nota final calculada
            $table->enum('estado', ['Pendiente', 'Evaluado'])->default('Pendiente');
            $table->timestamps();

            // Índices
            $table->index(['proyecto_id', 'jurado_id']);
            $table->index('rubrica_id');

            // Constraint único: un jurado no puede evaluar dos veces el mismo proyecto
            $table->unique(['proyecto_id', 'jurado_id']);
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};
