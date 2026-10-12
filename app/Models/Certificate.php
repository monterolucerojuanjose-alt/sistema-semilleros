<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODELO: CERTIFICADO (SISTEMA ACTUAL)
 * ============================================================================
 *
 * Constancia oficial que acredita la participación de una persona en un
 * evento: como asistente, ponente, organizador o voluntario.
 *
 * ⚠️  AVISO IMPORTANTE — CONVIVEN DOS SISTEMAS DE CERTIFICADOS
 *
 *     Este modelo (Certificate → tabla «certificates») es el que usa la
 *     pantalla de certificados para emitir los nuevos. Es el sistema vigente
 *     y el más completo: registra quién emitió el certificado, permite
 *     revocarlo y admite adjuntar el PDF firmado.
 *
 *     Existe además el modelo Certificado (→ tabla «certificados»), anterior,
 *     que es el que siguen consultando el panel principal y los informes.
 *
 *     Consecuencia práctica: los certificados emitidos desde la pantalla no
 *     se reflejan en las cifras del panel ni en los informes, porque esas
 *     pantallas cuentan la otra tabla.
 *
 *     Unificar ambos sistemas exige migrar los datos y reapuntar las
 *     consultas del panel y de los informes. Ver también Certificado.
 *
 * A diferencia del sistema anterior, aquí el PDF no se genera a partir de una
 * plantilla: se sube ya elaborado y firmado, y el modelo guarda su ruta.
 *
 * Tabla asociada: certificates
 *
 * @property int    $id
 * @property string $codigo               Código único de verificación.
 * @property string $titulo
 * @property string $descripcion
 * @property string $tipo                 participacion, ponencia, poster…
 * @property \Carbon\Carbon $fecha_emision
 * @property string $estado               «activo» o «revocado».
 * @property int    $user_id              Persona que lo recibe.
 * @property int    $evento_id            Evento al que corresponde.
 * @property int    $emitido_por          Quién lo expidió.
 * @property string $archivo_certificado  Ruta del PDF.
 * @property array  $metadata             Datos extra en formato libre.
 * @property string $observaciones
 *
 * @property-read string|null $url_archivo       Dirección pública del PDF.
 * @property-read string      $tipo_formateado   Tipo con formato de presentación.
 * @property-read string      $estado_formateado Estado con formato de presentación.
 */
