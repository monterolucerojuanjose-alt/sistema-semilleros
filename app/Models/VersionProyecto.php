<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODELO: VERSIÓN DE UN PROYECTO
 * ============================================================================
 *
 * Cada entrega de documento que se sube a un proyecto, conservando también
 * las anteriores.
 *
 * El sistema no reemplaza el archivo al subir uno nuevo: añade una versión.
 * Así queda registrado cómo evolucionó el trabajo, quién subió cada entrega
 * y con qué comentario, que es lo que permite a un tutor revisar el avance
 * y resolver discusiones sobre qué se entregó y cuándo.
 *
 * Tabla asociada: versiones_proyecto
 *
 * @property int    $id
 * @property int    $proyecto_id  Proyecto al que pertenece la versión.
 * @property string $archivo      Ruta del documento dentro de storage/app.
 * @property string $comentario   Nota que describe qué cambió en esta entrega.
 * @property int    $creado_por   Usuario que subió el archivo.
 *
 * @property-read string $nombre_archivo   Nombre del archivo sin la ruta.
 * @property-read string $extension        Extensión del documento.
 * @property-read string $tamaño_archivo   Peso en formato legible.
 */
class VersionProyecto extends Model
{
    /**
     * Nombre real de la tabla. En singular el nombre del modelo es
     * «VersionProyecto», y Laravel buscaría «version_proyectos».
     *
     * @var string
     */
    protected $table = 'versiones_proyecto';

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'archivo',
        'comentario',
        'creado_por'
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * Las fechas de creación importan más aquí que en otros modelos: son las
     * que ordenan el historial de entregas y las que acreditan si un trabajo
     * se subió dentro del plazo.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Proyecto al que pertenece esta versión.
     *
     * @return BelongsTo Relación de pertenencia con Proyecto.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /**
     * Usuario que subió esta versión.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Nombre del archivo sin la ruta de carpetas.
     *
     * Se accede como propiedad: $version->nombre_archivo
     *
     * En la base de datos se guarda la ruta completa («proyectos/11/tesis.pdf»),
     * pero en pantalla solo interesa el nombre («tesis.pdf»).
     *
     * @return string Nombre del archivo.
     */
    public function getNombreArchivoAttribute(): string
    {
        return basename($this->archivo);
    }

    /**
     * Extensión del documento.
     *
     * Se accede como propiedad: $version->extension
     *
     * La pantalla la usa para mostrar el icono correspondiente al tipo de
     * archivo (PDF, Word, etcétera).
     *
     * @return string Extensión sin el punto, por ejemplo «pdf».
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->archivo, PATHINFO_EXTENSION);
    }

    /**
     * Peso del archivo en un formato legible para una persona.
     *
     * Se accede como propiedad: $version->tamaño_archivo
     *
     * Convierte los bytes a la unidad más adecuada, de modo que en pantalla
     * se lea «2.45 MB» y no «2568192».
     *
     * @return string Peso con su unidad, o «N/A» si el archivo ya no existe.
     */
    public function getTamañoArchivoAttribute(): string
    {
        // Se comprueba que el archivo siga en el disco. Un registro puede
        // sobrevivir a su archivo (por una restauración parcial, por ejemplo),
        // y filesize() sobre un archivo inexistente genera un aviso de PHP.
        if (file_exists(storage_path('app/' . $this->archivo))) {
            $bytes = filesize(storage_path('app/' . $this->archivo));
            $units = ['B', 'KB', 'MB', 'GB'];

            // Se divide entre 1024 tantas veces como haga falta hasta dar con
            // la unidad adecuada. La condición «$i < count($units) - 1» impide
            // pasarse del último elemento del arreglo con archivos enormes.
            for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                $bytes /= 1024;
            }

            return round($bytes, 2) . ' ' . $units[$i];
        }

        return 'N/A';
    }
}
