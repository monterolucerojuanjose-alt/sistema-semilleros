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
        Schema::create('criterio_rubricas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubrica_id')->constrained('rubricas')->onDelete('cascade');
            $table->string('criterio');
            $table->decimal('peso', 5, 2); // Porcentaje con 2 decimales
            $table->integer('puntaje_maximo')->default(5); // Puntaje máximo para este criterio
            $table->timestamps();

            // Índices
            $table->index('rubrica_id');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('criterio_rubricas');
    }
};
