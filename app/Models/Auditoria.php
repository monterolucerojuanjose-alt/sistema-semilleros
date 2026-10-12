<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ============================================================================
 * MODELO: REGISTRO DE AUDITORÍA
 * ============================================================================
 *
 * Deja constancia de quién hizo qué, cuándo y desde dónde.
 *
 * Responde a la historia de usuario HU-07: ante cualquier duda («¿quién
 * cambió esta nota?», «¿quién borró este proyecto?») debe existir un rastro
 * consultable. Por eso cada registro guarda no solo la acción, sino también
 * el estado de los datos antes y después del cambio.
 *
 * Los registros de esta tabla no se modifican ni se borran desde la
 * aplicación: un historial que se puede alterar no sirve como evidencia.
 *
 * Tabla asociada: auditorias
 *
 * @property int    $id
 * @property int    $usuario_id        Quién realizó la acción.
 * @property string $accion            Qué hizo (create, update, delete, login…).
 * @property string $entidad_afectada  Sobre qué tipo de dato actuó.
 * @property int    $entidad_id        Identificador del registro afectado.
 * @property string $detalle           Descripción legible de lo ocurrido.
 * @property string $ip_address        Desde qué dirección de red se conectó.
 * @property string $user_agent        Con qué navegador y sistema operativo.
 * @property array  $datos_antes       Cómo estaba el registro antes del cambio.
 * @property array  $datos_despues     Cómo quedó después.
 *
 * @property-read string $icono_accion  Nombre del icono para la pantalla.
 * @property-read string $color_accion  Color asociado a la acción.
 */
class Auditoria extends Model
{
    use HasFactory;

