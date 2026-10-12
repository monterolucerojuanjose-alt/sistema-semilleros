<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODELO: INSCRIPCIÓN DE UN PROYECTO A UN EVENTO
 * ============================================================================
 *
 * Postulación de un proyecto para presentarse en un evento, ya sea como
 * ponencia o como póster.
 *
 * Conviene no confundirlo con EventoParticipante: aquel registra a las
 * personas que asisten a un evento, mientras que este registra los trabajos
 * que se presentan en él.
 *
 * Estados por los que pasa una inscripción:
 *
 *   Inscrito  →  En revisión  →  Aceptado
 *                             ↘  Rechazado
 *
 * La mayoría de los filtros de este modelo comprueban si el valor recibido
 * está vacío y, en ese caso, devuelven la consulta intacta. Eso permite
 * encadenarlos sin condicionales en el controlador: los filtros que el
 * usuario dejó en blanco simplemente no hacen nada.
 *
 * Tabla asociada: inscripciones_evento
 *
 * @property int    $id
 * @property int    $proyecto_id
 * @property int    $evento_id
 * @property string $tipo                Modalidad: «ponencia» o «poster».
 * @property string $estado              Situación de la postulación.
 * @property string $observaciones       Comentarios del comité evaluador.
 * @property string $codigo_inscripcion  Código único, formato INS-AAAA-NNNN.
 *
 * @property-read string $estado_texto        Estado con formato de presentación.
 * @property-read string $tipo_texto          Modalidad con formato de presentación.
 * @property-read string $estado_badge_class  Clases de estilo para la etiqueta.
 */
class InscripcionEvento extends Model
{
    use HasFactory;

