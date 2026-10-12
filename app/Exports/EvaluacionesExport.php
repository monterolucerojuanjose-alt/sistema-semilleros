<?php

namespace App\Exports;

use App\Models\Evaluacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EvaluacionesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    use \App\Exports\Concerns\DaFormatoAlaHoja;

    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    /**
     * Reúne las evaluaciones de las dos vías del sistema: las de rúbrica y las
     * que los jurados registran sobre proyectos de eventos. Antes sólo incluía
     * las primeras, de modo que el reporte no reflejaba lo ocurrido en eventos.
     */
    public function collection()
    {
        return $this->deRubrica()->concat($this->deEventos())->sortByDesc('fecha')->values();
    }

    private function deRubrica()
    {
        $query = Evaluacion::with(['proyecto.autor', 'proyecto.semillero', 'jurado', 'rubrica']);

        if (!empty($this->filtros['evento_id'])) {
            $query->whereHas('proyecto.inscripciones', fn ($q) => $q->where('evento_id', $this->filtros['evento_id']));
        }

        if (!empty($this->filtros['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $this->filtros['fecha_inicio']);
        }

        if (!empty($this->filtros['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $this->filtros['fecha_fin']);
        }

        if (!empty($this->filtros['semillero_id'])) {
            $query->whereHas('proyecto', fn ($q) => $q->where('semillero_id', $this->filtros['semillero_id']));
        }

        if (!empty($this->filtros['estado'])) {
            $query->where('estado', $this->filtros['estado']);
        }

        return $query->get()->map(fn ($e) => [
            'proyecto' => $e->proyecto->titulo ?? 'N/A',
            'autor' => $e->proyecto->autor->name ?? 'N/A',
            'semillero' => $e->proyecto->semillero->nombre ?? 'N/A',
            'programa' => $e->proyecto->semillero->linea_tematica ?? 'Sin programa',
            'modalidad' => '',
            'jurado' => $e->jurado->name ?? 'N/A',
            'origen' => $e->rubrica->nombre ?? 'Rúbrica',
            'nota_final' => $e->nota_final,
            'estado' => $e->estado,
            'observaciones' => $e->observaciones ?? '',
            'fecha' => $e->created_at,
        ]);
    }

    private function deEventos()
    {
        // Estas propuestas no cuelgan de un semillero del catálogo.
        if (!empty($this->filtros['semillero_id'])) {
            return collect();
        }

        if (!empty($this->filtros['estado']) && $this->filtros['estado'] !== 'Evaluado') {
            return collect();
        }

        $query = \App\Models\EventoParticipante::with(['evento:id,titulo', 'usuario:id,name', 'evaluador:id,name'])
            ->where('estado_evaluacion', 'evaluado')
            ->whereNotNull('nota_final');

        if (!empty($this->filtros['evento_id'])) {
            $query->where('evento_id', $this->filtros['evento_id']);
        }

        if (!empty($this->filtros['fecha_inicio'])) {
            $query->whereDate('fecha_evaluacion', '>=', $this->filtros['fecha_inicio']);
        }

        if (!empty($this->filtros['fecha_fin'])) {
            $query->whereDate('fecha_evaluacion', '<=', $this->filtros['fecha_fin']);
        }

        return $query->get()->map(fn ($p) => [
            'proyecto' => $p->titulo_proyecto ?: 'Proyecto sin título',
            'autor' => $p->usuario->name ?? 'N/A',
            'semillero' => 'Propuesta de evento',
            'programa' => $p->programa_reporte ?: 'Sin programa',
            'modalidad' => $p->modalidad ?: '',
            'jurado' => $p->evaluador->name ?? 'Sin asignar',
            'origen' => 'Evento: ' . ($p->evento->titulo ?? 'N/A'),
            'nota_final' => $p->nota_final,
            'estado' => 'Evaluado',
            'observaciones' => $p->analisis_proyecto ?? '',
            'fecha' => $p->fecha_evaluacion ?? $p->updated_at,
        ]);
    }

    public function headings(): array
    {
        return [
            'Proyecto',
            'Autor',
            'Semillero / Origen',
            'Programa',
            'Modalidad',
            'Jurado',
            'Rúbrica / Evento',
            'Nota Final (0-100)',
            'Estado',
            'Observaciones',
            'Fecha Evaluación',
        ];
    }

    public function map($fila): array
    {
        return [
            $fila['proyecto'],
            $fila['autor'],
            $fila['semillero'],
            $fila['programa'],
            $fila['modalidad'],
            $fila['jurado'],
            $fila['origen'],
            $fila['nota_final'] !== null ? number_format((float) $fila['nota_final'], 2) : 'Pendiente',
            $fila['estado'],
            $fila['observaciones'],
            optional($fila['fecha'])->format('d/m/Y H:i:s') ?? 'N/A',
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
        return 'Evaluaciones';
    }
}
