<?php

namespace Tests\Usabilidad;

use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Un estudiante rechazado debe poder corregir su propuesta y enviarla de
 * nuevo. Antes de esta prueba, el sistema devolvía «Tu propuesta para este
 * evento ya fue rechazada. No puedes volver a inscribirte» sin dar ninguna
 * salida: quedaba fuera del evento para siempre por un solo rechazo.
 */
class CorreccionPropuestaTest extends UsabilidadTestCase
{
    private function eventoAbierto(): Evento
    {
        return Evento::create([
            'titulo' => 'Evento de prueba - corrección',
            'descripcion' => 'Evento temporal para la prueba de reenvío.',
            'precio' => 0,
            'fecha_inicio' => now()->addDays(5),
            'fecha_fin' => now()->addDays(6),
            'ubicacion' => 'Aula de pruebas',
            'cupo_maximo' => 10,
            'estado' => 'activo',
            'creado_por' => $this->usuarioConRol('Coordinador')->id,
        ]);
    }

    private function enviarPropuesta(User $estudiante, Evento $evento, string $nombreArchivo = 'propuesta.pdf'): void
    {
        $this->actingAs($estudiante)->post("/eventos/{$evento->id}/inscribir", [
            'titulo_proyecto' => 'Mi proyecto',
            'descripcion_proyecto' => 'Descripción de la propuesta.',
            'archivo_proyecto' => UploadedFile::fake()->create($nombreArchivo, 200, 'application/pdf'),
            'modalidad' => \App\Support\CatalogosEvento::MODALIDADES[0],
            'area_participacion' => \App\Support\CatalogosEvento::AREAS_PARTICIPACION[0],
            'participantes' => [['nombre' => $estudiante->name, 'email' => $estudiante->email]],
        ])->assertSessionHasNoErrors();
    }

    /** El estudiante puede corregir y reenviar una propuesta rechazada. */
    public function test_una_propuesta_rechazada_se_puede_corregir_y_reenviar(): void
    {
        Storage::fake('public');

        $estudiante = $this->usuarioConRol('Estudiante');
        $coordinador = $this->usuarioConRol('Coordinador');
        $evento = $this->eventoAbierto();

        // Primer envío.
        $this->enviarPropuesta($estudiante, $evento, 'primera_version.pdf');

        $propuesta = EventoParticipante::where('evento_id', $evento->id)->where('user_id', $estudiante->id)->firstOrFail();
        $this->assertSame(1, $propuesta->intentos_envio, 'El primer envío debería contar como intento 1.');
        $archivoOriginal = $propuesta->archivo_proyecto;

        // El coordinador la rechaza.
        $this->actingAs($coordinador)
            ->post("/propuestas/{$propuesta->id}/rechazar", ['motivo_rechazo' => 'Falta la metodología.'])
            ->assertSessionHasNoErrors();

        $propuesta->refresh();
        $this->assertSame('rechazado', $propuesta->estado);

        // El estudiante corrige y reenvía: esto es exactamente lo que antes
        // devolvía el error «ya fue rechazada, no puedes volver a inscribirte».
        $this->enviarPropuesta($estudiante, $evento, 'version_corregida.pdf');

        $propuesta->refresh();
        $this->assertSame('pendiente', $propuesta->estado, 'La corrección no volvió a quedar pendiente de revisión.');
        $this->assertSame(2, $propuesta->intentos_envio, 'El reenvío no incrementó el contador de intentos.');
        $this->assertTrue($propuesta->es_correccion, 'El accesor es_correccion debería ser true tras el reenvío.');
        $this->assertSame(
            'Falta la metodología.',
            $propuesta->motivo_rechazo,
            'El motivo del rechazo anterior debería seguir visible mientras se revisa de nuevo.'
        );

        // Se actualizó el MISMO registro: no debe haber una fila duplicada
        // para el mismo estudiante en el mismo evento.
        $this->assertSame(
            1,
            EventoParticipante::where('evento_id', $evento->id)->where('user_id', $estudiante->id)->count()
        );

        // El archivo anterior se reemplazó, no se acumuló.
        Storage::disk('public')->assertMissing($archivoOriginal);
        Storage::disk('public')->assertExists($propuesta->archivo_proyecto);

        // Con el registro ya en 'pendiente', el coordinador puede aprobarlo
        // como cualquier propuesta nueva.
        $jurado = $this->usuarioConRol('Jurado');
        $this->actingAs($coordinador)->post("/propuestas/{$propuesta->id}/aprobar", [
            'programacion' => 'Ponencia',
            'aula' => 'Aula 101',
            'horario' => '10:00 - 10:20',
            'evaluador_id' => $jurado->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame('aprobado', $propuesta->fresh()->estado);
    }

    /** El estudiante ve el motivo del rechazo en el listado de eventos. */
    public function test_el_motivo_del_rechazo_se_muestra_al_estudiante(): void
    {
        Storage::fake('public');

        $estudiante = $this->usuarioConRol('Estudiante');
        $coordinador = $this->usuarioConRol('Coordinador');
        $evento = $this->eventoAbierto();

        $this->enviarPropuesta($estudiante, $evento);
        $propuesta = EventoParticipante::where('evento_id', $evento->id)->where('user_id', $estudiante->id)->firstOrFail();

        $this->actingAs($coordinador)
            ->post("/propuestas/{$propuesta->id}/rechazar", ['motivo_rechazo' => 'Corrige la bibliografía.'])
            ->assertSessionHasNoErrors();

        $props = $this->abrirPagina($estudiante, '/eventos', 'Eventos (motivo de rechazo)');
        $eventoEnPantalla = collect($props['eventos'])->firstWhere('id', $evento->id);

        $this->assertNotNull($eventoEnPantalla, 'El evento no aparece en el listado del estudiante.');
        $this->assertTrue($eventoEnPantalla['fue_rechazado']);
        $this->assertSame(
            'Corrige la bibliografía.',
            $eventoEnPantalla['propuesta_rechazada']['motivo_rechazo'] ?? null,
            'La pantalla no recibe el motivo del rechazo para mostrarlo al estudiante.'
        );
    }
}
