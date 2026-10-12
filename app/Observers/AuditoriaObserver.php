<?php

namespace App\Observers;

use App\Models\Auditoria;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Auth;

class AuditoriaObserver
{
    /**
     * Registrar actividad cuando se crea un modelo
     */
    public function created($model)
    {
        if (Auth::check()) {
            $this->registrarActividad('create', $model);
        }
    }

    /**
     * Registrar actividad cuando se actualiza un modelo
     */
    public function updated($model)
    {
        if (Auth::check()) {
            $this->registrarActividad('update', $model);
        }
    }

    /**
     * Registrar actividad cuando se elimina un modelo
     */
    public function deleted($model)
    {
        if (Auth::check()) {
            $this->registrarActividad('delete', $model);
        }
    }

    /**
     * Registrar actividad cuando se restaura un modelo
     */
    public function restored($model)
    {
        if (Auth::check()) {
            $this->registrarActividad('restore', $model);
        }
    }

    /**
     * Registrar actividad cuando se elimina permanentemente un modelo
     */
    public function forceDeleted($model)
    {
        if (Auth::check()) {
            $this->registrarActividad('force_delete', $model);
        }
    }

    /**
     * Registrar actividad de auditoría
     */
    private function registrarActividad($accion, $model)
    {
        $entidad = class_basename($model);
        $entidadId = $model->id ?? null;

        // Obtener detalles del cambio para updates
        $datosAntes = null;
        $datosDespues = null;
        $detalle = null;

        if ($accion === 'update' && $model->wasChanged()) {
            $cambios = $model->getChanges();
            $originales = $model->getOriginal();

            $datosAntes = array_intersect_key($originales, $cambios);
            $datosDespues = $cambios;

            $detalle = $this->generarDetalleCambios($cambios, $originales);
        } elseif ($accion === 'create') {
            $datosDespues = $model->getAttributes();
            $detalle = "Nuevo registro creado: {$entidad}";
        } elseif ($accion === 'delete') {
            $datosAntes = $model->getOriginal();
            $detalle = "Registro eliminado: {$entidad}";
        }

        // Crear registro de auditoría
        Auditoria::registrar(
            Auth::id(),
            $accion,
            $entidad,
            $entidadId,
            $detalle,
            $datosAntes,
            $datosDespues
        );

        // Crear notificaciones específicas según el tipo de actividad
        $this->crearNotificacionesEspecificas($accion, $model, $entidad);
    }

    /**
     * Generar detalle de los cambios realizados
     */
    private function generarDetalleCambios($cambios, $originales)
    {
        $detalles = [];

        foreach ($cambios as $campo => $nuevoValor) {
            $valorAnterior = $originales[$campo] ?? null;

            // Formatear valores especiales
            if ($campo === 'estado') {
                $detalles[] = "Estado: {$valorAnterior} → {$nuevoValor}";
            } elseif ($campo === 'password') {
                $detalles[] = "Contraseña actualizada";
            } elseif (in_array($campo, ['updated_at', 'created_at'])) {
                continue; // Ignorar timestamps automáticos
            } else {
                $detalles[] = ucfirst(str_replace('_', ' ', $campo)) . ": {$valorAnterior} → {$nuevoValor}";
            }
        }

        return implode(', ', $detalles);
    }

    /**
     * Crear notificaciones específicas según el tipo de actividad
     */
    private function crearNotificacionesEspecificas($accion, $model, $entidad)
    {
        $usuarioActual = Auth::user();

        switch ($entidad) {
            case 'Proyecto':
                $this->notificarCambiosProyecto($accion, $model, $usuarioActual);
                break;
            case 'InscripcionEvento':
                $this->notificarCambiosInscripcion($accion, $model, $usuarioActual);
                break;
            case 'Evaluacion':
                $this->notificarCambiosEvaluacion($accion, $model, $usuarioActual);
                break;
            case 'User':
                $this->notificarCambiosUsuario($accion, $model, $usuarioActual);
                break;
            case 'Semillero':
                $this->notificarCambiosSemillero($accion, $model, $usuarioActual);
                break;
            case 'EventoParticipante':
                $this->notificarCambiosEventoParticipante($accion, $model, $usuarioActual);
                break;
            case 'Certificate':
                $this->notificarCambiosCertificado($accion, $model, $usuarioActual);
                break;
        }
    }

