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
        Schema::table('evento_participantes', function (Blueprint $table) {
            // Campos para la propuesta del proyecto
            $table->string('titulo_proyecto')->nullable()->after('estado');
            $table->text('descripcion_proyecto')->nullable()->after('titulo_proyecto');
            $table->string('archivo_proyecto')->nullable()->after('descripcion_proyecto');
            $table->json('participantes')->nullable()->after('archivo_proyecto');

            // Campos para la programación (cuando se aprueba)
            $table->text('programacion')->nullable()->after('participantes');
            $table->string('aula')->nullable()->after('programacion');
            $table->string('horario')->nullable()->after('aula');

            // Campos para auditoría de aprobación/rechazo
            $table->timestamp('fecha_revision')->nullable()->after('horario');
            $table->foreignId('revisado_por')->nullable()->after('fecha_revision')->constrained('users');
            $table->text('motivo_rechazo')->nullable()->after('revisado_por');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            $table->dropForeign(['revisado_por']);
            $table->dropColumn([
                'titulo_proyecto',
                'descripcion_proyecto',
                'archivo_proyecto',
                'participantes',
                'programacion',
                'aula',
                'horario',
                'fecha_revision',
                'revisado_por',
                'motivo_rechazo',
            ]);
        });
    }
};
