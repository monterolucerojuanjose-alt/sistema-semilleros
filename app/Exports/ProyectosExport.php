<?php

namespace App\Exports;

use App\Support\Etiquetas;

class ProyectosExport extends ReporteExport
{
    public function headings(): array
    {
        return [
            'N.º',
            'Código',
            'Título',
            'Autor',
            'Correo del autor',
            'Semillero',
            'Línea temática',
            'Estado',
            'Fecha de creación',
            'Última actualización',
        ];
    }

    public function map($proyecto): array
    {
        return [
            $this->siguienteNumero(),
            $proyecto->codigo ?: 'Sin código',
            $proyecto->titulo,
            $proyecto->autor->name ?? 'Sin autor',
            $proyecto->autor->email ?? '—',
            $proyecto->semillero->nombre ?? 'Sin semillero',
            $proyecto->semillero->linea_tematica ?? 'Sin línea temática',
            Etiquetas::estado($proyecto->estado),
            optional($proyecto->created_at)->format('d/m/Y'),
            optional($proyecto->updated_at)->format('d/m/Y'),
        ];
    }

    public function title(): string
    {
        return 'Proyectos';
    }
}
