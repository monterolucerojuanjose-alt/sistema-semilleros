<?php

namespace App\Exports;

use App\Support\Etiquetas;

class SemillerosExport extends ReporteExport
{
    public function headings(): array
    {
        return [
            'N.º',
            'Nombre',
            'Línea temática',
            'Tutor',
            'Coordinador',
            'Estado',
            'Integrantes',
            'Solicitudes pendientes',
            'Proyectos',
            'Fecha de creación',
        ];
    }

    public function map($semillero): array
    {
        return [
            $this->siguienteNumero(),
            $semillero->nombre,
            $semillero->linea_tematica ?: 'Sin línea temática',
            $semillero->tutor->name ?? 'Sin asignar',
            $semillero->coordinador->name ?? 'Sin asignar',
            Etiquetas::estado($semillero->estado),
            (int) $semillero->miembros_aprobados_count,
            (int) $semillero->solicitudes_pendientes_count,
            (int) $semillero->proyectos_count,
            optional($semillero->created_at)->format('d/m/Y'),
        ];
    }

    public function title(): string
    {
        return 'Semilleros';
    }
}
