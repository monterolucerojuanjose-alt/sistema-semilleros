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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('accion'); // create, update, delete, login, logout, etc.
            $table->string('entidad_afectada'); // User, Proyecto, Semillero, etc.
            $table->string('entidad_id')->nullable(); // ID de la entidad afectada
            $table->text('detalle')->nullable(); // JSON o texto con detalles del cambio
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('datos_antes')->nullable(); // Datos antes del cambio (para updates)
            $table->json('datos_despues')->nullable(); // Datos después del cambio (para updates)
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['usuario_id', 'created_at']);
            $table->index(['entidad_afectada', 'created_at']);
            $table->index(['accion', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
