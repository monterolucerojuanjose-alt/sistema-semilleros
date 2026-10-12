<?php

namespace App\Exports;

use App\Models\Auditoria;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Usa anchos de columna explícitos en lugar de ajuste automático: el detalle y
 * el user agent son muy largos y el autoajuste produciría columnas enormes.
 */
class AuditoriaExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithColumnWidths, WithTitle
{
    use \App\Exports\Concerns\DaFormatoAlaHoja;

    protected $filtros;

    public function __construct(array $filtros)
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = Auditoria::with('usuario:id,name,email');

        // Aplicar filtros
        if (isset($this->filtros['usuario_id']) && $this->filtros['usuario_id']) {
            $query->where('usuario_id', $this->filtros['usuario_id']);
        }

        if (isset($this->filtros['accion']) && $this->filtros['accion']) {
            $query->where('accion', $this->filtros['accion']);
        }

        if (isset($this->filtros['entidad']) && $this->filtros['entidad']) {
            $query->where('entidad_afectada', $this->filtros['entidad']);
        }

        if (isset($this->filtros['fecha_inicio']) && $this->filtros['fecha_inicio']) {
            $query->whereDate('created_at', '>=', $this->filtros['fecha_inicio']);
        }

        if (isset($this->filtros['fecha_fin']) && $this->filtros['fecha_fin']) {
            $query->whereDate('created_at', '<=', $this->filtros['fecha_fin']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Usuario',
            'Email',
            'Acción',
            'Entidad Afectada',
            'ID Entidad',
            'Detalle',
            'IP Address',
            'User Agent',
            'Fecha y Hora',
        ];
    }

    public function map($auditoria): array
    {
        return [
            $auditoria->id,
            $auditoria->usuario->name ?? 'N/A',
            $auditoria->usuario->email ?? 'N/A',
            $auditoria->accion,
            $auditoria->entidad_afectada,
            $auditoria->entidad_id,
            $auditoria->detalle,
            $auditoria->ip_address,
            $auditoria->user_agent,
            $auditoria->created_at->format('d/m/Y H:i:s'),
        ];
    }

    public function title(): string
    {
        return 'Auditoría';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 20,  // Usuario
            'C' => 25,  // Email
            'D' => 15,  // Acción
            'E' => 20,  // Entidad Afectada
            'F' => 12,  // ID Entidad
            'G' => 40,  // Detalle
            'H' => 15,  // IP Address
            'I' => 30,  // User Agent
            'J' => 20,  // Fecha y Hora
        ];
    }
}