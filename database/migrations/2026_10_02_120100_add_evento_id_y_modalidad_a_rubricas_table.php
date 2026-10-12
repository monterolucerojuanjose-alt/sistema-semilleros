<?php

use App\Support\CatalogosEvento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una rúbrica puede quedar ligada a un evento y a una modalidad
     * concretos (para que el jurado la use al calificar las propuestas de
     * ese evento), o quedar genérica (ambos nulos) para seguir calificando
     * proyectos de catálogo como hasta ahora.
     */
    public function up(): void
    {
        Schema::table('rubricas', function (Blueprint $table) {
            $table->foreignId('evento_id')->nullable()->after('id')->constrained('eventos')->nullOnDelete();
            $table->enum('modalidad', CatalogosEvento::MODALIDADES)->nullable()->after('evento_id');
        });
    }

    public function down(): void
    {
        Schema::table('rubricas', function (Blueprint $table) {
            $table->dropForeign(['evento_id']);
            $table->dropColumn(['evento_id', 'modalidad']);
        });
    }
};
