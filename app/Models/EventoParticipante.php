<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODELO: PARTICIPANTE DE UN EVENTO
 * ============================================================================
 *
 * Registro de una persona inscrita a un evento, con su propuesta de proyecto
 * y, más adelante, con la evaluación que recibe.
 *
 * No debe confundirse con InscripcionEvento: aquel inscribe proyectos que ya
 * existen en el sistema, mientras que este recoge propuestas presentadas
 * directamente al evento, con su propio archivo y sus propios datos.
 *
 * El registro atraviesa dos procesos consecutivos, y por eso tiene dos campos
 * de estado independientes:
 *
 *   1. Admisión  (campo «estado»)
 *      pendiente → aprobado / rechazado → inscrito → asistio / no_asistio
 *
 *   2. Evaluación  (campo «estado_evaluacion»)
 *      sin_evaluar → en_revision → evaluado
 *
 * Mantenerlos separados permite que un proyecto admitido esté aún sin
 * calificar sin que ninguno de los dos estados contradiga al otro.
 *
 * Tabla asociada: evento_participantes
 *
 * @property int    $id
 * @property int    $evento_id
 * @property int    $user_id
 * @property string $carrera             Heredado de antes de la convocatoria actual; se conserva solo para inscripciones viejas.
 * @property string $modalidad           Modalidad de participación de la propuesta.
 * @property string $area_participacion  Área de participación (científica) de la propuesta.
 * @property string $estado             Situación en el proceso de admisión.
 * @property string $titulo_proyecto
 * @property string $archivo_proyecto   Ruta del documento presentado.
 * @property array  $participantes      Integrantes del equipo.
 * @property int    $intentos_envio     Cuántas veces se ha enviado esta propuesta (sube al corregir un rechazo).
 * @property string $aula               Aula asignada para la presentación.
 * @property string $horario            Horario asignado.
 * @property int    $revisado_por       Quién resolvió la admisión.
 * @property string $motivo_rechazo
 * @property int    $evaluador_id       Jurado que califica.
 * @property float  $nota_final         Nota obtenida, de 0.0 a 100.0.
 * @property string $estado_evaluacion  Situación en el proceso de evaluación.
 *
 * @property-read string $estado_texto        Estado con formato de presentación.
 * @property-read string $estado_badge_class  Clases de estilo para la etiqueta.
 * @property-read string $programa_reporte    Área de participación, o la carrera si es una inscripción anterior a ese campo.
 */
