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
        Schema::create('inscripciones_evento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->enum('tipo', ['ponencia', 'poster']);
            $table->enum('estado', ['Inscrito', 'En revisión', 'Aceptado', 'Rechazado'])->default('Inscrito');
            $table->text('observaciones')->nullable();
            $table->string('codigo_inscripcion')->unique(); // Código único para la inscripción
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['proyecto_id', 'estado']);
            $table->index(['tipo', 'estado']);
            $table->index('codigo_inscripcion');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones_evento');
    }
};
