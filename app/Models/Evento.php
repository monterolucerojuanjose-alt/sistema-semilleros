<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * ============================================================================
 * MODELO: EVENTO ACADÉMICO
 * ============================================================================
 *
 * Congreso, jornada o feria en la que los semilleros presentan sus proyectos.
 *
 * Un evento reúne dos tipos de vínculos distintos:
 *   - Participantes (EventoParticipante): personas inscritas que presentan
 *     una propuesta propia, con su archivo y sus datos.
 *   - Inscripciones (InscripcionEvento): proyectos que ya existen en el
 *     sistema y se postulan al evento.
 *
 * El estado del evento merece atención. Hay un campo «estado» guardado en la
 * base, pero lo que se muestra en pantalla es «estado_dinamico», que combina
 * ese campo con las fechas reales. Así un evento cuya fecha ya pasó aparece
 * como finalizado aunque nadie lo haya marcado a mano.
 *
 * Tabla asociada: eventos
 *
 * @property int    $id
 * @property string $titulo
 * @property string $descripcion
 * @property string $imagen        Ruta de la imagen de portada.
 * @property float  $precio        Costo de inscripción; 0 si es gratuito.
 * @property \Carbon\Carbon $fecha_inicio
 * @property \Carbon\Carbon $fecha_fin
 * @property string $ubicacion
 * @property int    $cupo_maximo   Límite de asistentes; vacío = sin límite.
 * @property string $estado        Estado fijado a mano.
 * @property int    $creado_por
 *
 * @property-read int|null $cupos_disponibles   Plazas libres.
 * @property-read string   $estado_dinamico     Estado real según las fechas.
 * @property-read string   $estado_texto        Estado con formato de presentación.
 * @property-read string   $estado_badge_class  Clases de estilo para la etiqueta.
 */
class Evento extends Model
{
    use HasFactory;

    /**
     * Estados de participación que ocupan una plaza del cupo.
     *
     * Se declara como constante porque la misma lista se usa en tres sitios
     * (contar plazas libres, comprobar si queda sitio y saber si alguien ya
     * está inscrito). Cuando estaba repetida, era fácil corregir una copia y
     * dejar las otras dos con una lista distinta.
     *
     * Nótese que «pendiente» también ocupa plaza: una solicitud en revisión
     * reserva el sitio hasta que se resuelva, para no admitir más gente de
     * la que cabe.
     */
    private const ESTADOS_QUE_OCUPAN_CUPO = ['inscrito', 'asistio', 'pendiente', 'aprobado'];

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'titulo',
        'descripcion',
        'imagen',
        'precio',
        'fecha_inicio',
        'fecha_fin',
        'ubicacion',
        'cupo_maximo',
        'estado',
        'creado_por',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * Convertir las fechas a objetos Carbon es lo que permite compararlas con
     * now() en estado_dinamico y darles formato en español en la pantalla.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'precio' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Usuario que creó el evento.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Personas inscritas al evento con su propuesta.
     *
     * @return HasMany Relación de uno a muchos con EventoParticipante.
     */
    public function participantes(): HasMany
    {
        return $this->hasMany(EventoParticipante::class);
    }

