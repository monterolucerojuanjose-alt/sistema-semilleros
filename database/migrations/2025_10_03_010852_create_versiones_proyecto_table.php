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
        Schema::create('versiones_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->string('archivo'); // Ruta del archivo
            $table->text('comentario')->nullable();
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['proyecto_id', 'created_at']);
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('versiones_proyecto');
    }
};
