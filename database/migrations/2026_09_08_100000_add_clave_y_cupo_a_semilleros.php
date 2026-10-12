<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que un semillero sea de acceso restringido y tenga un cupo.
 *
 * - `clave_acceso`: opcional. Si el semillero la tiene, el estudiante debe
 *   escribirla para poder solicitar su ingreso. Se guarda cifrada.
 * - `cupo_maximo`: opcional. Número máximo de integrantes aprobados; al
 *   alcanzarlo, el semillero deja de admitir solicitudes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semilleros', function (Blueprint $table) {
            $table->string('clave_acceso')->nullable()->after('estado');
            $table->unsignedSmallInteger('cupo_maximo')->nullable()->after('clave_acceso');
        });
    }

    public function down(): void
    {
        Schema::table('semilleros', function (Blueprint $table) {
            $table->dropColumn(['clave_acceso', 'cupo_maximo']);
        });
    }
};
