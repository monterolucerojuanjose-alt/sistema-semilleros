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
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('resumen');
            $table->string('codigo')->unique(); // Formato: FAC-YYYY-NNN
            $table->enum('estado', ['Inscrito', 'En revisión', 'Aprobado', 'Rechazado'])->default('Inscrito');
            $table->foreignId('semillero_id')->constrained('semilleros')->onDelete('cascade');
            $table->foreignId('autor_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['semillero_id', 'estado']);
            $table->index(['autor_id', 'estado']);
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
