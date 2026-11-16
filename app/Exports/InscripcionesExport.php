<?php

namespace App\Exports;

use App\Support\Etiquetas;

class InscripcionesExport extends ReporteExport
{
    public function headings(): array
    {
        return [
            'N.º',
            'Código de inscripción',
            'Evento',
            'Proyecto',
            'Autor',
            'Semillero',
            'Línea temática',
            'Tipo',
            'Estado',
            'Observaciones',
            'Fecha de inscripción',
        ];
    }

    public function map($inscripcion): array
    {
        return [
            $this->siguienteNumero(),
            $inscripcion->codigo_inscripcion,
            $inscripcion->evento->titulo ?? 'Sin evento',
            $inscripcion->proyecto->titulo ?? 'Proyecto eliminado',
            $inscripcion->proyecto->autor->name ?? 'Sin autor',
            $inscripcion->proyecto->semillero->nombre ?? 'Sin semillero',
            $inscripcion->proyecto->semillero->linea_tematica ?? 'Sin línea temática',
            Etiquetas::tipoInscripcion($inscripcion->tipo),
            Etiquetas::estado($inscripcion->estado),
            $inscripcion->observaciones ?: '—',
            optional($inscripcion->created_at)->format('d/m/Y'),
        ];
    }

    public function title(): string
    {
        return 'Inscripciones';
    }
}
