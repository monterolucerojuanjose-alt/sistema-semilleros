<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aplica los cambios a la base de datos.
     *
     * Cuenta cuántas veces un estudiante ha enviado una propuesta para el
     * mismo evento. Empieza en 1 con el primer envío y sube cada vez que
     * corrige y reenvía una propuesta rechazada, para que el coordinador
     * distinga a simple vista una propuesta nueva de una ya corregida.
     */
    public function up(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            $table->unsignedTinyInteger('intentos_envio')->default(1)->after('participantes');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            $table->dropColumn('intentos_envio');
        });
    }
};