    /**
     * Nombre real de la tabla.
     *
     * @var string
     */
    protected $table = 'auditorias';

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario_id',
        'accion',
        'entidad_afectada',
        'entidad_id',
        'detalle',
        'ip_address',
        'user_agent',
        'datos_antes',
        'datos_despues',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * Los dos campos de datos se guardan como texto JSON en la base y se
     * convierten a arreglo al leerlos. Así se puede almacenar el registro
     * completo, con todas sus columnas, sin necesidad de una tabla por cada
     * tipo de entidad auditada.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'datos_antes' => 'array',
        'datos_despues' => 'array',
    ];

    /**
     * Usuario que realizó la acción.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Filtro reutilizable: registros de un tipo de acción.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string  $accion  Acción a filtrar («create», «delete»…).
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    /**
     * Filtro reutilizable: registros sobre un tipo de entidad.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string  $entidad  Entidad a filtrar («Proyecto», «Usuario»…).
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorEntidad($query, $entidad)
    {
        return $query->where('entidad_afectada', $entidad);
    }

    /**
     * Filtro reutilizable: acciones de un usuario concreto.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int  $usuarioId  Identificador del usuario.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Filtro reutilizable: registros dentro de un rango de fechas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  mixed  $fechaInicio  Fecha desde la que buscar.
     * @param  mixed  $fechaFin     Fecha hasta la que buscar.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    /**
     * Icono que representa la acción en la pantalla de auditoría.
     *
     * Se accede como propiedad: $auditoria->icono_accion
     *
     * La correspondencia vive en el modelo y no en la pantalla para que el
     * listado, los informes y el PDF muestren siempre el mismo icono.
     *
     * @return string Nombre del icono.
     */
    public function getIconoAccionAttribute()
    {
        return match($this->accion) {
            'create' => 'plus-circle',
            'update' => 'pencil',
            'delete' => 'trash',
            'login' => 'login',
            'logout' => 'logout',
            'download' => 'download',
            'export' => 'document-download',
            'approve' => 'check-circle',
            'reject' => 'x-circle',
            // Icono neutro para acciones nuevas que aún no tengan uno propio,
            // de modo que añadir una acción no deje huecos en la interfaz.
            default => 'document-text'
        };
    }

    /**
     * Color con el que se resalta la acción en pantalla.
     *
     * Se accede como propiedad: $auditoria->color_accion
     *
     * El código de color es intencionado: verde para lo que crea o aprueba,
     * rojo para lo que elimina o rechaza, azul para lo que solo consulta o
     * modifica. Permite localizar de un vistazo las acciones delicadas.
     *
     * @return string Nombre del color.
     */
    public function getColorAccionAttribute()
    {
        return match($this->accion) {
            'create' => 'green',
            'update' => 'blue',
            'delete' => 'red',
            'login' => 'green',
            'logout' => 'gray',
            'download' => 'blue',
            'export' => 'purple',
            'approve' => 'green',
            'reject' => 'red',
            default => 'gray'
        };
    }

    /**
     * Crea un registro de auditoría.
     *
     * Es el punto único por el que se registra toda la actividad del sistema.
     * Los observadores y controladores llaman a este método en lugar de crear
     * el registro a mano, para que ninguno olvide guardar un campo.
     *
     * @param  int|null    $usuarioId        Quién realiza la acción.
     * @param  string      $accion           Qué acción se realiza.
     * @param  string      $entidadAfectada  Sobre qué tipo de dato.
     * @param  int|null    $entidadId        Identificador del registro afectado.
     * @param  string|null $detalle          Descripción legible.
     * @param  array|null  $datosAntes       Estado previo del registro.
     * @param  array|null  $datosDespues     Estado posterior del registro.
     * @param  string|null $ipAddress        Dirección de red; se detecta sola si se omite.
     * @param  string|null $userAgent        Navegador; se detecta solo si se omite.
     * @return static El registro de auditoría creado.
     */
    public static function registrar(
        $usuarioId,
        $accion,
        $entidadAfectada,
        $entidadId = null,
        $detalle = null,
        $datosAntes = null,
        $datosDespues = null,
        $ipAddress = null,
        $userAgent = null
    ) {
        return self::create([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'entidad_afectada' => $entidadAfectada,
            'entidad_id' => $entidadId,
            'detalle' => $detalle,
            'datos_antes' => $datosAntes,
            'datos_despues' => $datosDespues,

            // Si no se indican, la dirección y el navegador se toman de la
            // petición actual. Se admite pasarlos a mano porque las tareas en
            // segundo plano se ejecutan sin petición asociada, y allí
            // request() no devolvería nada útil.
            'ip_address' => $ipAddress ?: request()->ip(),
            'user_agent' => $userAgent ?: request()->userAgent(),
        ]);
    }

    /**
     * Resumen estadístico de la actividad reciente.
     *
     * Alimenta el panel de auditoría con cuatro cifras: total de acciones y
     * desglose por tipo de acción, por entidad y por usuario.
     *
     * @param  int  $dias  Número de días hacia atrás a considerar.
     * @return array<string, mixed> Resumen con los cuatro bloques de datos.
     */
    public static function obtenerResumen($dias = 30)
    {
        $fechaInicio = now()->subDays($dias);

        return [
            'total_actividades' => self::where('created_at', '>=', $fechaInicio)->count(),

            // Los agrupamientos se resuelven en la base de datos con GROUP BY
            // en lugar de traer todos los registros y contarlos en PHP. Con
            // cientos de miles de filas, la diferencia es entre una consulta
            // instantánea y agotar la memoria del servidor.
            'actividades_por_accion' => self::where('created_at', '>=', $fechaInicio)
                ->selectRaw('accion, COUNT(*) as total')
                ->groupBy('accion')
                ->pluck('total', 'accion'),

            'actividades_por_entidad' => self::where('created_at', '>=', $fechaInicio)
                ->selectRaw('entidad_afectada, COUNT(*) as total')
                ->groupBy('entidad_afectada')
                ->pluck('total', 'entidad_afectada'),

            // Solo los diez usuarios más activos: el panel muestra un ranking,
            // no la lista completa.
            'actividades_por_usuario' => self::where('created_at', '>=', $fechaInicio)
                ->with('usuario:id,name')
                ->selectRaw('usuario_id, COUNT(*) as total')
                ->groupBy('usuario_id')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get(),
        ];
    }
}