    /**
     * Notificaciones para cambios en proyectos
     */
    private function notificarCambiosProyecto($accion, $model, $usuarioActual)
    {
        $titulo = '';
        $mensaje = '';
        $tipo = 'info';

        switch ($accion) {
            case 'create':
                $titulo = 'Nuevo Proyecto Creado';
                $mensaje = "Se ha creado el proyecto '{$model->titulo}'";
                $tipo = 'exito';
                break;
            case 'update':
                if ($model->wasChanged('estado')) {
                    $estadoAnterior = $model->getOriginal('estado');
                    $nuevoEstado = $model->estado;

                    $titulo = 'Estado de Proyecto Actualizado';
                    $mensaje = "El proyecto '{$model->titulo}' cambió de {$estadoAnterior} a {$nuevoEstado}";

                    if ($nuevoEstado === 'Aprobado') {
                        $tipo = 'exito';
                    } elseif ($nuevoEstado === 'Rechazado') {
                        $tipo = 'error';
                    } else {
                        $tipo = 'info';
                    }
                }
                break;
        }

        if ($titulo && $mensaje) {
            // Notificar al autor del proyecto (si no es el mismo usuario)
            if ($model->autor_id && $model->autor_id !== $usuarioActual->id) {
                Notificacion::crear(
                    $model->autor_id,
                    $titulo,
                    $mensaje,
                    $tipo,
                    ['proyecto_id' => $model->id, 'proyecto_titulo' => $model->titulo]
                );
            }

            // Notificar a coordinadores del semillero
            if ($model->semillero_id) {
                Notificacion::crearParaRol(
                    'Coordinador',
                    $titulo,
                    $mensaje,
                    $tipo,
                    ['proyecto_id' => $model->id, 'proyecto_titulo' => $model->titulo]
                );
            }
        }
    }

    /**
     * Notificaciones para cambios en inscripciones
     */
    private function notificarCambiosInscripcion($accion, $model, $usuarioActual)
    {
        if ($accion === 'update' && $model->wasChanged('estado')) {
            $estadoAnterior = $model->getOriginal('estado');
            $nuevoEstado = $model->estado;

            $titulo = 'Estado de Inscripción Actualizado';
            $mensaje = "Tu inscripción ha cambiado de {$estadoAnterior} a {$nuevoEstado}";

            $tipo = match($nuevoEstado) {
                'Aceptado' => 'exito',
                'Rechazado' => 'error',
                default => 'info'
            };

            // Notificar al usuario de la inscripción
            if ($model->proyecto && $model->proyecto->autor_id) {
                Notificacion::crear(
                    $model->proyecto->autor_id,
                    $titulo,
                    $mensaje,
                    $tipo,
                    ['inscripcion_id' => $model->id]
                );
            }
        }
    }

    /**
     * Notificaciones para cambios en evaluaciones
     */
    private function notificarCambiosEvaluacion($accion, $model, $usuarioActual)
    {
        if ($accion === 'create' || ($accion === 'update' && $model->wasChanged('estado'))) {
            $titulo = 'Nueva Evaluación';
            $mensaje = "Se ha evaluado tu proyecto";
            $tipo = 'info';

            if ($model->proyecto && $model->proyecto->autor_id) {
                Notificacion::crear(
                    $model->proyecto->autor_id,
                    $titulo,
                    $mensaje,
                    $tipo,
                    ['evaluacion_id' => $model->id, 'proyecto_titulo' => $model->proyecto->titulo ?? 'N/A']
                );
            }
        }
    }

    /**
     * Notificaciones para cambios en usuarios
     */
    private function notificarCambiosUsuario($accion, $model, $usuarioActual)
    {
        if ($accion === 'update' && $model->wasChanged('estado')) {
            $titulo = 'Estado de Cuenta Actualizado';
            $mensaje = "El estado de tu cuenta ha sido actualizado";
            $tipo = 'info';

            Notificacion::crear(
                $model->id,
                $titulo,
                $mensaje,
                $tipo
            );
        }
    }

