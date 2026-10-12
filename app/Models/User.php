<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODELO: USUARIO
 * ============================================================================
 *
 * Toda persona que accede al sistema, sea cual sea su rol.
 *
 * No existe una tabla por cada tipo de persona: hay una sola de usuarios, y
 * lo que distingue a un estudiante de un jurado son los roles asignados. Esa
 * decisión permite que alguien acumule varios papeles a la vez, por ejemplo
 * un docente que además actúa como jurado en un evento.
 *
 * Roles del sistema:
 *   - Administrador  Control total.
 *   - Coordinador    Gestiona semilleros, eventos y rúbricas.
 *   - Docente/Tutor  Dirige uno o varios semilleros.
 *   - Jurado         Evalúa proyectos con las rúbricas.
 *   - Estudiante     Participa en semilleros y presenta proyectos.
 *
 * Tabla asociada: users
 *
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $password     Cifrada, nunca en texto legible.
 * @property string $estado       «activo» o «inactivo».
 * @property string $avatar_path  Ruta de la foto de perfil.
 *
 * @property-read string|null $avatar_url  Dirección pública de la foto.
 */
class User extends Authenticatable
{
    /**
     * Comportamientos que el modelo incorpora:
     *   HasFactory  Permite generar usuarios de prueba.
     *   Notifiable  Permite enviarle notificaciones y correos.
     *   HasRoles    Añade los roles y permisos (paquete spatie/laravel-permission),
     *               de donde salen métodos como hasRole() y can().
     */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'rol_id',
        'estado',
        'avatar_path',
    ];

    /**
     * Propiedades calculadas que se añaden a la respuesta enviada a la pantalla.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * Campos que nunca se envían al navegador.
     *
     * La contraseña y el token de sesión quedan fuera por motivos evidentes.
     * «avatar_path» también se oculta porque la pantalla no necesita la ruta
     * interna del archivo, sino la dirección pública que arma avatar_url.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'avatar_path',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * «hashed» es la conversión más importante del modelo: cifra la contraseña
     * automáticamente al asignarla. Gracias a ella es imposible guardar por
     * descuido una contraseña en texto legible.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Semilleros con los que el usuario tiene algún vínculo.
     *
     * Incluye los aprobados y también las solicitudes pendientes o rechazadas.
     *
     * @return BelongsToMany Relación de muchos a muchos con Semillero.
     */
    public function semilleros(): BelongsToMany
    {
        return $this->belongsToMany(Semillero::class, 'semillero_user')
                    ->withPivot(['rol_en_semillero', 'estado_solicitud', 'fecha'])
                    ->withTimestamps();
    }

    /**
     * Proyectos de los que el usuario es autor.
     *
     * @return HasMany Relación de uno a muchos con Proyecto.
     */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'autor_id');
    }

    /**
     * Versiones de documentos que el usuario ha subido.
     *
     * @return HasMany Relación de uno a muchos con VersionProyecto.
     */
    public function versionesProyecto(): HasMany
    {
        return $this->hasMany(VersionProyecto::class, 'creado_por');
    }

    /**
     * Semilleros que el usuario dirige como tutor.
     *
     * @return HasMany Relación de uno a muchos con Semillero.
     */
    public function semillerosComoTutor()
    {
        return $this->hasMany(Semillero::class, 'tutor_id');
    }

    /**
     * Semilleros a los que el usuario pertenece de verdad.
     *
     * Es la relación que debe usarse para decidir qué puede ver: las
     * solicitudes pendientes no dan acceso a nada.
     *
     * @return BelongsToMany Relación de muchos a muchos con Semillero.
     */
    public function semillerosAprobados(): BelongsToMany
    {
        return $this->belongsToMany(Semillero::class, 'semillero_user')
                    ->wherePivot('estado_solicitud', 'aprobado')
                    ->withPivot(['rol_en_semillero', 'estado_solicitud', 'fecha'])
                    ->withTimestamps();
    }

    /**
     * Solicitudes de ingreso del usuario que aún no se han resuelto.
     *
     * @return BelongsToMany Relación de muchos a muchos con Semillero.
     */
    public function solicitudesPendientes(): BelongsToMany
    {
        return $this->belongsToMany(Semillero::class, 'semillero_user')
                    ->wherePivot('estado_solicitud', 'pendiente')
                    ->withPivot(['rol_en_semillero', 'estado_solicitud', 'fecha'])
                    ->withTimestamps();
    }

    /**
     * Certificados emitidos a nombre del usuario.
     *
     * Apunta al modelo Certificado (tabla «certificados»), el sistema antiguo.
     * Los certificados nuevos se crean con el modelo Certificate, que no está
     * enlazado desde aquí. Ver el aviso en la cabecera de Certificado.
     *
     * @return HasMany Relación de uno a muchos con Certificado.
     */
    public function certificados(): HasMany
    {
        return $this->hasMany(Certificado::class, 'usuario_id');
    }

    /**
     * Notificaciones recibidas por el usuario.
     *
     * @return HasMany Relación de uno a muchos con Notificacion.
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    /**
     * Notificaciones que el usuario todavía no ha leído.
     *
     * Reaprovecha el filtro «noLeidas» definido en el modelo Notificacion, de
     * modo que la condición existe en un solo sitio. Alimenta el contador de
     * la campana en la barra superior.
     *
     * @return HasMany Relación de uno a muchos con Notificacion.
     */
    public function notificacionesNoLeidas(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'usuario_id')->noLeidas();
    }

    /**
     * Registros de auditoría generados por el usuario.
     *
     * @return HasMany Relación de uno a muchos con Auditoria.
     */
    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    /**
     * Eventos a los que el usuario se ha inscrito como asistente.
     *
     * @return BelongsToMany Relación de muchos a muchos con Evento.
     */
    public function eventosInscritos(): BelongsToMany
    {
        return $this->belongsToMany(Evento::class, 'evento_participantes')
                    ->withPivot(['estado', 'observaciones'])
                    ->withTimestamps();
    }

    /**
     * Participaciones del usuario en eventos, con todos sus datos.
     *
     * A diferencia de eventosInscritos(), que devuelve los eventos, esta
     * relación devuelve los registros de participación, con su estado y sus
     * observaciones.
     *
     * @return HasMany Relación de uno a muchos con EventoParticipante.
     */
    public function participacionesEventos(): HasMany
    {
        return $this->hasMany(EventoParticipante::class);
    }

    /**
     * Eventos que el usuario ha creado.
     *
     * @return HasMany Relación de uno a muchos con Evento.
     */
    public function eventosCreados(): HasMany
    {
        return $this->hasMany(Evento::class, 'creado_por');
    }

    /**
     * Dirección pública de la foto de perfil.
     *
     * Se accede como propiedad: $user->avatar_url
     *
     * @return string|null Dirección de la imagen, o null si no tiene foto.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        // Sin foto se devuelve null, y es la pantalla la que decide qué
        // mostrar en su lugar (el componente Avatar dibuja las iniciales).
        if (empty($this->avatar_path)) {
            return null;
        }

        $url = asset('storage/'.$this->avatar_path);

        // Se añade la fecha de modificación como parámetro. Es lo que obliga
        // al navegador a descargar la imagen de nuevo tras un cambio de foto:
        // sin este añadido, la dirección sería idéntica a la anterior y el
        // navegador seguiría mostrando la imagen antigua que tiene guardada.
        if ($this->updated_at) {
            $url .= '?v='.$this->updated_at->getTimestamp();
        }

        return $url;
    }
}
