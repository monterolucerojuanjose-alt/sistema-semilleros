<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * MODELO: PROYECTO DE INVESTIGACIÓN
 * ============================================================================
 *
 * Trabajo que desarrolla un estudiante dentro de un semillero.
 *
 * Cada proyecto recibe un código institucional consecutivo con el formato
 * FAC-AAAA-NNN (por ejemplo FAC-2026-014), conserva el historial de las
 * versiones entregadas, puede inscribirse a eventos y acumula las
 * evaluaciones que le hacen los jurados.
 *
 * Tabla asociada: proyectos
 *
 * @property int    $id
 * @property string $titulo
 * @property string $resumen
 * @property string $codigo        Código institucional, formato FAC-AAAA-NNN.
 * @property string $estado        Situación en el flujo de aprobación.
 * @property int    $semillero_id  Semillero al que pertenece.
 * @property int    $autor_id      Estudiante que lo presenta.
 *
 * @property-read float $promedio_evaluaciones   Media de las notas recibidas.
 * @property-read int   $total_evaluaciones      Evaluaciones ya cerradas.
 * @property-read int   $evaluaciones_pendientes Evaluaciones sin terminar.
 * @property-read bool  $evaluado                Si tiene alguna nota.
 */
class Proyecto extends Model
{
    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'titulo',
        'resumen',
        'codigo',
        'estado',
        'semillero_id',
        'autor_id'
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
     * Estudiante que presenta el proyecto.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    /**
     * Semillero al que pertenece el proyecto.
     *
     * @return BelongsTo Relación de pertenencia con Semillero.
     */
    public function semillero(): BelongsTo
    {
        return $this->belongsTo(Semillero::class);
    }

    /**
     * Historial de versiones entregadas.
     *
     * @return HasMany Relación de uno a muchos con VersionProyecto.
     */
    public function versiones(): HasMany
    {
        return $this->hasMany(VersionProyecto::class);
    }

    /**
     * Una sola inscripción a evento.
     *
     * Existe junto a inscripciones() porque muchas pantallas solo necesitan
     * saber si el proyecto está inscrito y a qué evento. Pedir una relación
     * «uno a uno» evita traer una lista completa para consultar un único dato.
     *
     * @return HasOne Relación de uno a uno con InscripcionEvento.
     */
    public function inscripcionEvento(): HasOne
    {
        return $this->hasOne(InscripcionEvento::class);
    }

