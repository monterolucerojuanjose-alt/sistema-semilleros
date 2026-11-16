<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Paso de APP_TIMEZONE=UTC a America/Bogota (UTC-5, sin horario de verano).
 *
 * Laravel guarda las fechas en la zona de la aplicación sin convertirlas, así
 * que todo lo que el sistema grabó con now() está en hora UTC. Con la zona
 * nueva esas fechas se leerían 5 horas adelantadas, por eso se les restan.
 *
 * NO se tocan:
 *  - eventos.fecha_inicio / fecha_fin: las escribe el usuario en el
 *    formulario, ya en hora local. Antes se mostraban 5 horas antes en el
 *    navegador; con la zona nueva quedan correctas sin moverlas.
 *  - failed_jobs.failed_at: la llena MySQL con CURRENT_TIMESTAMP, que ya
 *    está en hora del sistema (Colombia).
 *  - Columnas de sólo fecha (DATE), como certificates.fecha_emision.
 */
return new class extends Migration
{
    private const DESFASE_HORAS = 5;

    /** Columnas que el sistema llena con now(), por tabla. */
    private const COLUMNAS = [
        'auditorias' => ['created_at', 'updated_at'],
        'certificados' => ['created_at', 'updated_at', 'fecha_emision'],
        'certificates' => ['created_at', 'updated_at'],
        'criterio_rubricas' => ['created_at', 'updated_at'],
        'detalle_evaluaciones' => ['created_at', 'updated_at'],
        'evaluaciones' => ['created_at', 'updated_at'],
        'eventos' => ['created_at', 'updated_at'],
        'evento_participantes' => ['created_at', 'updated_at', 'fecha_revision', 'fecha_evaluacion'],
        'inscripciones_evento' => ['created_at', 'updated_at'],
        'notificaciones' => ['created_at', 'updated_at', 'leida_en'],
        'password_reset_tokens' => ['created_at'],
        'permissions' => ['created_at', 'updated_at'],
        'proyectos' => ['created_at', 'updated_at'],
        'roles' => ['created_at', 'updated_at'],
        'rubricas' => ['created_at', 'updated_at'],
        'semilleros' => ['created_at', 'updated_at'],
        'semillero_user' => ['created_at', 'updated_at', 'fecha'],
        'users' => ['created_at', 'updated_at', 'email_verified_at'],
        'versiones_proyecto' => ['created_at', 'updated_at'],
    ];

    public function up(): void
    {
        $this->desplazar('DATE_SUB');
    }

    public function down(): void
    {
        $this->desplazar('DATE_ADD');
    }

    private function desplazar(string $funcion): void
    {
        // Las pruebas corren en SQLite en memoria (tablas vacías y sin
        // DATE_SUB): ahí no hay nada que convertir.
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $schema = DB::getDatabaseName();

        DB::transaction(function () use ($funcion, $schema) {
            foreach (self::COLUMNAS as $tabla => $columnas) {
                // Sólo las columnas que existan de verdad en esta base.
                $existentes = DB::table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', $schema)
                    ->where('TABLE_NAME', $tabla)
                    ->whereIn('COLUMN_NAME', $columnas)
                    ->pluck('COLUMN_NAME')
                    ->all();

                foreach ($existentes as $columna) {
                    DB::statement(sprintf(
                        'UPDATE `%s` SET `%s` = %s(`%s`, INTERVAL %d HOUR) WHERE `%s` IS NOT NULL',
                        $tabla, $columna, $funcion, $columna, self::DESFASE_HORAS, $columna
                    ));
                }
            }
        });
    }
};
