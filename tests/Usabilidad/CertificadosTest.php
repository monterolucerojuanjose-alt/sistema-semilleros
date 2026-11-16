<?php

namespace Tests\Usabilidad;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Flujo completo de certificados: emitir, consultar, editar, revocar y
 * descargar. Cubre los fallos que tenía el módulo:
 *  - Sin PDF adjunto no había nada que descargar («Sin PDF»): ahora el
 *    sistema genera el certificado institucional.
 *  - Editar enviaba un PUT multipart que PHP no lee, así que guardar fallaba
 *    con «el título es obligatorio».
 *  - El panel de inicio contaba otra tabla y mostraba cifras que no
 *    coincidían con la pantalla de certificados.
 */
class CertificadosTest extends UsabilidadTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ningún archivo de prueba llega al disco real.
        Storage::fake('public');
    }

    private function estudiante(): User
    {
        return $this->usuarioConRol('Estudiante');
    }

    private function emitir(User $gestor, User $estudiante, array $extra = []): Certificate
    {
        $this->actingAs($gestor)->post('/certificados', array_merge([
            'titulo' => 'Certificado de ponencia — Prueba automática',
            'tipo' => 'ponencia',
            'fecha_emision' => now()->toDateString(),
            'user_id' => $estudiante->id,
            'horas' => 16,
        ], $extra))->assertRedirect()->assertSessionHasNoErrors();

        return Certificate::where('user_id', $estudiante->id)->latest('id')->firstOrFail();
    }

    public function test_emitir_sin_pdf_genera_el_certificado_institucional(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $estudiante = $this->estudiante();

        $certificado = $this->emitir($coordinador, $estudiante);

        $this->assertNull($certificado->archivo_certificado);
        $this->assertSame(16, $certificado->metadata['horas']);
        $this->assertSame($coordinador->id, $certificado->emitido_por);

        $descarga = $this->actingAs($estudiante)->get("/certificados/{$certificado->id}/descargar");
        $descarga->assertOk();
        $this->assertSame('application/pdf', $descarga->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $descarga->getContent(), 'La descarga no es un PDF real.');

        // ?ver=1 lo abre en el navegador en lugar de descargarlo.
        $enLinea = $this->actingAs($estudiante)->get("/certificados/{$certificado->id}/descargar?ver=1");
        $enLinea->assertOk();
        $this->assertStringContainsString('inline', (string) $enLinea->headers->get('Content-Disposition'));
    }

    public function test_si_se_adjunta_un_pdf_se_entrega_ese(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $estudiante = $this->estudiante();

        $certificado = $this->emitir($coordinador, $estudiante, [
            'archivo_certificado' => UploadedFile::fake()->create('firmado.pdf', 120, 'application/pdf'),
        ]);

        $this->assertNotNull($certificado->archivo_certificado);
        Storage::disk('public')->assertExists($certificado->archivo_certificado);

        $this->actingAs($estudiante)->get("/certificados/{$certificado->id}/descargar")->assertOk();
    }

    public function test_editar_guarda_los_cambios_aunque_viaje_como_multipart(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $certificado = $this->emitir($coordinador, $this->estudiante());

        // Así lo envía la pantalla: POST con _method=put y como multipart,
        // porque puede llevar un archivo.
        $this->actingAs($coordinador)->post("/certificados/{$certificado->id}", [
            '_method' => 'put',
            'titulo' => 'Título corregido',
            'tipo' => 'participacion',
            'fecha_emision' => now()->toDateString(),
            'user_id' => $certificado->user_id,
            'estado' => 'activo',
            'horas' => 8,
            'archivo_certificado' => UploadedFile::fake()->create('nuevo.pdf', 50, 'application/pdf'),
        ])->assertRedirect(route('certificados.show', $certificado))->assertSessionHasNoErrors();

        $certificado->refresh();
        $this->assertSame('Título corregido', $certificado->titulo);
        $this->assertSame('participacion', $certificado->tipo);
        $this->assertSame(8, $certificado->metadata['horas']);
        $this->assertNotNull($certificado->archivo_certificado);
    }

    public function test_un_certificado_revocado_no_lo_descarga_su_titular_pero_si_el_gestor(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $estudiante = $this->estudiante();
        $certificado = $this->emitir($coordinador, $estudiante);
        $certificado->update(['estado' => 'revocado']);

        $this->actingAs($estudiante)
            ->from("/certificados/{$certificado->id}")
            ->get("/certificados/{$certificado->id}/descargar")
            ->assertRedirect("/certificados/{$certificado->id}")
            ->assertSessionHas('error');

        $this->actingAs($coordinador)->get("/certificados/{$certificado->id}/descargar")->assertOk();
    }

    public function test_un_estudiante_no_ve_certificados_ajenos_ni_puede_emitir(): void
    {
        $coordinador = $this->usuarioConRol('Coordinador');
        $titular = $this->estudiante();
        $otro = User::role('Estudiante')->where('id', '!=', $titular->id)->firstOrFail();
        $certificado = $this->emitir($coordinador, $titular);

        $this->actingAs($otro)->get("/certificados/{$certificado->id}")->assertForbidden();
        $this->actingAs($otro)->get("/certificados/{$certificado->id}/descargar")->assertForbidden();
        $this->actingAs($otro)->get('/certificados/create')->assertForbidden();
    }

    public function test_el_panel_cuenta_los_mismos_certificados_que_la_pantalla(): void
    {
        $administrador = $this->usuarioConRol('Administrador');

        $panel = $this->abrirPagina($administrador, '/dashboard', 'Certificados');
        $pantalla = $this->abrirPagina($administrador, '/certificados', 'Certificados');

        $this->assertSame(
            $pantalla['resumen']['total'],
            $panel['stats']['totalCertificados'] ?? null,
            'El panel de inicio y la pantalla de certificados muestran cifras distintas.'
        );
    }
}
