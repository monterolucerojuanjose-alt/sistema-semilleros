<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

/**
 * ============================================================================
 * MODELO: SEMILLERO DE INVESTIGACIÓN
 * ============================================================================
 *
 * Grupo de estudiantes que trabaja una línea temática bajo la guía de un
 * tutor. Es la entidad central del sistema: los proyectos pertenecen a un
 * semillero, y los estudiantes participan a través de él.
 *
 * Control de ingreso, en dos capas:
 *
 *   1. Clave de acceso (opcional). Si el semillero define una, el estudiante
 *      debe escribirla para poder solicitar el ingreso. La clave se guarda
 *      cifrada y nunca se envía al navegador.
 *   2. Aprobación del tutor. Toda solicitud queda «pendiente» hasta que el
 *      tutor o la coordinación la aprueba.
 *
 * Además puede fijarse un cupo máximo de integrantes.
 *
 * Tabla asociada: semilleros
 *
 * @property int    $id
 * @property string $nombre
 * @property string $descripcion
 * @property string $linea_tematica  Área de investigación del grupo.
 * @property int    $tutor_id        Docente responsable.
 * @property int    $coordinador_id  Coordinador académico.
 * @property string $estado          «activo» o «inactivo».
 * @property string $clave_acceso    Clave cifrada; nunca se expone.
 * @property int    $cupo_maximo     Límite de integrantes; vacío = sin límite.
 *
 * @property-read bool     $requiere_clave     Si pide clave para ingresar.
 * @property-read int|null $cupos_disponibles  Plazas libres, o null si no hay tope.
 */
