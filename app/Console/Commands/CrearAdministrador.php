<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

/**
 * Crea el primer administrador de una instalación nueva.
 *
 * Una instalación recién hecha está en blanco y nadie puede entrar a la
 * gestión de usuarios. Antes el seeder creaba un administrador con una
 * contraseña fija escrita en el código (y publicada en el repositorio);
 * ahora quien instala elige el correo y la contraseña.
 *
 * Uso interactivo:
 *   php artisan semilleros:crear-administrador
 *
 * Sin preguntas (por ejemplo en un script de despliegue):
 *   php artisan semilleros:crear-administrador --nombre="Ana Pérez" --correo=ana@umariana.edu.co --password="..."
 */
class CrearAdministrador extends Command
{
    protected $signature = 'semilleros:crear-administrador
                            {--nombre= : Nombre completo}
                            {--correo= : Correo con el que iniciará sesión}
                            {--password= : Contraseña (mínimo 8 caracteres)}';

    protected $description = 'Crea el primer usuario Administrador del sistema';

    public function handle(): int
    {
        // Los roles deben existir; si se migró sin --seed, se crean aquí.
        if (! Role::where('name', 'Administrador')->exists()) {
            $this->callSilently('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
            $this->line('Se crearon los roles y permisos del sistema.');
        }

        $datos = [
            'nombre' => $this->option('nombre') ?? $this->ask('Nombre completo'),
            'correo' => $this->option('correo') ?? $this->ask('Correo electrónico'),
            'password' => $this->option('password') ?? $this->secret('Contraseña (mínimo 8 caracteres)'),
        ];

        if (! $this->option('password')) {
            $confirmacion = $this->secret('Repite la contraseña');
            if ($confirmacion !== $datos['password']) {
                $this->error('Las contraseñas no coinciden.');

                return self::FAILURE;
            }
        }

        $validador = Validator::make($datos, [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no es válido.',
            'correo.unique' => 'Ya existe un usuario con ese correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $mensaje) {
                $this->error($mensaje);
            }

            return self::FAILURE;
        }

        $rol = Role::where('name', 'Administrador')->first();

        $usuario = User::create([
            'name' => $datos['nombre'],
            'email' => $datos['correo'],
            'password' => Hash::make($datos['password']),
            'rol_id' => $rol->id,
            'estado' => 'activo',
        ]);
        $usuario->forceFill(['email_verified_at' => now()])->save();
        $usuario->assignRole($rol);

        $this->info("Administrador creado: {$usuario->email}");
        $this->line('Ya puede iniciar sesión y configurar el sistema (usuarios, semilleros, eventos y rúbricas).');

        return self::SUCCESS;
    }
}
