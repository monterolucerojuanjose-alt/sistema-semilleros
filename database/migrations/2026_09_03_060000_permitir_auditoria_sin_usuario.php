<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite registrar acciones de auditoría sin usuario asociado.
 *
 * Es necesario para dejar constancia de los intentos de inicio de sesión
 * fallidos con correos que no corresponden a ninguna cuenta: sin esto, el
 * indicio más útil para detectar accesos no autorizados no puede registrarse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
        });

        Schema::table('auditorias', function (Blueprint $table) {
            $table->foreignId('usuario_id')->nullable()->change();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
        });

        // Las filas sin usuario no caben en la definición anterior.
        \DB::table('auditorias')->whereNull('usuario_id')->delete();

        Schema::table('auditorias', function (Blueprint $table) {
            $table->foreignId('usuario_id')->nullable(false)->change();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
