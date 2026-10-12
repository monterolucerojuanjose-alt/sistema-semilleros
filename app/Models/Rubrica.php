<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODELO: RÚBRICA DE EVALUACIÓN
 * ============================================================================
 *
 * Representa el instrumento con el que un jurado califica un proyecto.
 *
 * Una rúbrica es una cabecera con un nombre («Rúbrica de proyectos de aula»)
 * que agrupa varios criterios. Cada criterio tiene un peso porcentual, y la
 * suma de todos ellos debe dar 100 para que la rúbrica sea utilizable.
 *
 * Estructura:
 *
 *   Rúbrica «Evaluación de proyectos»
 *     ├── Criterio «Innovación»      peso 40 %   máximo 5
 *     ├── Criterio «Metodología»     peso 35 %   máximo 5
 *     └── Criterio «Presentación»    peso 25 %   máximo 5
 *                                    ─────────
 *                                    100 %  ← condición para estar completa
 *
 * Tabla asociada: rubricas
 *
 * @property int         $id
 * @property string      $nombre       Nombre visible de la rúbrica.
 * @property string      $descripcion  Para qué tipo de proyecto está pensada.
 * @property int|null    $evento_id    Evento al que queda ligada, si no es genérica.
 * @property string|null $modalidad    Modalidad de participación a la que queda ligada, si no es genérica.
 *
 * @property-read float $total_pesos     Suma de los pesos de sus criterios.
 * @property-read bool  $completa        Si los pesos suman 100 %.
 * @property-read int   $puntaje_maximo  Nota más alta que admite.
 */
class Rubrica extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * Los criterios no están aquí: se crean por separado a través de la
     * relación, porque cada uno es un registro propio en otra tabla.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'evento_id',
        'modalidad',
    ];

    /**
     * Sin esto, «total_pesos» y «completa» sólo existían en el PHP: nunca
     * llegaban al JSON que recibe la pantalla, así que el listado mostraba
     * el porcentaje vacío y todas las rúbricas como «Incompleta», aunque
     * sus pesos sí sumaran 100.
     *
     * @var array<int, string>
     */
    protected $appends = ['total_pesos', 'completa'];

    /**
     * Criterios que componen esta rúbrica.
     *
     * @return HasMany Relación de uno a muchos con CriterioRubrica.
     */
    public function criterios(): HasMany
    {
        return $this->hasMany(CriterioRubrica::class);
    }

    /**
     * Evaluaciones que se han realizado usando esta rúbrica.
     *
     * Sirve para saber si una rúbrica ya se usó: modificar los pesos de una
     * rúbrica con evaluaciones hechas cambiaría notas ya publicadas.
     *
     * @return HasMany Relación de uno a muchos con Evaluacion.
     */
    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class);
    }

    /**
     * Evento al que queda ligada esta rúbrica, si no es genérica.
     *
     * @return BelongsTo Relación de pertenencia con Evento.
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    /**
     * Filtro reutilizable: rúbricas utilizables para calificar una propuesta
     * de un evento y una modalidad concretos.
     *
     * Prioriza las rúbricas específicas de ese evento+modalidad; si no hay
     * ninguna, incluye también las genéricas (sin evento ni modalidad
     * asignados) como respaldo, para no dejar al jurado sin ninguna opción.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int|null     $eventoId    Evento de la propuesta a calificar.
     * @param  string|null  $modalidad   Modalidad de la propuesta a calificar.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeParaEventoYModalidad($query, ?int $eventoId, ?string $modalidad)
    {
        return $query->where(function ($q) use ($eventoId, $modalidad) {
            $q->where(function ($q2) use ($eventoId, $modalidad) {
                $q2->where('evento_id', $eventoId)->where('modalidad', $modalidad);
            })->orWhere(function ($q2) {
                $q2->whereNull('evento_id')->whereNull('modalidad');
            });
        });
    }

    /**
     * Suma de los pesos de todos los criterios.
     *
     * Se accede como propiedad: $rubrica->total_pesos
     *
     * @return float Suma de los pesos, idealmente 100.
     */
    public function getTotalPesosAttribute(): float
    {
        return $this->criterios->sum('peso');
    }

    /**
     * Indica si la rúbrica está lista para usarse.
     *
     * Se accede como propiedad: $rubrica->completa
     *
     * @return bool Verdadero si los pesos suman 100 %.
     */
    public function getCompletaAttribute(): bool
    {
        // No se compara con «== 100» sino con un margen de 0,01. Los pesos se
        // guardan como decimales y en coma flotante una suma como
        // 33,33 + 33,33 + 33,34 puede dar 99,99999999 en lugar de 100 exacto.
        // Sin esta tolerancia, rúbricas correctas aparecerían como incompletas.
        return abs($this->total_pesos - 100.0) < 0.01;
    }

    /**
     * Nota más alta que puede darse con esta rúbrica.
     *
     * Se accede como propiedad: $rubrica->puntaje_maximo
     *
     * @return int Puntaje máximo; 5 si la rúbrica aún no tiene criterios.
     */
    public function getPuntajeMaximoAttribute(): int
    {
        // 5 es sólo un valor por defecto razonable para un criterio sin
        // configurar (no la escala final, que es institucional y va de 0 a
        // 100): evita devolver null cuando la rúbrica está recién creada y
        // todavía no se le han añadido criterios.
        return $this->criterios->max('puntaje_maximo') ?? 5;
    }
}