    /**
     * Todas las inscripciones a eventos del proyecto.
     *
     * Un mismo proyecto puede presentarse en varios eventos a lo largo del
     * tiempo, así que esta es la relación correcta para el historial.
     *
     * @return HasMany Relación de uno a muchos con InscripcionEvento.
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionEvento::class);
    }

    /**
     * Evaluaciones recibidas de los jurados.
     *
     * @return HasMany Relación de uno a muchos con Evaluacion.
     */
    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class);
    }

    /**
     * Nota media del proyecto.
     *
     * Se accede como propiedad: $proyecto->promedio_evaluaciones
     *
     * Solo promedia las evaluaciones cerradas. Incluir las que están a medias
     * daría una media falsa, porque su nota final aún vale cero.
     *
     * @return float Media de las notas, de 0.00 a 100.00; 0 si no hay ninguna.
     */
    public function getPromedioEvaluacionesAttribute(): float
    {
        $evaluacionesCompletas = $this->evaluaciones()->where('estado', 'Evaluado')->get();

        // Sin evaluaciones cerradas se devuelve 0 en lugar de null, para que
        // la pantalla muestre un número y no un hueco vacío.
        if ($evaluacionesCompletas->isEmpty()) {
            return 0;
        }

        return round($evaluacionesCompletas->avg('nota_final'), 2);
    }

    /**
     * Número de evaluaciones ya cerradas.
     *
     * Se accede como propiedad: $proyecto->total_evaluaciones
     *
     * @return int Cantidad de evaluaciones terminadas.
     */
    public function getTotalEvaluacionesAttribute(): int
    {
        return $this->evaluaciones()->where('estado', 'Evaluado')->count();
    }

    /**
     * Número de evaluaciones que algún jurado aún no ha terminado.
     *
     * Se accede como propiedad: $proyecto->evaluaciones_pendientes
     *
     * @return int Cantidad de evaluaciones pendientes.
     */
    public function getEvaluacionesPendientesAttribute(): int
    {
        return $this->evaluaciones()->where('estado', 'Pendiente')->count();
    }

    /**
     * Indica si el proyecto tiene al menos una nota.
     *
     * Se accede como propiedad: $proyecto->evaluado
     *
     * @return bool Verdadero si existe alguna evaluación cerrada.
     */
    public function getEvaluadoAttribute(): bool
    {
        // «exists» pregunta a la base si hay al menos una fila y se detiene
        // ahí. Es más rápido que contarlas todas cuando solo interesa el sí
        // o el no.
        return $this->evaluaciones()->where('estado', 'Evaluado')->exists();
    }

    /**
     * Versión más reciente del proyecto.
     *
     * @return HasOne Relación de uno a uno, ordenada de la más nueva a la más antigua.
     */
    public function ultimaVersion()
    {
        return $this->hasOne(VersionProyecto::class)->latest();
    }

    /**
     * Filtro reutilizable: proyectos en un estado concreto.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string  $estado  Estado a filtrar.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Filtro reutilizable: proyectos de un semillero.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int  $semilleroId  Identificador del semillero.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorSemillero($query, $semilleroId)
    {
        return $query->where('semillero_id', $semilleroId);
    }

    /**
     * Filtro reutilizable: proyectos de un autor.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int  $autorId  Identificador del estudiante.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorAutor($query, $autorId)
    {
        return $query->where('autor_id', $autorId);
    }

    /**
     * Calcula el siguiente código institucional disponible.
     *
     * ⚠️  Este método solo calcula el código, no reserva nada. Si dos personas
     *     registran un proyecto en el mismo instante, ambas pueden obtener el
     *     mismo número. Para crear proyectos debe usarse crearConCodigo(),
     *     que sí protege frente a esa coincidencia.
     *
     * @return string Código con el formato FAC-AAAA-NNN.
     */
    public static function generarCodigo(): string
    {
        $year = date('Y');

        // Se busca el código más alto del año en curso. La numeración se
        // reinicia cada año, de ahí que el filtro incluya el año.
        $ultimoProyecto = self::where('codigo', 'like', "FAC-{$year}-%")
            ->orderBy('codigo', 'desc')
            ->first();

        if ($ultimoProyecto) {
            // Se toman las tres últimas cifras del código y se suma uno.
            $ultimoNumero = (int) substr($ultimoProyecto->codigo, -3);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            // Primer proyecto del año.
            $nuevoNumero = 1;
        }

        // «%03d» rellena con ceros a la izquierda: 1 se convierte en «001».
        // Ese formato fijo es lo que permite ordenar los códigos como texto
        // y que el orden coincida con el numérico.
        return sprintf('FAC-%s-%03d', $year, $nuevoNumero);
    }

    /**
     * Crea un proyecto asignándole el código de forma segura.
     *
     * Es la vía correcta para registrar proyectos. Resuelve el problema de
     * las peticiones simultáneas: sin esta protección, dos estudiantes que
     * envíen el formulario a la vez leerían el mismo «último código» y
     * ambos proyectos acabarían con el mismo número.
     *
     * La solución combina dos mecanismos:
     *   - transaction: o se completan todos los pasos, o no se guarda nada.
     *   - lockForUpdate: bloquea las filas consultadas hasta que termine la
     *     transacción, de modo que la segunda petición espera su turno en
     *     lugar de leer un dato que está a punto de quedar obsoleto.
     *
     * @param  array<string, mixed>  $attributes  Datos del proyecto, sin el código.
     * @return self El proyecto creado, ya con su código asignado.
     */
    public static function crearConCodigo(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $year = now()->format('Y');

            $ultimoCodigo = self::where('codigo', 'like', "FAC-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('codigo')
                ->value('codigo');

            $ultimoNumero = $ultimoCodigo ? (int) substr($ultimoCodigo, -3) : 0;
            $attributes['codigo'] = sprintf('FAC-%s-%03d', $year, $ultimoNumero + 1);

            return self::create($attributes);
        });
    }
}
