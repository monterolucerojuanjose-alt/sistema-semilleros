<?php

namespace App\Support;

use Barryvdh\DomPDF\PDF;

/**
 * Escribe "Página N de M" en el pie de cada página.
 *
 * DomPDF no resuelve `counter(pages)` desde CSS (siempre devuelve 0), así que
 * la numeración se dibuja sobre el lienzo una vez renderizado el documento,
 * que es cuando se conoce el número total de páginas.
 */
trait NumeracionPdf
{
    protected function numerarPaginas(PDF $pdf): PDF
    {
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');

        $canvas->page_text(
            $canvas->get_width() - 110,
            $canvas->get_height() - 28,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $fuente,
            8,
            [0.6, 0.6, 0.6]
        );

        return $pdf;
    }
}
