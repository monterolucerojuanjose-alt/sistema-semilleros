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
            $table->enum('carrera', [
                'Nutricion',
                'Enfermeria',
                'Ingenieria de Sistemas',
                'Radiologia',
                'Ingenieria Ambiental',
                'Mecatronica',
                'Derecho',
                'Terapia Ocupacional'
            ])->nullable()->after('user_id');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            $table->dropColumn('carrera');
        });
    }
};
