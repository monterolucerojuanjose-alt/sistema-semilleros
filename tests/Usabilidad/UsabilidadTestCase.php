<?php

namespace Tests\Usabilidad;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Base de las pruebas de usabilidad.
 *
 * Cada prueba corre dentro de una transacción que se revierte al terminar, de
 * modo que la copia de datos queda igual que al inicio y las pruebas pueden
 * repetirse indefinidamente.
 */
abstract class UsabilidadTestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Salvaguarda: estas pruebas escriben en la base. Si por un error de
        // configuración apuntaran a la base real, se detienen aquí.
        $baseDatos = DB::connection()->getDatabaseName();

        if (! str_ends_with($baseDatos, '_test')) {
            $this->fail(
                "Las pruebas de usabilidad apuntan a «{$baseDatos}», que no es una base de pruebas. ".
                'Ejecuta con: php vendor/bin/phpunit -c phpunit.usabilidad.xml'
            );
        }
    }

    /**
     * Devuelve el primer usuario que tenga el rol indicado.
     */
    protected function usuarioConRol(string $rol): User
    {
        $usuario = User::whereHas('roles', fn ($q) => $q->where('name', $rol))->first();

        $this->assertNotNull($usuario, "No hay ningún usuario con el rol «{$rol}» en los datos de prueba.");

        return $usuario;
    }

    /**
     * Abre una página como el usuario dado y comprueba que carga sin errores.
     *
     * Devuelve las propiedades que Inertia entrega a la vista, para poder
     * verificar que la pantalla trae datos y no sólo que responde 200.
     */
    protected function abrirPagina(User $usuario, string $url, string $historia): array
    {
        $respuesta = $this->actingAs($usuario)->get($url);

        $this->assertSame(
            200,
            $respuesta->getStatusCode(),
            "{$historia}: la página {$url} respondió {$respuesta->getStatusCode()} en lugar de 200."
        );

        // Algunas rutas devuelven JSON en lugar de una pantalla de Inertia;
        // en ese caso basta con haber comprobado que responden correctamente.
        $vista = $respuesta->baseResponse->original;

        if (! $vista instanceof \Illuminate\View\View) {
            return [];
        }

        return $vista->getData()['page']['props'] ?? [];
    }
}
