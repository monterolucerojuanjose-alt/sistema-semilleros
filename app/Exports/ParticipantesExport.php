<?php

namespace App\Exports;

use App\Support\Etiquetas;

class ParticipantesExport extends ReporteExport
{
    public function headings(): array
    {
        return [
            'N.º',
            'Nombre',
            'Correo electrónico',
            'Estado',
            'Semilleros',
            'Líneas temáticas',
            'Proyectos',
            'Fecha de registro',
        ];
    }

    public function map($participante): array
    {
        $proyectos = $participante->proyectos ?? collect();

        return [
            $this->siguienteNumero(),
            $participante->name,
            $participante->email,
            Etiquetas::estado($participante->estado ?? 'activo'),
            $proyectos->pluck('semillero.nombre')->filter()->unique()->implode(', ') ?: 'Sin semillero',
            $proyectos->pluck('semillero.linea_tematica')->filter()->unique()->implode(', ') ?: 'Sin línea temática',
            $proyectos->pluck('titulo')->filter()->implode(', ') ?: 'Sin proyectos',
            optional($participante->created_at)->format('d/m/Y'),
        ];
    }

    public function title(): string
    {
        return 'Participantes';
    }
}
