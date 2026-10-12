<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade a las notificaciones el remitente y la fecha de lectura.
 *
 * Las notificaciones se mostraban sin indicar quién las enviaba, de modo que un
 * anuncio del administrador (HU-09) llegaba sin autor identificable. La fecha de
 * lectura permite además distinguir cuándo se leyó cada aviso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            // Nulo para los avisos que genera el propio sistema.
            $table->foreignId('remitente_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('leida_en')->nullable()->after('leida');
        });

        // Las notificaciones ya marcadas como leídas no tienen fecha registrada;
        // se toma la de su última actualización como mejor aproximación.
        \DB::table('notificaciones')
            ->where('leida', true)
            ->whereNull('leida_en')
            ->update(['leida_en' => \DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropForeign(['remitente_id']);
            $table->dropColumn(['remitente_id', 'leida_en']);
        });
    }
};
