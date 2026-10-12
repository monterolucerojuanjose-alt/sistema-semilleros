<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODELO: EVALUACIÓN DE UN PROYECTO
 * ============================================================================
 *
 * La calificación completa que un jurado otorga a un proyecto usando una
 * rúbrica determinada.
 *
 * Reúne tres elementos: quién califica (jurado), qué califica (proyecto) y
 * con qué instrumento (rúbrica). El desglose criterio por criterio vive en
 * DetalleEvaluacion; aquí queda la cabecera y la nota final resultante.
 *
 * Ciclo de vida:
 *
 *   1. Se crea la evaluación en estado «Pendiente».
 *   2. El jurado va registrando la nota de cada criterio (detalles).
 *   3. Al terminar, completarEvaluacion() calcula la nota final y la deja
 *      en estado «Evaluado».
 *
 * Tabla asociada: evaluaciones
 *
 * @property int    $id
 * @property int    $proyecto_id    Proyecto calificado.
 * @property int    $jurado_id      Usuario que califica.
 * @property int    $rubrica_id     Rúbrica utilizada.
 * @property string $observaciones  Comentarios cualitativos del jurado.
 * @property float  $nota_final     Nota resultante, de 0.0 a 100.0.
 * @property string $estado         «Pendiente» o «Evaluado».
 *
 * @property-read bool $completa        Si la evaluación ya está cerrada.
 * @property-read int  $puntaje_total   Suma de las notas sin ponderar.
 * @property-read int  $puntaje_maximo  Suma de los máximos sin ponderar.
 */
class Evaluacion extends Model
{
    use HasFactory;

    /**
     * Nota más alta que puede obtener un proyecto.
     *
     * Corresponde a la escala institucional de calificación, de 0.0 a 100.0.
     * Se declara como constante y no como número suelto para que la escala
     * se cambie en un único sitio si la institución la modifica.
     */
    public const NOTA_MAXIMA = 100;

    /**
     * Nombre real de la tabla. Laravel buscaría «evaluacions» al pluralizar
     * en inglés, que no es el nombre correcto en español.
     *
     * @var string
     */
    protected $table = 'evaluaciones';

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proyecto_id',
        'evento_participante_id',
        'jurado_id',
        'rubrica_id',
        'observaciones',
        'nota_final',
        'estado',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * «decimal:2» conserva los dos decimales de la nota. Sin esta conversión,
     * una nota de 4.50 podría mostrarse como 4.5 o como «4.50» en texto según
     * el contexto, y las comparaciones numéricas fallarían.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nota_final' => 'decimal:2',
    ];

    /**
     * Proyecto que se está calificando.
     *
     * Nulo cuando la evaluación es, en cambio, de una propuesta de evento
     * (ver eventoParticipante()): una evaluación califica siempre una de
     * las dos cosas, nunca ambas.
     *
     * @return BelongsTo Relación de pertenencia con Proyecto.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /**
     * Propuesta de evento que se está calificando.
     *
     * Nulo cuando la evaluación es, en cambio, de un proyecto de catálogo
     * (ver proyecto()).
     *
     * @return BelongsTo Relación de pertenencia con EventoParticipante.
     */
    public function eventoParticipante(): BelongsTo
    {
        return $this->belongsTo(EventoParticipante::class);
    }

    /**
     * Usuario que actúa como jurado en esta evaluación.
     *
     * Se especifica «jurado_id» porque Laravel buscaría por omisión una
     * columna llamada «user_id», que no existe en esta tabla.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function jurado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jurado_id');
    }

    /**
     * Rúbrica con la que se califica.
     *
     * @return BelongsTo Relación de pertenencia con Rubrica.
     */
    public function rubrica(): BelongsTo
    {
        return $this->belongsTo(Rubrica::class);
    }

    /**
     * Notas otorgadas en cada criterio de la rúbrica.
     *
     * @return HasMany Relación de uno a muchos con DetalleEvaluacion.
     */
    public function detalleEvaluaciones(): HasMany
    {
        return $this->hasMany(DetalleEvaluacion::class);
    }

    /**
     * Calcula la nota final a partir de las notas por criterio.
     *
     * Procedimiento, para cada criterio:
     *   1. Se lleva la nota a la escala 0-100, sea cual sea el máximo propio
     *      del criterio.  →  (nota / máximo) × 100
     *   2. Se pondera por el peso porcentual del criterio.  →  × (peso / 100)
     *   3. Se acumula.
     *
     * Ejemplo con tres criterios:
     *
     *   Innovación    4/5, peso 40 %  →  (4/5)×100 × 0,40 = 32,00
     *   Metodología   5/5, peso 35 %  →  (5/5)×100 × 0,35 = 35,00
     *   Presentación  3/5, peso 25 %  →  (3/5)×100 × 0,25 = 15,00
     *                                    ───────────────────────
     *                                    Nota final:        82,00
     *
     * Este resultado coincide con DetalleEvaluacion::puntaje_ponderado, que
     * ya trabajaba en escala 0-100; antes del cambio de escala institucional
     * ese accesor y esta nota final no eran directamente comparables.
     *
     * @return float Nota final entre 0.00 y 100.00, con dos decimales.
     */
    public function calcularNotaFinal(): float
    {
        $notaFinal = 0;

        foreach ($this->detalleEvaluaciones as $detalle) {
            $criterio = $detalle->criterio;

            // Se salta el criterio si fue eliminado de la rúbrica o si su
            // máximo es cero. Sin esta comprobación, el cálculo terminaría
            // en una división por cero y la pantalla de resultados quedaría
            // en blanco sin mensaje de error.
            if (!$criterio || !$criterio->puntaje_maximo) {
                continue;
            }

            // Paso 1: llevar la nota del criterio a la escala 0-100.
            $puntajeNormalizado = ($detalle->puntaje / $criterio->puntaje_maximo) * self::NOTA_MAXIMA;

            // Paso 2 y 3: ponderar por el peso y acumular.
            $notaFinal += $puntajeNormalizado * ($criterio->peso / 100);
        }

        // Se redondea a dos decimales, que es la precisión con la que se
        // publican las notas y la que admite la columna de la base de datos.
        return round($notaFinal, 2);
    }

    /**
     * Cierra la evaluación: calcula la nota y la marca como terminada.
     *
     * @return void
     */
    public function completarEvaluacion(): void
    {
        $this->nota_final = $this->calcularNotaFinal();
        $this->estado = 'Evaluado';
        $this->save();
    }

    /**
     * Indica si la evaluación ya está cerrada.
     *
     * Se accede como propiedad: $evaluacion->completa
     *
     * @return bool Verdadero si el estado es «Evaluado».
     */
    public function getCompletaAttribute(): bool
    {
        return $this->estado === 'Evaluado';
    }

    /**
     * Suma de las notas de todos los criterios, sin aplicar pesos.
     *
     * Se accede como propiedad: $evaluacion->puntaje_total
     *
     * No es la nota final: ignora los pesos. Se usa junto a puntaje_maximo
     * para mostrar un «14 de 15» informativo en pantalla.
     *
     * @return int Suma de las notas otorgadas.
     */
    public function getPuntajeTotalAttribute(): int
    {
        return $this->detalleEvaluaciones->sum('puntaje');
    }

    /**
     * Suma de los máximos de todos los criterios, sin aplicar pesos.
     *
     * Se accede como propiedad: $evaluacion->puntaje_maximo
     *
     * @return int Puntaje máximo alcanzable sin ponderar.
     */
    public function getPuntajeMaximoAttribute(): int
    {
        return $this->rubrica->criterios->sum('puntaje_maximo');
    }
}
