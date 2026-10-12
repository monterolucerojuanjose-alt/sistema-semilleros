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
        Schema::table('inscripciones_evento', function (Blueprint $table) {
            $table->foreignId('evento_id')->nullable()->after('proyecto_id')->constrained('eventos')->onDelete('cascade');
            $table->index('evento_id');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::table('inscripciones_evento', function (Blueprint $table) {
            $table->dropForeign(['evento_id']);
            $table->dropColumn('evento_id');
        });
    }
};
