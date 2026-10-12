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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('titulo');
            $table->text('mensaje');
            $table->enum('tipo', ['info', 'alerta', 'exito', 'error'])->default('info');
            $table->boolean('leida')->default(false);
            $table->json('datos_adicionales')->nullable(); // Para datos extra como enlaces, IDs, etc.
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['usuario_id', 'leida']);
            $table->index(['usuario_id', 'created_at']);
            $table->index('tipo');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
