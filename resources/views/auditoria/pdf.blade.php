<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Auditoría</title>
    <style>
        /* DejaVu Sans viene incluida con DomPDF y contiene acentos y el símbolo →
           que aparece en los detalles de cambios; Helvetica los imprime como "?". */
        @page { margin: 26mm 12mm 20mm 12mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            font-size: 8.5px;
            color: #1f2937;
        }

        header {
            position: fixed;
            top: -18mm; left: 0; right: 0;
            height: 16mm;
            border-bottom: 1.5pt solid #4f46e5;
        }
        header .titulo { font-size: 15px; font-weight: bold; color: #4f46e5; }
        header .institucion { font-size: 8.5px; color: #6b7280; margin-top: 2px; }
        header .generado {
            position: absolute;
            top: 2mm; right: 0;
            text-align: right;
            font-size: 8px;
            color: #6b7280;
        }

        footer {
            position: fixed;
            bottom: -14mm; left: 0; right: 0;
            height: 10mm;
            border-top: 0.5pt solid #e5e7eb;
            padding-top: 3px;
            font-size: 8px;
            color: #9ca3af;
        }
        /* La numeración "Página N de M" la dibuja NumeracionPdf sobre el lienzo:
           DomPDF no resuelve counter(pages) desde CSS. */

        /* DomPDF no soporta CSS grid; el resumen se maqueta con una tabla. */
        .resumen {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .resumen td {
            width: 25%;
            background-color: #f8fafc;
            border: 0.5pt solid #e2e8f0;
            padding: 7px;
            text-align: center;
        }
        .resumen .etiqueta { font-size: 7px; color: #6b7280; text-transform: uppercase; }
        .resumen .valor { font-size: 14px; font-weight: bold; color: #4f46e5; margin-top: 2px; }

        .filtros {
            background-color: #f8fafc;
            border: 0.5pt solid #e2e8f0;
            border-left: 2.5pt solid #4f46e5;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 8px;
        }
        .filtros strong { color: #374151; }

        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.datos thead { display: table-header-group; }
        table.datos th {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: bold;
            font-size: 8px;
            text-align: left;
            padding: 5px 6px;
            border: 0.5pt solid #4f46e5;
        }
        table.datos td {
            padding: 4px 6px;
            font-size: 7.5px;
            border: 0.5pt solid #e5e7eb;
            vertical-align: top;
        }
        table.datos tbody tr:nth-child(even) td { background-color: #f8fafc; }
        .usuario-nombre { font-weight: bold; }
        .usuario-correo { color: #6b7280; font-size: 7px; }
        .ip { font-size: 7px; color: #4b5563; }

        /* Sin display:inline-block: DomPDF estira la celda a toda la altura de
           la página cuando se usa dentro de una tabla. */
        .accion {
            padding: 2px 5px;
            border-radius: 8px;
            font-size: 7px;
            font-weight: bold;
            background-color: #f1f5f9;
            color: #475569;
        }
        .accion-create        { background-color: #dcfce7; color: #166534; }
        .accion-update        { background-color: #dbeafe; color: #1e40af; }
        .accion-delete,
        .accion-force-delete  { background-color: #fee2e2; color: #991b1b; }
        .accion-login         { background-color: #e0e7ff; color: #3730a3; }
        .accion-logout        { background-color: #f1f5f9; color: #475569; }
        .accion-login-fallido,
        .accion-bloqueo       { background-color: #fef3c7; color: #92400e; }
        .accion-cleanup       { background-color: #ede9fe; color: #5b21b6; }

        .sin-datos {
            text-align: center;
            padding: 25px;
            color: #9ca3af;
            font-size: 10px;
            border: 0.5pt dashed #d1d5db;
        }
    </style>
</head>
<body>
    @php
        // Traducción de las acciones registradas, igual que en la pantalla.
        $etiquetas = [
            'create' => 'Creación',
            'update' => 'Modificación',
            'delete' => 'Eliminación',
            'restore' => 'Restauración',
            'force_delete' => 'Eliminación definitiva',
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'login_fallido' => 'Intento fallido',
            'bloqueo' => 'Bloqueo',
            'cleanup' => 'Depuración',
            'download' => 'Descarga',
            'export' => 'Exportación',
            'approve' => 'Aprobación',
            'reject' => 'Rechazo',
        ];

        $nombresFiltro = [
            'usuario_id' => 'Usuario',
            'accion' => 'Acción',
            'entidad' => 'Entidad',
            'fecha_inicio' => 'Desde',
            'fecha_fin' => 'Hasta',
        ];

        $filtrosAplicados = [];
        foreach ($filtros as $clave => $valor) {
            if (blank($valor)) continue;
            $texto = $valor;
            if ($clave === 'usuario_id') {
                $texto = optional(\App\Models\User::find($valor))->name ?? ('#'.$valor);
            } elseif ($clave === 'accion') {
                $texto = $etiquetas[$valor] ?? $valor;
            } elseif (in_array($clave, ['fecha_inicio', 'fecha_fin'])) {
                $texto = \Carbon\Carbon::parse($valor)->format('d/m/Y');
            }
            $filtrosAplicados[$nombresFiltro[$clave] ?? $clave] = $texto;
        }
    @endphp

    <header>
        <div class="titulo">Registros de Auditoría</div>
        <div class="institucion">Universidad Mariana · Sistema de Gestión de Semilleros de Investigación</div>
        <div class="generado">
            Generado el {{ $fecha_generacion }}<br>
            {{ $auditorias->count() }} {{ $auditorias->count() === 1 ? 'registro' : 'registros' }}
        </div>
    </header>

    <footer>
        Universidad Mariana · Sistema de Gestión de Semilleros
    </footer>

    <table class="resumen">
        <tr>
            <td>
                <div class="etiqueta">Total de registros</div>
                <div class="valor">{{ $auditorias->count() }}</div>
            </td>
            <td>
                <div class="etiqueta">Últimos 7 días</div>
                <div class="valor">{{ $auditorias->where('created_at', '>=', now()->subDays(7))->count() }}</div>
            </td>
            <td>
                <div class="etiqueta">Usuarios con actividad</div>
                <div class="valor">{{ $auditorias->pluck('usuario_id')->filter()->unique()->count() }}</div>
            </td>
            <td>
                <div class="etiqueta">Entidades afectadas</div>
                <div class="valor">{{ $auditorias->pluck('entidad_afectada')->unique()->count() }}</div>
            </td>
        </tr>
    </table>

    <div class="filtros">
        <strong>Filtros aplicados:</strong>
        @if(count($filtrosAplicados) > 0)
            @foreach($filtrosAplicados as $etiqueta => $valor){{ $etiqueta }}: {{ $valor }}@if(!$loop->last) &nbsp;·&nbsp; @endif @endforeach
        @else
            ninguno (se incluyen todos los registros)
        @endif
    </div>

    @if($auditorias->isEmpty())
        <div class="sin-datos">No se encontraron registros de auditoría con los filtros aplicados.</div>
    @else
        <table class="datos">
            <thead>
                <tr>
                    <th style="width: 13%;">Fecha y hora</th>
                    <th style="width: 19%;">Usuario</th>
                    <th style="width: 12%;">Acción</th>
                    <th style="width: 14%;">Entidad</th>
                    <th style="width: 30%;">Detalle</th>
                    <th style="width: 12%;">Origen</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($auditorias as $auditoria)
                    <tr>
                        <td>{{ $auditoria->created_at->format('d/m/Y') }}<br>
                            <span class="ip">{{ $auditoria->created_at->format('H:i:s') }}</span>
                        </td>
                        <td>
                            <span class="usuario-nombre">{{ $auditoria->usuario->name ?? 'No identificado' }}</span><br>
                            <span class="usuario-correo">{{ $auditoria->usuario->email ?? 'Sin cuenta asociada' }}</span>
                        </td>
                        <td>
                            <span class="accion accion-{{ \Illuminate\Support\Str::slug($auditoria->accion) }}">
                                {{ $etiquetas[$auditoria->accion] ?? $auditoria->accion }}
                            </span>
                        </td>
                        <td>
                            {{ $auditoria->entidad_afectada }}@if($auditoria->entidad_id) <span class="ip">#{{ $auditoria->entidad_id }}</span>@endif
                        </td>
                        <td>{{ \Illuminate\Support\Str::limit($auditoria->detalle ?? 'Sin detalles', 110) }}</td>
                        <td class="ip">{{ $auditoria->ip_address ?? 'N/D' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
