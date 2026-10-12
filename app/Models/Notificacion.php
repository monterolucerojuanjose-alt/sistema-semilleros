<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ============================================================================
 * MODELO: NOTIFICACIÓN
 * ============================================================================
 *
 * Aviso dirigido a un usuario dentro del sistema: la aprobación de una
 * solicitud, la publicación de una nota, el recordatorio de un evento…
 *
 * Cubre las historias de usuario HU-09, HU-37 y HU-38. Hay dos orígenes:
 *
 *   - Automáticas: las genera el propio sistema ante un hecho (el remitente
 *     queda vacío).
 *   - Manuales: las escribe una persona, normalmente un coordinador que
 *     avisa a un grupo (el remitente identifica a quien las envió).
 *
 * Tabla asociada: notificaciones
 *
 * @property int    $id
 * @property int    $usuario_id         Destinatario.
 * @property int    $remitente_id       Quién la envió; vacío si fue el sistema.
 * @property string $titulo
 * @property string $mensaje
 * @property string $tipo               info, exito, alerta o error.
 * @property bool   $leida
 * @property \Carbon\Carbon $leida_en   Cuándo se leyó.
 * @property array  $datos_adicionales  Datos extra, como el enlace a abrir.
 *
 * @property-read string $color  Color asociado al tipo.
 * @property-read string $icono  Icono asociado al tipo.
 */
class Notificacion extends Model
{
    use HasFactory;

