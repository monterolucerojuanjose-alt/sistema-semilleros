<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Evento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParticipantesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    use \App\Exports\Concerns\DaFormatoAlaHoja;

    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        // Si se filtra por evento, obtener participantes del evento
        if (!empty($this->filtros['evento_id'])) {
            $evento = Evento::with([
                'participantes.usuario.proyectos.semillero',
                'participantes.usuario.roles'
            ])->find($this->filtros['evento_id']);

            if (!$evento) {
                return collect([]);
            }

            return $evento->participantes->map(function($participante) {
                return $participante->usuario;
            })->filter()->unique('id')->sortBy('name')->values();
        }

        $query = User::with(['roles', 'proyectos.semillero']);

        if (!empty($this->filtros['semillero_id'])) {
            $query->whereHas('proyectos', function($q) {
                $q->where('semillero_id', $this->filtros['semillero_id']);
            });
        }

        return $query->whereHas('roles', function($q) {
            $q->where('name', 'Estudiante');
        })->orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Email',
            'Estado',
            'Semillero',
            'Programa/Línea Temática',
            'Proyectos',
            'Fecha Registro',
        ];
    }

    public function map($participante): array
    {
        $semilleros = $participante->proyectos->pluck('semillero.nombre')->unique()->implode(', ');
        $proyectos = $participante->proyectos->pluck('titulo')->implode(', ');
        $programas = $participante->proyectos->pluck('semillero.linea_tematica')->filter()->unique()->implode(', ');

        return [
            $participante->id,
            $participante->name,
            $participante->email,
            $participante->estado ?? 'Activo',
            $semilleros ?: 'N/A',
            $programas ?: 'Sin programa',
            $proyectos ?: 'N/A',
            $participante->created_at->format('d/m/Y H:i:s'),
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
        return 'Participantes';
    }
}