class EventoParticipante extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // --- Datos de la inscripción ---
        'evento_id',
        'user_id',
        'carrera',
        'modalidad',
        'area_participacion',
        'estado',
        'observaciones',

        // --- Propuesta presentada ---
        'titulo_proyecto',
        'descripcion_proyecto',
        'archivo_proyecto',
        'participantes',
        'intentos_envio',

        // --- Asignación logística, una vez admitido ---
        'programacion',
        'aula',
        'horario',

        // --- Resolución de la admisión ---
        'fecha_revision',
        'revisado_por',
        'motivo_rechazo',

        // --- Evaluación del proyecto ---
        'evaluador_id',
        'nota_final',
        'analisis_proyecto',
        'fortalezas',
        'debilidades',
        'recomendaciones',
        'estado_evaluacion',
        'fecha_evaluacion',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * «participantes» se guarda como JSON porque un proyecto puede presentarse
     * en equipo, y el número de integrantes varía. Guardarlo así evita crear
     * una tabla adicional para un dato que solo se consulta junto al registro.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'participantes' => 'array',
        'fecha_revision' => 'datetime',
        'fecha_evaluacion' => 'datetime',
        'nota_final' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Evento al que corresponde esta participación.
     *
     * @return BelongsTo Relación de pertenencia con Evento.
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    /**
     * Estudiante que se inscribe.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Persona que aprobó o rechazó la inscripción.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    /**
     * Jurado o docente que califica el proyecto.
     *
     * Es una relación distinta de la del revisor: quien admite la propuesta
     * no tiene por qué ser quien después la evalúa.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function evaluador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluador_id');
    }

    /**
     * Evaluaciones con rúbrica registradas sobre esta propuesta.
     *
     * Normalmente es una por jurado asignado. El detalle criterio por
     * criterio vive en DetalleEvaluacion, colgado de cada una de estas filas.
     *
     * @return HasMany Relación de uno a muchos con Evaluacion.
     */
    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class);
    }

    /**
     * Filtro reutilizable: inscripciones sin resolver.
     *
     * Alimenta la bandeja de revisión del coordinador.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Filtro reutilizable: inscripciones aprobadas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeAprobadas($query)
    {
        return $query->where('estado', 'aprobado');
    }

    /**
     * Filtro reutilizable: inscripciones rechazadas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeRechazadas($query)
    {
        return $query->where('estado', 'rechazado');
    }

    /**
     * Filtro reutilizable: participantes con la inscripción confirmada.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeInscritos($query)
    {
        return $query->where('estado', 'inscrito');
    }

    /**
     * Filtro reutilizable: quienes efectivamente asistieron.
     *
     * Es el filtro relevante para emitir certificados de asistencia: se
     * certifica a quien asistió, no a quien solo se inscribió.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeAsistieron($query)
    {
        return $query->where('estado', 'asistio');
    }

    /**
     * Filtro reutilizable: participantes de un evento concreto.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int  $eventoId  Identificador del evento.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorEvento($query, $eventoId)
    {
        return $query->where('evento_id', $eventoId);
    }

    /**
     * Filtro reutilizable: participaciones de una persona.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int  $userId  Identificador del usuario.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filtro reutilizable: proyectos que nadie ha empezado a calificar.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeSinEvaluar($query)
    {
        return $query->where('estado_evaluacion', 'sin_evaluar');
    }

    /**
     * Filtro reutilizable: proyectos con la evaluación a medias.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeEnRevision($query)
    {
        return $query->where('estado_evaluacion', 'en_revision');
    }

    /**
     * Filtro reutilizable: proyectos ya calificados.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeEvaluados($query)
    {
        return $query->where('estado_evaluacion', 'evaluado');
    }

    /**
     * Filtro reutilizable: proyectos asignados a un jurado.
     *
     * Es el filtro que usa la pantalla del jurado para mostrarle únicamente
     * los proyectos que le tocan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int  $evaluadorId  Identificador del jurado.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorEvaluador($query, $evaluadorId)
    {
        return $query->where('evaluador_id', $evaluadorId);
    }

    /**
     * Si esta propuesta es una corrección de un rechazo anterior.
     *
     * Se accede como propiedad: $participante->es_correccion
     *
     * @return bool
     */
    public function getEsCorreccionAttribute(): bool
    {
        return $this->intentos_envio > 1;
    }

    /**
     * El área de participación que se muestra en los reportes.
     *
     * Se accede como propiedad: $participante->programa_reporte
     *
     * Las inscripciones hechas antes de que existiera «área de
     * participación» solo tienen «carrera»; esta propiedad centraliza el
     * criterio de respaldo para no repetirlo en cada reporte y exportación.
     *
     * @return string|null
     */
    public function getProgramaReporteAttribute(): ?string
    {
        return $this->area_participacion ?? $this->carrera;
    }

    /**
     * Estado con el formato con el que se muestra en pantalla.
     *
     * Se accede como propiedad: $participante->estado_texto
     *
     * Convierte el valor técnico guardado en la base («no_asistio») en el
     * texto que lee la persona («No Asistió»).
     *
     * @return string Estado formateado.
     */
    public function getEstadoTextoAttribute(): string
    {
        return match($this->estado) {
            'pendiente' => 'Pendiente de Revisión',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            'inscrito' => 'Inscrito',
            'asistio' => 'Asistió',
            'no_asistio' => 'No Asistió',
            'cancelado' => 'Cancelado',
            default => $this->estado,
        };
    }

    /**
     * Clases de estilo para la etiqueta de color del estado.
     *
     * Se accede como propiedad: $participante->estado_badge_class
     *
     * @return string Clases de Tailwind para la etiqueta.
     */
    public function getEstadoBadgeClassAttribute(): string
    {
        return match($this->estado) {
            'pendiente' => 'bg-yellow-100 text-yellow-800',
            'aprobado' => 'bg-green-100 text-green-800',
            'rechazado' => 'bg-red-100 text-red-800',
            'inscrito' => 'bg-blue-100 text-blue-800',
            'asistio' => 'bg-green-100 text-green-800',
            'no_asistio' => 'bg-red-100 text-red-800',
            'cancelado' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
