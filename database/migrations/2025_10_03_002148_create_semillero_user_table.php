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
        Schema::create('semillero_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('semillero_id')->constrained('semilleros')->onDelete('cascade');
            $table->string('rol_en_semillero')->default('estudiante');
            $table->enum('estado_solicitud', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['user_id', 'semillero_id']);
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('semillero_user');
    }
};
