<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ============================================================================
 * MODELO: CERTIFICADO (SISTEMA ANTIGUO)
 * ============================================================================
 *
 * Constancia de participación o de reconocimiento emitida a un usuario.
 *
 * ⚠️  AVISO IMPORTANTE — CONVIVEN DOS SISTEMAS DE CERTIFICADOS
 *
 *     Este modelo (Certificado → tabla «certificados») es el primero que se
 *     construyó. Hoy lo consultan el panel principal (DashboardController) y
 *     el módulo de informes (ReporteController).
 *
 *     Existe además el modelo Certificate (→ tabla «certificates»), que es el
 *     que usa la pantalla de certificados para crear los nuevos.
 *
 *     Consecuencia práctica: los certificados que se emiten desde la pantalla
 *     se guardan en «certificates», pero el panel y los informes cuentan los
 *     de «certificados». Por eso las cifras del panel no reflejan los
 *     certificados recientes.
 *
 *     Ambos modelos están en uso, así que ninguno puede eliminarse sin antes
 *     unificar los datos y reapuntar las consultas. Ver también Certificate.
 *
 * Tabla asociada: certificados
 *
 * @property int    $id
 * @property int    $usuario_id     Persona que recibe el certificado.
 * @property int    $proyecto_id    Proyecto asociado (puede ir vacío).
 * @property string $tipo           Clase de certificado (participación, ponencia…).
 * @property string $url_pdf        Ruta del archivo PDF.
 * @property string $codigo_qr      Código único de verificación.
 * @property \Carbon\Carbon $fecha_emision  Fecha en que se expidió.
 */
class Certificado extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden rellenar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario_id',
        'proyecto_id',
        'tipo',
        'url_pdf',
        'codigo_qr',
        'fecha_emision',
    ];

    /**
     * Conversión automática de tipos de cada campo.
     *
     * Convertir la fecha a objeto Carbon permite darle formato en español
     * («2 de mayo de 2026») sin manipular texto a mano.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_emision' => 'datetime',
    ];

    /**
     * Persona a la que se le emitió el certificado.
     *
     * @return BelongsTo Relación de pertenencia con User.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Proyecto al que corresponde el certificado.
     *
     * Es opcional: hay certificados de asistencia a un evento que no están
     * ligados a ningún proyecto concreto.
     *
     * @return BelongsTo Relación de pertenencia con Proyecto.
     */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /**
     * Filtro reutilizable: certificados de un tipo concreto.
     *
     * Se usa así:  Certificado::porTipo('participacion')->get()
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Consulta en curso.
     * @param  string  $tipo  Tipo de certificado a filtrar.
     * @return \Illuminate\Database\Eloquent\Builder Consulta con el filtro aplicado.
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Filtro reutilizable: certificados de un usuario concreto.
     *
     * Se usa así:  Certificado::porUsuario($user->id)->get()
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
     * Genera un código de verificación único.
     *
     * El código es lo que permite comprobar que un certificado es auténtico:
     * quien lo recibe puede contrastarlo contra el sistema.
     *
     * @return string Código con el formato CERT-XXXXXXXXXXXXX.
     */
    public static function generarCodigoQR(): string
    {
        // uniqid() se basa en la hora actual con precisión de microsegundos,
        // así que repetirse es muy improbable. Aun así se comprueba contra la
        // base de datos dentro de un bucle: dos certificados con el mismo
        // código harían imposible distinguir cuál es cuál al verificarlos.
        do {
            $codigo = 'CERT-' . strtoupper(uniqid());
        } while (self::where('codigo_qr', $codigo)->exists());

        return $codigo;
    }
}