    /**
     * Proyectos ya registrados en el sistema que se postulan a este evento.
     *
     * @return HasMany Relación de uno a muchos con InscripcionEvento.
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionEvento::class);
    }

    /**
     * Usuarios vinculados al evento, en cualquier estado.
     *
     * @return BelongsToMany Relación de muchos a muchos con User.
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'evento_participantes')
                    ->withPivot(['estado', 'observaciones'])
                    ->withTimestamps();
    }

    /**
     * Usuarios con la inscripción confirmada.
     *
     * @return BelongsToMany Relación de muchos a muchos con User.
     */
    public function estudiantesInscritos(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'evento_participantes')
                    ->wherePivot('estado', 'inscrito')
                    ->withPivot(['estado', 'observaciones'])
                    ->withTimestamps();
    }

    /**
     * Filtro reutilizable: eventos marcados como activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Filtro reutilizable: eventos que aún no han comenzado.
     *
     * Se ordenan del más cercano al más lejano, que es el orden útil para la
     * cartelera de próximos eventos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeProximos($query)
    {
        return $query->where('fecha_inicio', '>=', now())
                     ->orderBy('fecha_inicio', 'asc');
    }

    /**
     * Filtro reutilizable: eventos terminados.
     *
     * Un evento cuenta como terminado si se marcó a mano como finalizado o
     * si su fecha de fin ya pasó.
     *
     * Las dos condiciones se agrupan dentro de una función. Sin ese
     * agrupamiento, el «or» se combinaría con los filtros anteriores de la
     * consulta y devolvería también eventos que no debían aparecer.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeFinalizados($query)
    {
        return $query->where(function ($q) {
            $q->where('estado', 'finalizado')
              ->orWhere('fecha_fin', '<', now());
        });
    }

    /**
     * Indica si todavía queda sitio en el evento.
     *
     * @return bool Verdadero si admite más inscripciones.
     */
    public function tieneCuposDisponibles(): bool
    {
        // Sin cupo máximo el evento es de aforo libre.
        if (!$this->cupo_maximo) {
            return true;
        }

        return $this->participantes()
                    ->whereIn('estado', self::ESTADOS_QUE_OCUPAN_CUPO)
                    ->count() < $this->cupo_maximo;
    }

    /**
     * Plazas libres que quedan.
     *
     * Se accede como propiedad: $evento->cupos_disponibles
     *
     * @return int|null Plazas disponibles, o null si el evento no tiene tope.
     */
    public function getCuposDisponiblesAttribute(): ?int
    {
        // Se devuelve null y no cero: null significa «sin límite», mientras
        // que cero significaría «lleno».
        if (!$this->cupo_maximo) {
            return null;
        }

        $inscritos = $this->participantes()
                          ->whereIn('estado', self::ESTADOS_QUE_OCUPAN_CUPO)
                          ->count();

        // «max(0, ...)» evita mostrar cifras negativas si el cupo se redujo
        // después de haber admitido a más gente de la que ahora cabe.
        return max(0, $this->cupo_maximo - $inscritos);
    }

    /**
     * Comprueba si una persona ya está inscrita al evento.
     *
     * Se usa para decidir si mostrar el botón «Inscribirme» o el aviso «Ya
     * estás inscrito», y para impedir inscripciones duplicadas.
     *
     * @param  int  $userId  Identificador del usuario.
     * @return bool Verdadero si ya tiene una inscripción vigente.
     */
    public function usuarioEstaInscrito(int $userId): bool
    {
        return $this->participantes()
                    ->where('user_id', $userId)
                    ->whereIn('estado', self::ESTADOS_QUE_OCUPAN_CUPO)
                    ->exists();
    }

    /**
     * Estado real del evento, calculado a partir de las fechas.
     *
     * Se accede como propiedad: $evento->estado_dinamico
     *
     * Resuelve un problema práctico: nadie entra al sistema a marcar como
     * finalizado cada evento que termina. Sin este cálculo, la cartelera
     * seguiría anunciando como «activos» eventos celebrados hace meses.
     *
     * Orden de decisión:
     *   1. Si se marcó a mano como finalizado o inactivo, se respeta. Una
     *      decisión humana (por ejemplo cancelar un evento) prevalece sobre
     *      lo que digan las fechas.
     *   2. Si la fecha de fin ya pasó → finalizado.
     *   3. Si ya empezó pero no ha terminado → en curso.
     *   4. En cualquier otro caso → activo (aún no comienza).
     *
     * @return string activo, en_curso, inactivo o finalizado.
     */
    public function getEstadoDinamicoAttribute(): string
    {
        $ahora = now();

        // Paso 1: la decisión manual manda sobre el cálculo automático.
        if (in_array($this->estado, ['finalizado', 'inactivo'])) {
            return $this->estado;
        }

        // Paso 2: el evento ya terminó.
        if ($this->fecha_fin && $this->fecha_fin < $ahora) {
            return 'finalizado';
        }

        // Paso 3: el evento está teniendo lugar ahora mismo.
        if ($this->fecha_inicio <= $ahora) {
            return 'en_curso';
        }

        // Paso 4: todavía no ha comenzado.
        return 'activo';
    }

    /**
     * Estado con el formato con el que se muestra en pantalla.
     *
     * Se accede como propiedad: $evento->estado_texto
     *
     * @return string Estado formateado.
     */
    public function getEstadoTextoAttribute(): string
    {
        // Se parte del estado dinámico, no del guardado, para que el texto
        // coincida con lo que el usuario ve reflejado en el color y la fecha.
        $estadoDinamico = $this->estado_dinamico;

        return match($estadoDinamico) {
            'activo' => 'Activo',
            'en_curso' => 'En Curso',
            'inactivo' => 'Inactivo',
            'finalizado' => 'Finalizado',
            default => $estadoDinamico,
        };
    }

    /**
     * Clases de estilo para la etiqueta de color del estado.
     *
     * Se accede como propiedad: $evento->estado_badge_class
     *
     * @return string Clases de Tailwind para la etiqueta.
     */
    public function getEstadoBadgeClassAttribute(): string
    {
        $estadoDinamico = $this->estado_dinamico;

        return match($estadoDinamico) {
            'activo' => 'bg-green-100 text-green-800',
            'en_curso' => 'bg-orange-100 text-orange-800',
            'inactivo' => 'bg-gray-100 text-gray-800',
            'finalizado' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Consulta que alimenta el módulo de informes (requisito RF-11).
     *
     * Vive en el modelo, y no en el controlador, para que la pantalla de
     * informes y la exportación a Excel apliquen exactamente los mismos
     * filtros y no muestren datos distintos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  array<string, mixed>  $filtros  Filtros opcionales: evento_id,
     *         estado, fecha_inicio y fecha_fin.
     * @return \Illuminate\Database\Eloquent\Builder Consulta lista para ejecutar.
     */
    public function scopeConsulta($query, array $filtros = [])
    {
        // «withCount» añade los totales como columnas calculadas en la misma
        // consulta, en lugar de contar participantes evento por evento.
        $query->with('creador')
              ->withCount(['participantes', 'inscripciones']);

        if (!empty($filtros['evento_id'])) {
            $query->where('id', $filtros['evento_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        // El rango se aplica sobre la fecha de inicio del evento, no sobre la
        // de creación del registro: quien pide «eventos de marzo» se refiere
        // a los celebrados en marzo, no a los dados de alta ese mes.
        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('fecha_inicio', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('fecha_inicio', '<=', $filtros['fecha_fin']);
        }

        return $query->orderBy('fecha_inicio', 'desc');
    }
}
