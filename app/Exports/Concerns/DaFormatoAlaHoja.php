<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Formato común de todas las exportaciones a Excel del sistema.
 *
 * Se centraliza aquí para que las hojas tengan el mismo aspecto y para no
 * repetir el estilo en cada exportación, que es como acaban desincronizándose.
 *
 * La clase que lo use debe declarar `WithEvents` y `ShouldAutoSize`.
 */
trait DaFormatoAlaHoja
{
    /** Color corporativo de la cabecera. */
    protected string $colorCabecera = 'FF1D4ED8';

    /** Límites de ancho de columna, en caracteres. */
    private const ANCHO_MINIMO = 11;
    private const ANCHO_MAXIMO = 48;

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento) {
                $hoja = $evento->sheet->getDelegate();
                $ultimaColumna = $hoja->getHighestColumn();
                $ultimaFila = $hoja->getHighestRow();

                // Cabecera: texto blanco en negrita sobre fondo azul, centrada.
                $hoja->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => $this->colorCabecera],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $hoja->getRowDimension(1)->setRowHeight(24);

                if ($ultimaFila < 2) {
                    return;
                }

                // Bordes suaves y alineación superior en los datos.
                $hoja->getStyle("A1:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFD1D5DB'],
                        ],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
                ]);

                // Filas alternas para facilitar la lectura.
                for ($fila = 2; $fila <= $ultimaFila; $fila++) {
                    if ($fila % 2 === 0) {
                        $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFF8FAFC'],
                            ],
                        ]);
                    }
                }

                // La cabecera queda visible al desplazarse y permite ordenar y filtrar.
                $hoja->freezePane('A2');
                $hoja->setAutoFilter("A1:{$ultimaColumna}{$ultimaFila}");

                // El ajuste automático se calcula al guardar y puede dar columnas
                // inmanejables (un campo con varios títulos concatenados llega a
                // superar los 170 caracteres). Se calcula aquí y se acota.
                $hoja->calculateColumnWidths();

                foreach ($hoja->getColumnDimensions() as $dimension) {
                    if (! $dimension->getAutoSize()) {
                        continue;
                    }

                    $ancho = $dimension->getWidth();

                    if ($ancho > self::ANCHO_MAXIMO) {
                        $dimension->setAutoSize(false);
                        $dimension->setWidth(self::ANCHO_MAXIMO);
                    } elseif ($ancho > 0 && $ancho < self::ANCHO_MINIMO) {
                        $dimension->setAutoSize(false);
                        $dimension->setWidth(self::ANCHO_MINIMO);
                    }
                }
            },
        ];
    }
}
