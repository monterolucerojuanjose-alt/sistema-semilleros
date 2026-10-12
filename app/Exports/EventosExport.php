<?php

namespace App\Exports;

use App\Models\Evento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EventosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    use \App\Exports\Concerns\DaFormatoAlaHoja;

    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        return Evento::consulta($this->filtros)->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Título',
            'Fecha de Inicio',
            'Fecha de Fin',
            'Ubicación',
            'Estado',
            'Cupo Máximo',
            'Participantes',
            'Proyectos Inscritos',
            'Creado Por',
        ];
    }

    public function map($evento): array
    {
        return [
            $evento->id,
            $evento->titulo,
            optional($evento->fecha_inicio)->format('d/m/Y H:i'),
            optional($evento->fecha_fin)->format('d/m/Y H:i'),
            $evento->ubicacion ?? 'No especificada',
            $evento->estado,
            $evento->cupo_maximo ?? 'Sin límite',
            $evento->participantes_count,
            $evento->inscripciones_count,
            $evento->creador->name ?? 'N/A',
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
        return 'Eventos';
    }
}
