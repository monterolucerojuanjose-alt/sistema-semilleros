<?php

namespace App\Exports;

use App\Models\Proyecto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProyectosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    use \App\Exports\Concerns\DaFormatoAlaHoja;

    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = Proyecto::with(['autor', 'semillero']);

        // Si se filtra por evento, solo mostrar proyectos inscritos en ese evento
        if (!empty($this->filtros['evento_id'])) {
            $query->whereHas('inscripciones', function($q) {
                $q->where('evento_id', $this->filtros['evento_id']);
            })->with(['inscripciones' => function($q) {
                $q->where('evento_id', $this->filtros['evento_id']);
            }]);
        }

        if (!empty($this->filtros['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $this->filtros['fecha_inicio']);
        }

        if (!empty($this->filtros['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $this->filtros['fecha_fin']);
        }

        if (!empty($this->filtros['semillero_id'])) {
            $query->where('semillero_id', $this->filtros['semillero_id']);
        }

        if (!empty($this->filtros['estado'])) {
            $query->where('estado', $this->filtros['estado']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Código',
            'Título',
            'Autor',
            'Email Autor',
            'Semillero',
            'Programa/Línea Temática',
            'Estado',
            'Fecha Creación',
            'Fecha Actualización',
        ];
    }

    public function map($proyecto): array
    {
        return [
            $proyecto->id,
            $proyecto->codigo,
            $proyecto->titulo,
            $proyecto->autor->name ?? 'N/A',
            $proyecto->autor->email ?? 'N/A',
            $proyecto->semillero->nombre ?? 'N/A',
            $proyecto->semillero->linea_tematica ?? 'Sin programa',
            $proyecto->estado,
            $proyecto->created_at->format('d/m/Y H:i:s'),
            $proyecto->updated_at->format('d/m/Y H:i:s'),
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
        return 'Proyectos';
    }
}
