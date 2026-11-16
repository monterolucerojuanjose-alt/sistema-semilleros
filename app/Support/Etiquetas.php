<?php

namespace App\Support;

use App\Models\Evento;
use App\Models\Semillero;
use Carbon\Carbon;

/**
 * Textos de presentación compartidos por los reportes en PDF y en Excel.
 *
 * La base de datos guarda los valores tal como los usa el código («activo»,
 * «poster»), pero un documento oficial debe mostrarlos bien escritos
 * («Activo», «Póster»). Se centraliza aquí para que ambos formatos digan
 * exactamente lo mismo.
 */
class Etiquetas
{
    /** Escritura correcta de los estados que se guardan en minúscula. */
    private const ESTADOS = [
        'activo' => 'Activo',
        'inactivo' => 'Inactivo',
        'finalizado' => 'Finalizado',
        'evaluado' => 'Evaluado',
        'pendiente' => 'Pendiente',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'en_revision' => 'En revisión',
        'en revisión' => 'En revisión',
        'inscrito' => 'Inscrito',
        'aceptado' => 'Aceptado',
        'emitido' => 'Emitido',
        'anulado' => 'Anulado',
    ];

    private const TIPOS_INSCRIPCION = [
        'ponencia' => 'Ponencia',
        'poster' => 'Póster',
    ];

    public static function estado(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return 'Sin estado';
        }

        return self::ESTADOS[mb_strtolower($valor)] ?? self::primeraMayuscula($valor);
    }

    public static function tipoInscripcion(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return 'Sin tipo';
        }

        return self::TIPOS_INSCRIPCION[mb_strtolower($valor)] ?? self::primeraMayuscula($valor);
    }

    /** Nota con dos decimales y coma decimal, como se escribe en Colombia. */
    public static function nota($valor): string
    {
        return $valor === null ? 'Sin nota' : number_format((float) $valor, 2, ',', '.');
    }

    /**
     * Filtros con valor, ya traducidos a texto legible (nombres en vez de
     * identificadores y fechas en formato día/mes/año).
     *
     * @return array<string, string>
     */
    public static function filtrosAplicados(array $filtros): array
    {
        $aplicados = [];

        if (!empty($filtros['evento_id'])) {
            $aplicados['Evento'] = Evento::whereKey($filtros['evento_id'])->value('titulo') ?? "#{$filtros['evento_id']}";
        }
        if (!empty($filtros['semillero_id'])) {
            $aplicados['Semillero'] = Semillero::whereKey($filtros['semillero_id'])->value('nombre') ?? "#{$filtros['semillero_id']}";
        }
        if (!empty($filtros['fecha_inicio'])) {
            $aplicados['Desde'] = Carbon::parse($filtros['fecha_inicio'])->format('d/m/Y');
        }
        if (!empty($filtros['fecha_fin'])) {
            $aplicados['Hasta'] = Carbon::parse($filtros['fecha_fin'])->format('d/m/Y');
        }
        if (!empty($filtros['estado'])) {
            $aplicados['Estado'] = self::estado($filtros['estado']);
        }
        if (!empty($filtros['tipo'])) {
            $aplicados['Tipo'] = self::tipoInscripcion($filtros['tipo']);
        }

        return $aplicados;
    }

    private static function primeraMayuscula(string $texto): string
    {
        return mb_strtoupper(mb_substr($texto, 0, 1)) . mb_substr($texto, 1);
    }
}
