<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODELO: DETALLE DE UNA EVALUACIÓN
 * ============================================================================
 *
 * La nota concreta que un jurado puso a un proyecto en un criterio concreto.
 * Es la unidad más pequeña del sistema de calificación.
 *
 * Relación con el resto del módulo:
 *
 *   Evaluación (un jurado califica un proyecto)
 *     ├── Detalle: criterio «Innovación»   → 4
 *     ├── Detalle: criterio «Metodología»  → 5
 *     └── Detalle: criterio «Presentación» → 3
 *
 * La nota final de la evaluación no se guarda como un número suelto: se
 * calcula sumando estos detalles ponderados por el peso de cada criterio.
 * Así siempre se puede justificar de dónde sale una nota, que es lo que se
 * necesita ante una reclamación.
 *
 * Tabla asociada: detalle_evaluaciones
 *
 * @property int $id
 * @property int $evaluacion_id  Evaluación a la que pertenece.
 * @property int $criterio_id    Criterio que se calificó.
 * @property int $puntaje        Nota otorgada.
 *
 * @property-read float $puntaje_normalizado  La nota llevada a escala 0-100.
 * @property-read float $puntaje_ponderado    Lo que aporta a la nota final.
 * @property-read bool  $puntaje_valido       Si la nota está dentro del rango.
 */
class DetalleEvaluacion extends Model
{
    use HasFactory;

    /**
     * Nombre real de la tabla, declarado porque Laravel no pluraliza
     * correctamente «DetalleEvaluacion» al español.
     *
     * @var string
     */
    protected $table = 'detalle_evaluaciones';

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'evaluacion_id',
        'criterio_id',
        'puntaje',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'puntaje' => 'integer',
    ];

    /**
     * Evaluación a la que pertenece esta nota.
     *
     * @return BelongsTo Relación de pertenencia con Evaluacion.
     */
    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class);
    }

    /**
     * Criterio que se está calificando.
     *
     * @return BelongsTo Relación de pertenencia con CriterioRubrica.
     */
    public function criterio(): BelongsTo
    {
        return $this->belongsTo(CriterioRubrica::class, 'criterio_id');
    }

    /**
     * Convierte la nota a una escala de 0 a 100.
     *
     * Se accede como propiedad: $detalle->puntaje_normalizado
     *
     * Este paso es necesario porque no todos los criterios tienen por qué
     * usar la misma escala. Llevarlos todos a base 100 permite combinarlos
     * después según su peso.
     *
     * Ejemplo: una nota de 4 sobre un máximo de 5 equivale a 80.
     *
     * @return float Nota expresada sobre 100.
     */
    public function getPuntajeNormalizadoAttribute(): float
    {
        // Se comprueba el cero antes de dividir. Sin esta guarda, un criterio
        // mal configurado con máximo 0 provocaría una división por cero y
        // dejaría la pantalla de resultados en blanco.
        if ($this->criterio->puntaje_maximo == 0) {
            return 0;
        }

        return ($this->puntaje / $this->criterio->puntaje_maximo) * 100;
    }

    /**
     * Cuánto aporta esta nota a la calificación final.
     *
     * Se accede como propiedad: $detalle->puntaje_ponderado
     *
     * Ejemplo: 80 sobre 100 en un criterio que pesa el 40 % aporta 32 puntos.
     * La suma de los puntajes ponderados de todos los detalles es la nota
     * final de la evaluación.
     *
     * @return float Puntos que aporta a la nota final.
     */
    public function getPuntajePonderadoAttribute(): float
    {
        return $this->puntaje_normalizado * ($this->criterio->peso / 100);
    }

    /**
     * Comprueba que la nota está dentro del rango permitido por el criterio.
     *
     * Se accede como propiedad: $detalle->puntaje_valido
     *
     * @return bool Verdadero si la nota es válida.
     */
    public function getPuntajeValidoAttribute(): bool
    {
        // El límite inferior es 1 y no 0, de modo que este comprobante
        // considera inválida una nota de cero. Es una comprobación auxiliar
        // para informes; la validación que realmente impide guardar una nota
        // fuera de rango está en el controlador de evaluaciones.
        return $this->puntaje >= 1 && $this->puntaje <= $this->criterio->puntaje_maximo;
    }
}