class Semillero extends Model
{
    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'linea_tematica',
        'tutor_id',
        'coordinador_id',
        'estado',
        'clave_acceso',
        'cupo_maximo',
    ];

    /**
     * Campos que nunca se envían al navegador.
     *
     * La clave de acceso queda excluida de forma explícita. Aunque se guarda
     * cifrada, incluirla en la respuesta la dejaría visible en el código
     * fuente de la página para cualquiera que lo inspeccione.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'clave_acceso',
    ];

    /**
     * Propiedades calculadas que se añaden a la respuesta enviada a la pantalla.
     *
     * «requiere_clave» resuelve el dilema de la clave: la pantalla necesita
     * saber si debe mostrar el campo para escribirla, pero no debe conocer su
     * valor. Se envía el sí o el no, nunca la clave.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'requiere_clave',
        'cupos_disponibles',
    ];

    /**
     * Indica si el semillero exige clave para solicitar el ingreso.
     *
     * Se accede como propiedad: $semillero->requiere_clave
     *
     * Se lee de $this->attributes y no de $this->clave_acceso porque el campo
     * está oculto, y el acceso normal devolvería un valor vacío.
     *
     * @return bool Verdadero si hay una clave definida.
     */
    public function getRequiereClaveAttribute(): bool
    {
        return filled($this->attributes['clave_acceso'] ?? null);
    }

    /**
     * Plazas libres que quedan en el semillero.
     *
     * Se accede como propiedad: $semillero->cupos_disponibles
     *
     * @return int|null Plazas disponibles, o null si el semillero no tiene tope.
     */
    public function getCuposDisponiblesAttribute(): ?int
    {
        // Sin cupo máximo no hay nada que calcular: el semillero admite a
        // cualquiera. Se devuelve null y no cero, porque cero significaría
        // «lleno» y aquí el sentido es «sin límite».
        if (! $this->cupo_maximo) {
            return null;
        }

        // Si la relación ya viene cargada se cuenta en memoria; si no, se
        // pregunta a la base de datos. Esta distinción evita el problema
        // conocido como «N+1»: sin ella, un listado de veinte semilleros
        // lanzaría veinte consultas adicionales, una por cada tarjeta.
        $ocupados = $this->relationLoaded('miembrosAprobados')
            ? $this->miembrosAprobados->count()
            : $this->miembrosAprobados()->count();

        // «max(0, ...)» impide mostrar cifras negativas si alguna vez se
        // admitieron más integrantes que el cupo o si el cupo se redujo
        // después de aceptar gente.
        return max(0, $this->cupo_maximo - $ocupados);
    }

    /**
     * Indica si el semillero ya no admite más integrantes.
     *
     * @return bool Verdadero si no quedan plazas libres.
     */
    public function estaLleno(): bool
    {
        // La comparación es estricta (===) a propósito: null significa «sin
        // límite» y con una comparación laxa (==) se confundiría con cero,
        // dejando como llenos a todos los semilleros sin tope.
        return $this->cupos_disponibles === 0;
    }

    /**
     * Comprueba la clave escrita por el estudiante.
     *
     * @param  string|null  $clave  Clave introducida en el formulario.
     * @return bool Verdadero si la clave es correcta o si no se exige ninguna.
     */
    public function claveEsCorrecta(?string $clave): bool
    {
        // Si el semillero no pide clave, cualquier intento es válido.
        if (! $this->requiere_clave) {
            return true;
        }

        // Hash::check compara la clave escrita con la cifrada. No se comparan
        // como texto porque la almacenada está cifrada de un solo sentido:
        // no existe forma de descifrarla, solo de verificar una coincidencia.
        return $clave !== null && Hash::check($clave, $this->attributes['clave_acceso']);
    }

    /**
     * Docente responsable del semillero.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    /**
     * Coordinador académico del semillero.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }

    /**
     * Proyectos que desarrolla este semillero.
     *
     * @return HasMany Relación de uno a muchos con Proyecto.
     */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }

    /**
     * Todos los usuarios vinculados al semillero, en cualquier estado.
     *
     * Incluye tanto a los aprobados como a los que tienen la solicitud
     * pendiente o rechazada. La tabla intermedia «semillero_user» guarda,
     * además del vínculo, el rol dentro del grupo y el estado de la solicitud.
     *
     * @return BelongsToMany Relación de muchos a muchos con User.
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'semillero_user')
                    ->withPivot(['rol_en_semillero', 'estado_solicitud', 'fecha'])
                    ->withTimestamps();
    }

    /**
     * Solo los integrantes con la solicitud aprobada.
     *
     * Es la relación que debe usarse para contar integrantes reales y para
     * decidir quién puede ver o subir material del semillero.
     *
     * @return BelongsToMany Relación de muchos a muchos con User.
     */
    public function miembrosAprobados(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'semillero_user')
                    ->wherePivot('estado_solicitud', 'aprobado')
                    ->withPivot(['rol_en_semillero', 'estado_solicitud', 'fecha'])
                    ->withTimestamps();
    }

    /**
     * Solicitudes de ingreso aún sin resolver.
     *
     * Alimenta la bandeja de solicitudes del tutor.
     *
     * @return BelongsToMany Relación de muchos a muchos con User.
     */
    public function solicitudesPendientes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'semillero_user')
                    ->wherePivot('estado_solicitud', 'pendiente')
                    ->withPivot(['rol_en_semillero', 'estado_solicitud', 'fecha'])
                    ->withTimestamps();
    }

    /**
     * Filtro reutilizable: solo semilleros activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Filtro reutilizable: solo semilleros inactivos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeInactivos($query)
    {
        return $query->where('estado', 'inactivo');
    }

    /**
     * Consulta que alimenta el módulo de informes (requisito RF-11).
     *
     * Vive en el modelo, y no en el controlador, para que la pantalla de
     * informes y la exportación a Excel apliquen exactamente los mismos
     * filtros. Cuando esta lógica estaba duplicada, cambiar un filtro en la
     * pantalla dejaba el Excel mostrando otros datos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  array<string, mixed>  $filtros  Filtros opcionales: semillero_id,
     *         estado, fecha_inicio y fecha_fin.
     * @return \Illuminate\Database\Eloquent\Builder Consulta lista para ejecutar.
     */
    public function scopeConsulta($query, array $filtros = [])
    {
        // «with» carga tutor y coordinador por adelantado, y «withCount»
        // añade los totales como columnas calculadas. Ambos evitan lanzar
        // consultas extra por cada fila del informe.
        $query->with(['tutor', 'coordinador'])
              ->withCount(['miembrosAprobados', 'solicitudesPendientes', 'proyectos']);

        // Cada filtro se aplica solo si viene con valor. Se usa «!empty» y no
        // «isset» porque un campo enviado vacío desde el formulario llega
        // como cadena en blanco, no como ausente, y filtraría por vacío.
        if (!empty($filtros['semillero_id'])) {
            $query->where('id', $filtros['semillero_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        // «whereDate» compara solo la parte de la fecha, ignorando la hora.
        // Sin esto, un semillero creado a las 15:00 quedaría fuera de un
        // filtro «hasta hoy», porque la comparación tomaría las 00:00.
        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_fin']);
        }

        return $query->orderBy('nombre');
    }
}