class Certificate extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'titulo',
        'descripcion',
        'tipo',
        'fecha_emision',
        'estado',
        'user_id',
        'evento_id',
        'emitido_por',
        'archivo_certificado',
        'metadata',
        'observaciones',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * La fecha de emisión se convierte a «date» y no a «datetime» porque de
     * un certificado interesa el día, no la hora exacta.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_emision' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Persona que recibe el certificado.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Evento al que corresponde el certificado.
     *
     * @return BelongsTo Relación de pertenencia con Evento.
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    /**
     * Persona que expidió el certificado.
     *
     * Es un dato de responsabilidad: deja constancia de quién avaló la
     * constancia, no solo de quién la recibió.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function emisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }

    /**
     * Filtro reutilizable: certificados vigentes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Filtro reutilizable: certificados de una persona.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int  $userId  Identificador del titular.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filtro reutilizable: certificados de un evento.
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
     * Filtro reutilizable: búsqueda por el código exacto.
     *
     * Es la consulta que resuelve la verificación de autenticidad: quien
     * recibe un certificado puede comprobar su código contra el sistema.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string  $codigo  Código a buscar.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo', $codigo);
    }

    /**
     * Filtro reutilizable: búsqueda por texto libre.
     *
     * Busca en el título, el código y la descripción del certificado, y
     * además en el nombre y correo del titular y en el título del evento.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string|null  $buscar  Texto a buscar; si viene vacío no filtra.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeBuscar($query, $buscar)
    {
        if (empty($buscar)) {
            return $query;
        }

        // Las condiciones se agrupan dentro de una función para que los «or»
        // no se mezclen con el resto de filtros ya aplicados a la consulta.
        return $query->where(function ($q) use ($buscar) {
            $q->where('titulo', 'like', "%{$buscar}%")
              ->orWhere('codigo', 'like', "%{$buscar}%")
              ->orWhere('descripcion', 'like', "%{$buscar}%")
              ->orWhereHas('usuario', function ($userQuery) use ($buscar) {
                  $userQuery->where('name', 'like', "%{$buscar}%")
                           ->orWhere('email', 'like', "%{$buscar}%");
              })
              ->orWhereHas('evento', function ($eventoQuery) use ($buscar) {
                  $eventoQuery->where('titulo', 'like', "%{$buscar}%");
              });
        });
    }

    /**
     * Filtro reutilizable: certificados de un tipo.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string|null  $tipo  Tipo a filtrar; si viene vacío no filtra.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorTipo($query, $tipo)
    {
        if (empty($tipo)) {
            return $query;
        }

        return $query->where('tipo', $tipo);
    }

    /**
     * Filtro reutilizable: certificados en un estado.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string|null  $estado  Estado a filtrar; si viene vacío no filtra.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorEstado($query, $estado)
    {
        if (empty($estado)) {
            return $query;
        }

        return $query->where('estado', $estado);
    }

    /**
     * Filtro reutilizable: certificados emitidos en un rango de fechas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  mixed  $fechaInicio  Fecha desde la que buscar; opcional.
     * @param  mixed  $fechaFin     Fecha hasta la que buscar; opcional.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorFecha($query, $fechaInicio, $fechaFin)
    {
        if (!empty($fechaInicio)) {
            $query->where('fecha_emision', '>=', $fechaInicio);
        }

        if (!empty($fechaFin)) {
            $query->where('fecha_emision', '<=', $fechaFin);
        }

        return $query;
    }

    /**
     * Genera un código de verificación único.
     *
     * @return string Código con el formato CERT-XXXXXXXXXXXXX.
     */
    public static function generarCodigo(): string
    {
        // El bucle garantiza que el código no exista ya. Dos certificados con
        // el mismo código harían inútil la verificación de autenticidad.
        do {
            $codigo = 'CERT-' . strtoupper(uniqid());
        } while (self::where('codigo', $codigo)->exists());

        return $codigo;
    }

    /**
     * Indica si el certificado sigue siendo válido.
     *
     * Un certificado revocado se conserva en el sistema en lugar de borrarse,
     * para que al verificar su código pueda responderse «existe, pero fue
     * anulado» en lugar de «no existe».
     *
     * @return bool Verdadero si el estado es «activo».
     */
    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Dirección pública del PDF del certificado.
     *
     * Se accede como propiedad: $certificate->url_archivo
     *
     * @return string|null Dirección del archivo, o null si no se adjuntó ninguno.
     */
    public function getUrlArchivoAttribute(): ?string
    {
        return $this->archivo_certificado ? asset('storage/' . $this->archivo_certificado) : null;
    }

    /**
     * Tipo de certificado con el formato con el que se muestra en pantalla.
     *
     * Se accede como propiedad: $certificate->tipo_formateado
     *
     * @return string Tipo formateado.
     */
    public function getTipoFormateadoAttribute(): string
    {
        return match($this->tipo) {
            'participacion' => 'Participación',
            'ponencia' => 'Ponencia',
            'poster' => 'Póster',
            'organizacion' => 'Organización',
            'voluntariado' => 'Voluntariado',
            // «ucfirst» pone en mayúscula la primera letra de cualquier tipo
            // no contemplado, para que al menos se lea con dignidad.
            default => ucfirst($this->tipo),
        };
    }

    /**
     * Estado con el formato con el que se muestra en pantalla.
     *
     * Se accede como propiedad: $certificate->estado_formateado
     *
     * @return string Estado formateado.
     */
    public function getEstadoFormateadoAttribute(): string
    {
        return match($this->estado) {
            'activo' => 'Activo',
            'revocado' => 'Revocado',
            default => ucfirst($this->estado),
        };
    }
}
