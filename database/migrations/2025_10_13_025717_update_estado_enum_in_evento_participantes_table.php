<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aplica los cambios a la base de datos.
     */
    public function up(): void
    {
        if (! $this->soportaEnum()) {
            return;
        }

        // Modificar el ENUM para agregar los nuevos estados de propuestas
        DB::statement("ALTER TABLE evento_participantes MODIFY COLUMN estado ENUM('pendiente', 'aprobado', 'rechazado', 'inscrito', 'asistio', 'no_asistio', 'cancelado') NOT NULL DEFAULT 'pendiente'");
    }

    /**
     * Deshace los cambios en la base de datos.
     */
    public function down(): void
    {
        if (! $this->soportaEnum()) {
            return;
        }

        // Revertir al ENUM original
        DB::statement("ALTER TABLE evento_participantes MODIFY COLUMN estado ENUM('inscrito', 'asistio', 'no_asistio', 'cancelado') NOT NULL DEFAULT 'inscrito'");
    }

    /**
     * El tipo ENUM y la sintaxis MODIFY COLUMN sólo existen en MySQL/MariaDB.
     * En otros motores (SQLite, usado por la suite de pruebas) la columna queda
     * como cadena y la restricción de valores la aplica la validación.
     */
    private function soportaEnum(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
