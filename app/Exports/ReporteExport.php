<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Base de las exportaciones del módulo Reportes.
 *
 * Cada exportación recibe los registros YA consultados por ReporteController,
 * los mismos con los que se arma el PDF. Antes cada exportación repetía su
 * propia consulta y se desincronizaban: el Excel de inscripciones y el de
 * participantes ignoraban el filtro de estado que el PDF sí aplicaba, así que
 * el mismo reporte daba resultados distintos según el formato.
 *
 * WithStrictNullComparison hace que un 0 se escriba como 0: sin ella la
 * librería lo trata como vacío y un semillero sin integrantes salía con la
 * celda en blanco en lugar de «0».
 */
abstract class ReporteExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, ShouldAutoSize, WithStrictNullComparison
{
    use Concerns\DaFormatoAlaHoja;

    public function __construct(
        protected Collection $registros,
        protected array $filtros = [],
    ) {
    }

    /** Consecutivo de la columna «N.º». */
    private int $numeroFila = 0;

    public function collection(): Collection
    {
        return $this->registros->values();
    }

    protected function siguienteNumero(): int
    {
        return ++$this->numeroFila;
    }
}
