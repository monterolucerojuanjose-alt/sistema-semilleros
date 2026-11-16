<?php

namespace App\Exports\Concerns;

use App\Support\Etiquetas;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Formato común de todas las exportaciones a Excel del sistema.
 *
 * Deja la hoja como un documento formal y no como un volcado de datos:
 *
 *   Fila 1  Título del reporte
 *   Fila 2  Institución y fecha de generación
 *   Fila 3  Filtros aplicados
 *   Fila 4  (separación)
 *   Fila 5  Cabecera de columnas: fija al desplazarse, con autofiltro
 *   ...     Datos con filas alternas
 *   ...     Total de registros
 *
 * Además queda lista para imprimir: A4 horizontal, ajustada al ancho de la
 * página y repitiendo la cabecera en cada hoja impresa.
 *
 * La clase que lo use debe declarar `WithEvents` y `WithTitle`, y tener la
 * propiedad `$filtros`.
 */
trait DaFormatoAlaHoja
{
    /** Color corporativo de la cabecera. */
    protected string $colorCabecera = 'FF1D4ED8';

    /** Límites de ancho de columna, en caracteres. */
    private int $anchoMinimo = 11;
    private int $anchoMaximo = 48;

    /** Filas que ocupa el bloque de título antes de la cabecera. */
    private int $filasTitulo = 4;

    /** Título que encabeza la hoja. Se puede redefinir en cada exportación. */
    protected function tituloDelReporte(): string
    {
        return 'Reporte de ' . $this->title();
    }

    /**
     * Filtros que se describen bajo el título.
     *
     * @return array<string, string>
     */
    protected function filtrosParaEncabezado(): array
    {
        return Etiquetas::filtrosAplicados($this->filtros ?? []);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento) {
                $this->darFormato($evento->sheet->getDelegate());
            },
        ];
    }

    private function darFormato(Worksheet $hoja): void
    {
        $ultimaColumna = $hoja->getHighestColumn();
        $totalRegistros = max(0, $hoja->getHighestRow() - 1);

        // Los anchos se calculan con sólo la cabecera y los datos en la hoja.
        // Si se calcularan después de escribir el título, la columna A se
        // ensancharía hasta el largo del título.
        $this->fijarAnchos($hoja);

        $hoja->insertNewRowBefore(1, $this->filasTitulo);
        $filaCabecera = $this->filasTitulo + 1;
        $primeraFilaDatos = $filaCabecera + 1;
        $ultimaFila = $filaCabecera + $totalRegistros;

        $this->escribirTitulo($hoja, $ultimaColumna);

        // Cabecera: texto blanco en negrita sobre fondo azul, centrada.
        $hoja->getStyle("A{$filaCabecera}:{$ultimaColumna}{$filaCabecera}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $this->colorCabecera]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $hoja->getRowDimension($filaCabecera)->setRowHeight(30);

        $this->configurarImpresion($hoja, $filaCabecera);

        if ($totalRegistros === 0) {
            $hoja->setCellValue("A{$primeraFilaDatos}", 'No se encontraron registros con los filtros aplicados.');
            $hoja->mergeCells("A{$primeraFilaDatos}:{$ultimaColumna}{$primeraFilaDatos}");
            $hoja->getStyle("A{$primeraFilaDatos}")->applyFromArray([
                'font' => ['italic' => true, 'color' => ['argb' => 'FF6B7280']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            return;
        }

        // Bordes suaves y alineación superior en los datos. El texto largo
        // baja de renglón dentro de la celda en vez de quedar cortado.
        $hoja->getStyle("A{$filaCabecera}:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']],
            ],
        ]);
        $hoja->getStyle("A{$primeraFilaDatos}:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);

        // Filas alternas para facilitar la lectura.
        for ($fila = $primeraFilaDatos; $fila <= $ultimaFila; $fila++) {
            if (($fila - $primeraFilaDatos) % 2 === 1) {
                $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
                ]);
            }
        }

        // La cabecera queda visible al desplazarse y permite ordenar y filtrar.
        $hoja->freezePane("A{$primeraFilaDatos}");
        $hoja->setAutoFilter("A{$filaCabecera}:{$ultimaColumna}{$ultimaFila}");

        // Total al pie.
        $filaTotal = $ultimaFila + 2;
        $hoja->setCellValue("A{$filaTotal}", 'Total de registros: ' . $totalRegistros);
        $hoja->getStyle("A{$filaTotal}")->getFont()->setBold(true)->getColor()->setARGB('FF1F2937');
    }

    private function escribirTitulo(Worksheet $hoja, string $ultimaColumna): void
    {
        $filtros = $this->filtrosParaEncabezado();
        $textoFiltros = $filtros
            ? 'Filtros: ' . collect($filtros)->map(fn ($valor, $etiqueta) => "{$etiqueta}: {$valor}")->implode('  ·  ')
            : 'Sin filtros: incluye todos los registros.';

        $lineas = [
            1 => [$this->tituloDelReporte(), ['bold' => true, 'size' => 16, 'color' => ['argb' => $this->colorCabecera]], 26],
            2 => ['Universidad Mariana · Sistema de Gestión de Semilleros de Investigación · Generado el ' . now()->format('d/m/Y \a \l\a\s H:i'),
                  ['size' => 10, 'color' => ['argb' => 'FF4B5563']], 18],
            3 => [$textoFiltros, ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF6B7280']], 18],
        ];

        foreach ($lineas as $fila => [$texto, $fuente, $alto]) {
            $hoja->setCellValue("A{$fila}", $texto);
            $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
            $hoja->getStyle("A{$fila}")->applyFromArray([
                'font' => $fuente,
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $hoja->getRowDimension($fila)->setRowHeight($alto);
        }

        // Línea azul bajo el título, como en el PDF.
        $hoja->getStyle("A3:{$ultimaColumna}3")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB($this->colorCabecera);
    }

    /**
     * Ajusta cada columna a su contenido, dentro de unos límites.
     *
     * El ajuste automático de la librería se recalcula al guardar y puede
     * dar columnas inmanejables (un campo con varios títulos concatenados
     * supera los 170 caracteres), así que se calcula aquí y se deja fijo.
     * Las exportaciones con anchos explícitos (sin ajuste automático) se
     * respetan tal cual.
     */
    private function fijarAnchos(Worksheet $hoja): void
    {
        $hoja->calculateColumnWidths();

        foreach ($hoja->getColumnDimensions() as $dimension) {
            if (! $dimension->getAutoSize()) {
                continue;
            }

            $ancho = $dimension->getWidth();
            $dimension->setAutoSize(false);
            $dimension->setWidth(min($this->anchoMaximo, max($this->anchoMinimo, $ancho + 2)));
        }
    }

    private function configurarImpresion(Worksheet $hoja, int $filaCabecera): void
    {
        $pagina = $hoja->getPageSetup();
        $pagina->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pagina->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pagina->setFitToWidth(1);
        $pagina->setFitToHeight(0);
        $pagina->setRowsToRepeatAtTopByStartAndEnd($filaCabecera, $filaCabecera);

        $hoja->getPageMargins()->setTop(0.5)->setBottom(0.6)->setLeft(0.4)->setRight(0.4);
        $hoja->getHeaderFooter()->setOddFooter('&L&8Universidad Mariana · Sistema de Gestión de Semilleros&R&8Página &P de &N');
        $hoja->setShowGridlines(false);
    }
}
