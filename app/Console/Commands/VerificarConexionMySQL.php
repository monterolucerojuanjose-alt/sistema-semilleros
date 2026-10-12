<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * ============================================================================
 * COMANDO DE CONSOLA: VERIFICAR LA CONEXIÓN CON MYSQL
 * ============================================================================
 *
 * Comprueba que la aplicación puede hablar con la base de datos y muestra un
 * resumen de cuántos registros hay en cada tabla principal.
 *
 * Es la primera herramienta a la que recurrir cuando el sistema no arranca o
 * muestra pantallas vacías, porque separa dos causas que desde el navegador
 * se confunden: que el servidor de base de datos no responda, o que responda
 * pero las tablas estén vacías.
 *
 * Uso:
 *   php artisan db:check
 */
class VerificarConexionMySQL extends Command
{
    /**
     * Nombre con el que se invoca el comando. No recibe argumentos.
     *
     * @var string
     */
    protected $signature = 'db:check';

    /**
     * Descripción que aparece al ejecutar «php artisan list».
     *
     * @var string
     */
    protected $description = 'Verifica la conexión con la base de datos MySQL';

    /**
     * Tablas cuyo número de registros se muestra en el resumen.
     *
     * Se declaran en una propiedad, y no repitiendo el mismo bloque de código
     * siete veces, para que añadir una tabla nueva al informe sea agregar una
     * línea aquí.
     *
     * @var array<string, string> Nombre de la tabla => etiqueta a mostrar.
     */
    private array $tablasDelResumen = [
        'users'                => 'Usuarios',
        'semilleros'           => 'Semilleros',
        'proyectos'            => 'Proyectos',
        'eventos'              => 'Eventos',
        'inscripciones_evento' => 'Inscripciones',
        'evaluaciones'         => 'Evaluaciones',
        'certificados'         => 'Certificados',
        'notificaciones'       => 'Notificaciones',
        'auditorias'           => 'Registros de auditoría',
    ];

    /**
     * Ejecuta el comando.
     *
     * @return int Command::SUCCESS (0) si la conexión funciona,
     *             Command::FAILURE (1) si no se pudo conectar.
     */
    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  Verificando conexión con la base de datos');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        try {
            // ---------------------------------------------------------------
            // 1. Probar la conexión
            // ---------------------------------------------------------------
            // getPdo() abre la conexión de verdad. Laravel es perezoso: hasta
            // que no se le pide algo concreto no intenta conectarse, así que
            // sin esta llamada el comando diría «todo correcto» aunque MySQL
            // estuviera apagado.
            DB::connection()->getPdo();

            $dbName = DB::connection()->getDatabaseName();
            $this->info("✅ Conexión exitosa a la base de datos: {$dbName}");
            $this->newLine();

            // ---------------------------------------------------------------
            // 2. Mostrar con qué parámetros se conectó
            // ---------------------------------------------------------------
            // Se leen de la configuración y no del archivo .env directamente,
            // porque así se ve el valor que la aplicación está usando de
            // verdad, incluido el que provenga de la caché de configuración.
            $this->info('📊 Información de la conexión:');
            $this->line("   • Conexión: " . config('database.default'));
            $this->line("   • Servidor: " . config('database.connections.mysql.host'));
            $this->line("   • Puerto: " . config('database.connections.mysql.port'));
            $this->line("   • Base de datos: " . config('database.connections.mysql.database'));
            $this->line("   • Usuario: " . config('database.connections.mysql.username'));
            $this->newLine();

            // ---------------------------------------------------------------
            // 3. Contar los registros de cada tabla principal
            // ---------------------------------------------------------------
            $this->info('📈 Estadísticas de datos:');

            foreach ($this->tablasDelResumen as $tabla => $etiqueta) {
                try {
                    $total = DB::table($tabla)->count();
                    $this->line("   • {$etiqueta}: {$total}");
                } catch (Exception $e) {
                    // Cada tabla se consulta dentro de su propio try. Así, si
                    // falta una migración, el informe avisa de esa tabla y
                    // sigue con las demás en lugar de cortarse en la primera.
                    $this->warn("   • {$etiqueta}: la tabla «{$tabla}» no existe");
                }
            }

            $this->newLine();
            $this->info('═══════════════════════════════════════════════════════');
            $this->info('  ✅ Sistema listo para usar');
            $this->info('═══════════════════════════════════════════════════════');

            return Command::SUCCESS;

        } catch (Exception $e) {
            // ---------------------------------------------------------------
            // 4. La conexión falló: explicar cómo resolverlo
            // ---------------------------------------------------------------
            // Se muestra el mensaje original y además una lista de causas
            // habituales, porque el error de PDO por sí solo («connection
            // refused») no le dice nada a quien no conoce el sistema.
            $this->newLine();
            $this->error('❌ Error de conexión a la base de datos');
            $this->error('   Mensaje: ' . $e->getMessage());
            $this->newLine();
            $this->warn('💡 Comprueba que:');
            $this->line('   1. El servidor MySQL esté en marcha.');
            $this->line('      En macOS:  brew services start mysql');
            $this->line('      En Windows: iniciar MySQL desde el panel de XAMPP');
            $this->line('   2. La base de datos "semilleros_db" exista.');
            $this->line('      Crearla con:  mysql -u root -e "CREATE DATABASE semilleros_db;"');
            $this->line('   3. El archivo .env tenga la configuración correcta:');
            $this->line('      DB_CONNECTION=mysql');
            $this->line('      DB_HOST=127.0.0.1');
            $this->line('      DB_PORT=3306');
            $this->line('      DB_DATABASE=semilleros_db');
            $this->line('      DB_USERNAME=root');
            $this->line('      DB_PASSWORD=');
            $this->newLine();

            return Command::FAILURE;
        }
    }
}