    /**
     * Notificaciones para cambios en semilleros
     */
    private function notificarCambiosSemillero($accion, $model, $usuarioActual)
    {
        if ($accion === 'update' && $model->wasChanged('estado')) {
            $titulo = 'Estado de Semillero Actualizado';
            $mensaje = "El semillero '{$model->nombre}' ha cambiado de estado";
            $tipo = 'info';

            // Notificar a todos los miembros del semillero
            $miembros = $model->usuarios()->pluck('user_id')->toArray();

            if (!empty($miembros)) {
                Notificacion::crearParaUsuarios(
                    $miembros,
                    $titulo,
                    $mensaje,
                    $tipo,
                    ['semillero_id' => $model->id, 'semillero_nombre' => $model->nombre]
                );
            }
        }
    }

    /**
     * Notificaciones para cambios en participantes de eventos
     */
    private function notificarCambiosEventoParticipante($accion, $model, $usuarioActual)
    {
        if ($accion !== 'update' || ! $model->user_id) {
            return;
        }

        $proyecto = $model->titulo_proyecto ?: 'tu propuesta';
        $model->loadMissing('evento');
        $evento = $model->evento?->titulo;

        // Se avisa en lenguaje claro y sólo de lo que le importa al estudiante.
        // Antes el mensaje era el cambio técnico ("sin_evaluar → evaluado").
        $aviso = null;

        if ($model->wasChanged('estado_evaluacion') && $model->estado_evaluacion === 'evaluado') {
            $nota = $model->nota_final !== null
                ? ' La nota obtenida es ' . number_format((float) $model->nota_final, 2) . ' sobre 100.'
                : '';

            $aviso = [
                'titulo' => 'Ya calificaron tu proyecto',
                'mensaje' => "El jurado evaluó «{$proyecto}»." . $nota
                    . ' Puedes ver las observaciones en "Mis notas".',
                'tipo' => $model->nota_final !== null && (float) $model->nota_final >= 60 ? 'exito' : 'info',
            ];
        } elseif ($model->wasChanged('estado') && $model->estado === 'aprobado') {
            $detalles = array_filter([
                $evento ? "Evento: {$evento}" : null,
                $model->aula ? "Aula: {$model->aula}" : null,
                $model->horario ? "Horario: {$model->horario}" : null,
            ]);

            $aviso = [
                'titulo' => 'Tu propuesta fue aprobada',
                'mensaje' => "«{$proyecto}» fue aceptada para presentarse."
                    . ($detalles ? ' ' . implode(' · ', $detalles) . '.' : ''),
                'tipo' => 'exito',
            ];
        } elseif ($model->wasChanged('estado') && $model->estado === 'rechazado') {
            $aviso = [
                'titulo' => 'Tu propuesta no fue aceptada',
                'mensaje' => "«{$proyecto}» no fue aceptada."
                    . ($model->motivo_rechazo ? " Motivo: {$model->motivo_rechazo}" : '')
                    . ' Puedes revisarla y volver a presentarla.',
                'tipo' => 'alerta',
            ];
        }

        if ($aviso) {
            Notificacion::crear(
                $model->user_id,
                $aviso['titulo'],
                $aviso['mensaje'],
                $aviso['tipo'],
                ['evento_id' => $model->evento_id, 'evento_participante_id' => $model->id],
                $usuarioActual?->id
            );
        }
    }

    /**
     * Notificaciones para certificados
     */
    private function notificarCambiosCertificado($accion, $model, $usuarioActual)
    {
        $titulo = '';
        $mensaje = '';
        $tipo = 'info';

        if ($accion === 'create') {
            $titulo = 'Nuevo certificado emitido';
            $mensaje = "Se te ha emitido el certificado '".($model->titulo ?? 'Certificado')."'";
            $tipo = 'exito';
        } elseif ($accion === 'update' && $model->wasChanged('estado')) {
            $titulo = 'Actualización de certificado';
            $mensaje = "Tu certificado cambió de estado a ".$model->estado;
            $tipo = $model->estado === 'revocado' ? 'alerta' : 'info';
        }

        if ($titulo && $model->user_id) {
            Notificacion::crear(
                $model->user_id,
                $titulo,
                $mensaje,
                $tipo,
                ['certificado_id' => $model->id]
            );
        }
    }
}
