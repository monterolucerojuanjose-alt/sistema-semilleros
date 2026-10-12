<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODELO: CRITERIO DE UNA RÚBRICA
 * ============================================================================
 *
 * Cada uno de los aspectos que se califican dentro de una rúbrica, junto con
 * lo que pesa en la nota final.
 *
 * Por ejemplo: «Innovación», con un peso del 40 % y una nota máxima de 5.
 * Un proyecto que saque 4 sobre 5 en ese criterio aporta a la nota final
 * (4 / 5) × 40 % = 32 puntos de los 100 posibles.
 *
 * Tabla asociada: criterio_rubricas
 *
 * @property int    $id
 * @property int    $rubrica_id      Rúbrica a la que pertenece.
 * @property string $criterio        Nombre del aspecto que se evalúa.
 * @property float  $peso            Porcentaje que aporta a la nota final.
 * @property int    $puntaje_maximo  Nota más alta admitida en este criterio.
 *
 * @property-read float $promedio_puntajes   Media de las notas recibidas.
 * @property-read int   $total_evaluaciones  Cuántas veces se ha calificado.
 */
class CriterioRubrica extends Model
{
    use HasFactory;

    /**
     * Nombre real de la tabla.
     *
     * Se declara de forma explícita porque Laravel, al pluralizar en inglés,
     * buscaría «criterio_rubricas» partiendo de «CriterioRubrica» y no acierta
     * con los nombres compuestos en español.
     *
     * @var string
     */
    protected $table = 'criterio_rubricas';

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rubrica_id',
        'criterio',
        'peso',
        'puntaje_maximo',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * «decimal:2» es importante en el peso: sin esta conversión el valor
     * llegaría como texto («40.00») y las sumas y comparaciones darían
     * resultados incorrectos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'peso' => 'decimal:2',
        'puntaje_maximo' => 'integer',
    ];

    /**
     * Rúbrica a la que pertenece este criterio.
     *
     * @return BelongsTo Relación de pertenencia con Rubrica.
     */
    public function rubrica(): BelongsTo
    {
        return $this->belongsTo(Rubrica::class);
    }

    /**
     * Notas que los jurados han puesto en este criterio.
     *
     * Se indica «criterio_id» de forma explícita porque Laravel buscaría por
     * omisión una columna llamada «criterio_rubrica_id», que no existe.
     *
     * @return HasMany Relación de uno a muchos con DetalleEvaluacion.
     */
    public function detalleEvaluaciones(): HasMany
    {
        return $this->hasMany(DetalleEvaluacion::class, 'criterio_id');
    }

    /**
     * Nota media obtenida en este criterio por todos los proyectos.
     *
     * Se accede como propiedad: $criterio->promedio_puntajes
     *
     * Útil en los informes: un criterio con media muy baja puede señalar una
     * debilidad común de los proyectos, o un criterio mal redactado.
     *
     * @return float Media de las notas; 0 si todavía no se ha evaluado.
     */
    public function getPromedioPuntajesAttribute(): float
    {
        // «avg» devuelve null cuando no hay ninguna nota. El «?? 0» evita que
        // ese null llegue a la pantalla y se muestre como un hueco vacío.
        return $this->detalleEvaluaciones->avg('puntaje') ?? 0;
    }

    /**
     * Número de veces que se ha calificado este criterio.
     *
     * Se accede como propiedad: $criterio->total_evaluaciones
     *
     * @return int Cantidad de calificaciones registradas.
     */
    public function getTotalEvaluacionesAttribute(): int
    {
        return $this->detalleEvaluaciones->count();
    }
}