    /**
     * Nombre real de la tabla.
     *
     * @var string
     */
    protected $table = 'notificaciones';

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario_id',
        'remitente_id',
        'titulo',
        'mensaje',
        'tipo',
        'leida',
        'leida_en',
        'datos_adicionales',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * Convertir «leida» a booleano importa: en MySQL se guarda como 0 o 1, y
     * sin esta conversión la pantalla recibiría un número. En JavaScript el
     * número 0 no es exactamente falso en todas las comparaciones, y eso
     * provocaba que avisos ya leídos siguieran apareciendo como nuevos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'leida' => 'boolean',
        'leida_en' => 'datetime',
        'datos_adicionales' => 'array',
    ];

    /**
     * Usuario que recibe la notificación.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Usuario que la envió.
     *
     * Queda vacío cuando el aviso lo genera el propio sistema, que es lo que
     * permite distinguir en pantalla entre «El sistema te informa» y
     * «María González te informa».
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function remitente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    /**
     * Filtro reutilizable: notificaciones sin leer.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    /**
     * Filtro reutilizable: notificaciones ya leídas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopeLeidas($query)
    {
        return $query->where('leida', true);
    }

    /**
     * Filtro reutilizable: notificaciones de un tipo concreto.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string  $tipo  Tipo a filtrar.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Filtro reutilizable: notificaciones de un usuario concreto.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  int  $usuarioId  Identificador del destinatario.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Marca la notificación como leída y anota el momento.
     *
     * @return void
     */
    public function marcarComoLeida()
    {
        // Si ya estaba leída no se hace nada. Así se conserva la hora de la
        // primera lectura, que es el dato con valor; y se evita una escritura
        // inútil en la base cada vez que el usuario vuelve a abrir el aviso.
        if ($this->leida) {
            return;
        }

        $this->update(['leida' => true, 'leida_en' => now()]);
    }

    /**
     * Color con el que se muestra la notificación.
     *
     * Se accede como propiedad: $notificacion->color
     *
     * @return string Nombre del color.
     */
    public function getColorAttribute()
    {
        return match($this->tipo) {
            'info' => 'blue',
            'exito' => 'green',
            'alerta' => 'yellow',
            'error' => 'red',
            default => 'gray'
        };
    }

    /**
     * Icono con el que se muestra la notificación.
     *
     * Se accede como propiedad: $notificacion->icono
     *
     * @return string Nombre del icono.
     */
    public function getIconoAttribute()
    {
        return match($this->tipo) {
            'info' => 'information-circle',
            'exito' => 'check-circle',
            'alerta' => 'exclamation-triangle',
            'error' => 'x-circle',
            default => 'bell'
        };
    }

    /**
     * Crea una notificación para un único usuario.
     *
     * @param  int         $usuarioId         Destinatario.
     * @param  string      $titulo            Título del aviso.
     * @param  string      $mensaje           Cuerpo del aviso.
     * @param  string      $tipo              info, exito, alerta o error.
     * @param  array|null  $datosAdicionales  Datos extra, como un enlace.
     * @param  int|null    $remitenteId       Quién envía; vacío si es el sistema.
     * @return static La notificación creada.
     */
    public static function crear($usuarioId, $titulo, $mensaje, $tipo = 'info', $datosAdicionales = null, $remitenteId = null)
    {
        return self::create([
            'usuario_id' => $usuarioId,
            'remitente_id' => $remitenteId,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'datos_adicionales' => $datosAdicionales,
        ]);
    }

    /**
     * Crea la misma notificación para varios usuarios a la vez.
     *
     * Se usa en los envíos masivos. La inserción va por lotes y no de una en
     * una: avisar a doscientos estudiantes con create() lanzaría doscientas
     * consultas y la petición acabaría agotando el tiempo de espera.
     *
     * @param  iterable    $usuarioIds        Destinatarios.
     * @param  string      $titulo            Título del aviso.
     * @param  string      $mensaje           Cuerpo del aviso.
     * @param  string      $tipo              info, exito, alerta o error.
     * @param  array|null  $datosAdicionales  Datos extra.
     * @param  int|null    $remitenteId       Quién envía.
     * @return int Número de notificaciones creadas.
     */
    public static function crearParaUsuarios($usuarioIds, $titulo, $mensaje, $tipo = 'info', $datosAdicionales = null, $remitenteId = null)
    {
        // «unique» descarta destinatarios repetidos: alguien que pertenezca a
        // dos grupos del envío recibiría el mismo aviso dos veces.
        $usuarioIds = collect($usuarioIds)->unique()->values();

        if ($usuarioIds->isEmpty()) {
            return 0;
        }

        $ahora = now();

        // La conversión a JSON se hace aquí a mano porque insert() escribe
        // directamente en la base y no pasa por el modelo, de modo que la
        // conversión automática declarada en $casts no llega a aplicarse.
        $datos = is_array($datosAdicionales) ? json_encode($datosAdicionales) : $datosAdicionales;

        // Lotes de 500 para no superar el límite de tamaño de una consulta en
        // MySQL. Con un único insert de varios miles de filas, el servidor
        // rechazaría la sentencia por exceder el paquete máximo permitido.
        $usuarioIds->chunk(500)->each(function ($lote) use ($titulo, $mensaje, $tipo, $datos, $remitenteId, $ahora) {
            self::insert($lote->map(fn ($usuarioId) => [
                'usuario_id' => $usuarioId,
                'remitente_id' => $remitenteId,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'tipo' => $tipo,
                'leida' => false,
                'datos_adicionales' => $datos,

                // Las marcas de tiempo se escriben a mano por el mismo motivo
                // que el JSON: insert() no las rellena de forma automática.
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->all());
        });

        return $usuarioIds->count();
    }

    /**
     * Crea la misma notificación para todos los usuarios de un rol.
     *
     * Por ejemplo, avisar a todos los jurados de que se abrió una evaluación.
     *
     * @param  string      $rol               Nombre exacto del rol.
     * @param  string      $titulo            Título del aviso.
     * @param  string      $mensaje           Cuerpo del aviso.
     * @param  string      $tipo              info, exito, alerta o error.
     * @param  array|null  $datosAdicionales  Datos extra.
     * @param  int|null    $remitenteId       Quién envía.
     * @return int Número de notificaciones creadas.
     */
    public static function crearParaRol($rol, $titulo, $mensaje, $tipo = 'info', $datosAdicionales = null, $remitenteId = null)
    {
        // Se piden solo los identificadores con «pluck» en lugar de los
        // usuarios completos: para crear los avisos no hace falta cargar en
        // memoria el nombre, el correo y los demás campos de cada persona.
        $usuarios = User::whereHas('roles', fn ($query) => $query->where('name', $rol))
            ->pluck('id')
            ->all();

        return self::crearParaUsuarios($usuarios, $titulo, $mensaje, $tipo, $datosAdicionales, $remitenteId);
    }
}
