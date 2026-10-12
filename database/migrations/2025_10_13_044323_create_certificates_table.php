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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // Código único del certificado
            $table->string('titulo'); // Título del certificado
            $table->text('descripcion')->nullable(); // Descripción del logro
            $table->string('tipo')->default('participacion'); // Tipo: participacion, ponencia, poster, etc.
            $table->date('fecha_emision'); // Fecha de emisión
            $table->string('estado')->default('activo'); // activo, revocado

            // Relaciones
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Estudiante que recibe el certificado
            $table->foreignId('evento_id')->nullable()->constrained()->onDelete('cascade'); // Evento relacionado
            $table->foreignId('emitido_por')->constrained('users')->onDelete('cascade'); // Coordinador/Admin que emite

            // Información adicional
            $table->string('archivo_certificado')->nullable(); // Ruta del archivo PDF generado
            $table->json('metadata')->nullable(); // Información adicional (horas, créditos, etc.)
            $table->text('observaciones')->nullable(); // Observaciones del emisor

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'estado']);
            $table->index(['evento_id', 'estado']);
            $table->index('codigo');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
