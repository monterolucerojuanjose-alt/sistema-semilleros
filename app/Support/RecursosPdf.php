<?php

namespace App\Support;

/**
 * Recursos gráficos para los documentos PDF (reportes y certificados).
 *
 * El logo institucional está en WebP, que DomPDF no dibuja de forma fiable.
 * Se convierte una vez a PNG y se entrega como data URI, así el PDF no
 * depende de rutas del disco ni de que DomPDF pueda leer archivos remotos.
 */
class RecursosPdf
{
    private const LOGO_WEBP = 'images/5c2a2a6b4c875f31275aa66759e3307f.webp';

    private static ?string $logo = null;

    /** Logo institucional como data URI PNG, o null si no se puede leer. */
    public static function logo(): ?string
    {
        if (self::$logo !== null) {
            return self::$logo ?: null;
        }

        $origen = public_path(self::LOGO_WEBP);
        $cache = storage_path('app/pdf-logo.png');

        if (!is_file($cache) || filemtime($cache) < @filemtime($origen)) {
            if (!is_file($origen) || !function_exists('imagecreatefromwebp')) {
                return (self::$logo = '') ?: null;
            }

            $imagen = @imagecreatefromwebp($origen);
            if (!$imagen) {
                return (self::$logo = '') ?: null;
            }

            imagesavealpha($imagen, true);
            imagepng($imagen, $cache);
            imagedestroy($imagen);
        }

        return self::$logo = 'data:image/png;base64,' . base64_encode(file_get_contents($cache));
    }
}
