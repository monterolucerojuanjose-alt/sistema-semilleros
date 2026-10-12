<?php

namespace App\Exports;

use App\Models\Semillero;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SemillerosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    use \App\Exports\Concerns\DaFormatoAlaHoja;

    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        return Semillero::consulta($this->filtros)->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Línea Temática',
            'Tutor',
            'Coordinador',
            'Estado',
            'Integrantes',
            'Solicitudes Pendientes',
            'Proyectos',
            'Fecha de Creación',
        ];
    }

    public function map($semillero): array
    {
        return [
            $semillero->id,
            $semillero->nombre,
            $semillero->linea_tematica ?? 'Sin programa',
            $semillero->tutor->name ?? 'Sin asignar',
            $semillero->coordinador->name ?? 'Sin asignar',
            $semillero->estado,
            $semillero->miembros_aprobados_count,
            $semillero->solicitudes_pendientes_count,
            $semillero->proyectos_count,
            $semillero->created_at->format('d/m/Y H:i:s'),
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
        return 'Semilleros';
    }
}
