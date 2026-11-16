<?php

namespace App\Exports;

use App\Support\Etiquetas;

class EventosExport extends ReporteExport
{
    public function headings(): array
    {
        return [
            'N.º',
            'Título',
            'Fecha de inicio',
            'Fecha de fin',
            'Ubicación',
            'Estado',
            'Cupo máximo',
            'Participantes',
            'Proyectos inscritos',
            'Creado por',
        ];
    }

    public function map($evento): array
    {
        return [
            $this->siguienteNumero(),
            $evento->titulo,
            optional($evento->fecha_inicio)->format('d/m/Y H:i'),
            optional($evento->fecha_fin)->format('d/m/Y H:i'),
            $evento->ubicacion ?: 'No especificada',
            Etiquetas::estado($evento->estado),
            $evento->cupo_maximo ?? 'Sin límite',
            (int) $evento->participantes_count,
            (int) $evento->inscripciones_count,
            $evento->creador->name ?? 'Sin registro',
        ];
    }

    public function title(): string
    {
        return 'Eventos';
    }
}
