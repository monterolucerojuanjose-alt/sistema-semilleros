<?php

namespace App\Exports;

use App\Support\Etiquetas;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Recibe las evaluaciones ya normalizadas por ReporteController: tanto las
 * hechas con rúbrica sobre proyectos del catálogo como las calificaciones de
 * propuestas de eventos.
 */
class EvaluacionesExport extends ReporteExport implements WithColumnFormatting
{
    public function headings(): array
    {
        return [
            'N.º',
            'Proyecto',
            'Autor',
            'Semillero / origen',
            'Línea temática / área',
            'Modalidad',
            'Jurado',
            'Rúbrica / evento',
            'Nota (0-100)',
            'Estado',
            'Observaciones',
            'Fecha de evaluación',
        ];
    }

    public function map($fila): array
    {
        return [
            $this->siguienteNumero(),
            $fila['proyecto'],
            $fila['autor'],
            $fila['semillero'],
            $fila['programa'] ?: 'Sin línea temática',
            $fila['modalidad'],
            $fila['jurado'],
            $fila['origen'],
            // Número real (no texto) para que Excel pueda sumar y promediar.
            $fila['nota_final'] !== null ? round((float) $fila['nota_final'], 2) : 'Sin nota',
            Etiquetas::estado($fila['estado']),
            $fila['observaciones'] ?: '—',
            optional($fila['fecha'])->format('d/m/Y'),
        ];
    }

    public function columnFormats(): array
    {
        return ['I' => NumberFormat::FORMAT_NUMBER_00];
    }

    public function title(): string
    {
        return 'Evaluaciones';
    }
}
