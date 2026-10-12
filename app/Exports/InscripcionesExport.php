<?php

namespace App\Exports;

use App\Models\InscripcionEvento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InscripcionesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    use \App\Exports\Concerns\DaFormatoAlaHoja;

    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = InscripcionEvento::with(['proyecto.autor', 'proyecto.semillero', 'evento']);

        // Si se filtra por evento específico
        if (!empty($this->filtros['evento_id'])) {
            $query->where('evento_id', $this->filtros['evento_id']);
        }

        if (!empty($this->filtros['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $this->filtros['fecha_inicio']);
        }

        if (!empty($this->filtros['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $this->filtros['fecha_fin']);
        }

        if (!empty($this->filtros['semillero_id'])) {
            $query->whereHas('proyecto', function($q) {
                $q->where('semillero_id', $this->filtros['semillero_id']);
            });
        }

        if (!empty($this->filtros['tipo'])) {
            $query->where('tipo', $this->filtros['tipo']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Código Inscripción',
            'Evento',
            'Proyecto',
            'Autor',
            'Semillero',
            'Programa/Línea Temática',
            'Tipo',
            'Estado',
            'Observaciones',
            'Fecha Inscripción',
        ];
    }

    public function map($inscripcion): array
    {
        return [
            $inscripcion->id,
            $inscripcion->codigo_inscripcion,
            $inscripcion->evento->titulo ?? 'N/A',
            $inscripcion->proyecto->titulo ?? 'N/A',
            $inscripcion->proyecto->autor->name ?? 'N/A',
            $inscripcion->proyecto->semillero->nombre ?? 'N/A',
            $inscripcion->proyecto->semillero->linea_tematica ?? 'Sin programa',
            $inscripcion->tipo,
            $inscripcion->estado,
            $inscripcion->observaciones ?? '',
            $inscripcion->created_at->format('d/m/Y H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Inscripciones';
    }
}
