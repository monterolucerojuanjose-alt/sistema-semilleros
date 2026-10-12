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
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->onDelete('set null');
            $table->enum('tipo', ['Participante', 'Ponente', 'Jurado', 'Coordinador']);
            $table->string('url_pdf');
            $table->string('codigo_qr')->unique();
            $table->datetime('fecha_emision');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['usuario_id', 'tipo']);
            $table->index('codigo_qr');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};