    /**
     * Nombre real de la tabla.
     *
     * @var string
     */
    protected $table = 'inscripciones_evento';

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'evento_id',
        'tipo',
        'estado',
        'observaciones',
        'codigo_inscripcion',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Proyecto que se postula.
     *
     * @return BelongsTo Relación de pertenencia con Proyecto.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /**
     * Evento al que se postula.
     *
     * @return BelongsTo Relación de pertenencia con Evento.
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    /**
     * Filtro reutilizable: inscripciones en un estado concreto.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string|null  $estado  Estado a filtrar; si viene vacío no filtra.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorEstado($query, $estado)
    {
        if ($estado && $estado !== '') {
            return $query->where('estado', $estado);
        }
        return $query;
    }

    /**
     * Filtro reutilizable: inscripciones de una modalidad.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string|null  $tipo  Modalidad a filtrar; si viene vacía no filtra.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorTipo($query, $tipo)
    {
        if ($tipo && $tipo !== '') {
            return $query->where('tipo', $tipo);
        }
        return $query;
    }

    /**
     * Filtro reutilizable: inscripciones que siguen vigentes.
     *
     * Se enumeran los estados válidos en lugar de excluir «Cancelado». La
     * diferencia importa: si mañana se añade un estado nuevo, con esta forma
     * queda fuera hasta que se decida incluirlo, en vez de colarse sin que
     * nadie lo revise.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeActivas($query)
    {
        return $query->whereIn('estado', ['Inscrito', 'En revisión', 'Aceptado', 'Rechazado']);
    }

    /**
     * Filtro reutilizable: inscripciones dentro de un rango de fechas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  mixed  $fechaInicio  Fecha desde la que buscar; opcional.
     * @param  mixed  $fechaFin     Fecha hasta la que buscar; opcional.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorFecha($query, $fechaInicio, $fechaFin)
    {
        // Los dos extremos son independientes: se puede filtrar solo «desde»,
        // solo «hasta», o ambos.
        if ($fechaInicio) {
            $query->whereDate('created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->whereDate('created_at', '<=', $fechaFin);
        }
        return $query;
    }

    /**
     * Filtro reutilizable: inscripciones de proyectos de un semillero.
     *
     * El semillero no es un campo de esta tabla, sino del proyecto. Por eso
     * se usa «whereHas», que filtra por una condición sobre la tabla
     * relacionada sin necesidad de traer los proyectos completos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int|null  $semilleroId  Semillero a filtrar; si viene vacío no filtra.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorSemillero($query, $semilleroId)
    {
        if ($semilleroId && $semilleroId !== '') {
            return $query->whereHas('proyecto', function ($q) use ($semilleroId) {
                $q->where('semillero_id', $semilleroId);
            });
        }
        return $query;
    }

    /**
     * Filtro reutilizable: búsqueda por texto libre.
     *
     * Busca el término en el código de inscripción, y también en el título y
     * el código del proyecto y en el nombre y correo de su autor. Así el
     * buscador funciona igual escribiendo «INS-2026-0042», «robótica» o
     * «María», que es como la gente busca de verdad.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string|null  $termino  Texto a buscar; si viene vacío no filtra.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeBuscar($query, $termino)
    {
        if ($termino && $termino !== '') {
            // Todas las condiciones se agrupan dentro de una función. Ese
            // paréntesis es imprescindible: sin él, los «or» se mezclarían
            // con los demás filtros de la consulta y una búsqueda anularía
            // el filtro de estado o el de semillero ya aplicados.
            return $query->where(function ($q) use ($termino) {
                $q->where('codigo_inscripcion', 'like', "%{$termino}%")
                  ->orWhereHas('proyecto', function ($subQuery) use ($termino) {
                      $subQuery->where('titulo', 'like', "%{$termino}%")
                               ->orWhere('codigo', 'like', "%{$termino}%")
                               ->orWhereHas('autor', function ($authorQuery) use ($termino) {
                                   $authorQuery->where('name', 'like', "%{$termino}%")
                                              ->orWhere('email', 'like', "%{$termino}%");
                               });
                  });
            });
        }
        return $query;
    }

    /**
     * Filtro reutilizable: inscripciones de los proyectos de un usuario.
     *
     * Es el filtro que usa la pantalla «Mis inscripciones» para que cada
     * estudiante vea únicamente las suyas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int|null  $userId  Identificador del autor.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorUsuario($query, $userId)
    {
        if ($userId) {
            return $query->whereHas('proyecto', function ($q) use ($userId) {
                $q->where('autor_id', $userId);
            });
        }
        return $query;
    }

    /**
     * Genera un código de inscripción único.
     *
     * @return string Código con el formato INS-AAAA-NNNN.
     */
    public static function generarCodigo(): string
    {
        // El número es aleatorio, no consecutivo, así que puede repetirse.
        // El bucle comprueba contra la base de datos y vuelve a intentarlo
        // hasta dar con uno libre. Con 9999 combinaciones por año, las
        // repeticiones son frecuentes cuando hay muchas inscripciones, y sin
        // esta comprobación el guardado fallaría por clave duplicada.
        do {
            $codigo = 'INS-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('codigo_inscripcion', $codigo)->exists());

        return $codigo;
    }

    /**
     * Estado con el formato con el que se muestra en pantalla.
     *
     * Se accede como propiedad: $inscripcion->estado_texto
     *
     * @return string Estado formateado.
     */
    public function getEstadoTextoAttribute(): string
    {
        return match($this->estado) {
            'Inscrito' => 'Inscrito',
            'En revisión' => 'En Revisión',
            'Aceptado' => 'Aceptado',
            'Rechazado' => 'Rechazado',
            // Si apareciera un estado no contemplado se muestra tal cual, en
            // lugar de dejar el hueco vacío en la pantalla.
            default => $this->estado,
        };
    }

    /**
     * Modalidad con el formato con el que se muestra en pantalla.
     *
     * Se accede como propiedad: $inscripcion->tipo_texto
     *
     * Traduce el valor guardado en la base («poster») al que lee la persona
     * («Póster»), con su mayúscula y su tilde.
     *
     * @return string Modalidad formateada.
     */
    public function getTipoTextoAttribute(): string
    {
        return match($this->tipo) {
            'ponencia' => 'Ponencia',
            'poster' => 'Póster',
            default => $this->tipo,
        };
    }

    /**
     * Clases de estilo para la etiqueta de color del estado.
     *
     * Se accede como propiedad: $inscripcion->estado_badge_class
     *
     * La correspondencia entre estado y color vive aquí, en un solo sitio,
     * para que el listado, el detalle y los informes coloreen igual.
     *
     * @return string Clases de Tailwind para la etiqueta.
     */
    public function getEstadoBadgeClassAttribute(): string
    {
        return match($this->estado) {
            'Inscrito' => 'bg-blue-100 text-blue-800',
            'En revisión' => 'bg-yellow-100 text-yellow-800',
            'Aceptado' => 'bg-green-100 text-green-800',
            'Rechazado' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
