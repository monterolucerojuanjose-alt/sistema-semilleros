<?php

use App\Support\CatalogosEvento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La convocatoria exige que el estudiante declare la modalidad y el
     * área de participación de su propuesta. «carrera» no se elimina: las
     * inscripciones ya hechas la conservan como dato histórico, pero los
     * formularios nuevos piden estos dos campos en su lugar.
     */
    public function up(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            $table->enum('modalidad', CatalogosEvento::MODALIDADES)->nullable()->after('carrera');
            $table->enum('area_participacion', CatalogosEvento::AREAS_PARTICIPACION)->nullable()->after('modalidad');
        });
    }

    public function down(): void
    {
        Schema::table('evento_participantes', function (Blueprint $table) {
            $table->dropColumn(['modalidad', 'area_participacion']);
        });
    }
};
