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
        Schema::table('semilleros', function (Blueprint $table) {
            $table->foreignId('coordinador_id')->nullable()->constrained('users')->onDelete('set null')->after('tutor_id');
        });
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        Schema::table('semilleros', function (Blueprint $table) {
            $table->dropForeign(['coordinador_id']);
            $table->dropColumn('coordinador_id');
        });
    }
};
