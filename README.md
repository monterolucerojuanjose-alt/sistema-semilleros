# Sistema de Gestión de Semilleros de Investigación

Aplicación web para la administración de semilleros de investigación: gestión de proyectos y sus versiones, inscripción de propuestas a eventos, evaluación mediante rúbricas, emisión de certificados, notificaciones, auditoría y reportes.

## Tecnologías

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11 (PHP 8.2 – 8.4) |
| Frontend | Vue 3 + Inertia.js |
| Estilos | Tailwind CSS 3 |
| Build | Vite 5 |
| Base de datos | MySQL 8 / MariaDB 10.4+ |
| Autenticación y roles | Laravel Breeze + Spatie Laravel Permission |
| Reportes | maatwebsite/excel (Excel) y barryvdh/laravel-dompdf (PDF) |

> En desarrollo no se necesita Apache ni Nginx: se usa el servidor integrado de Laravel (`php artisan serve`) junto al servidor de desarrollo de Vite.

## Requisitos

- PHP >= 8.2 y <= 8.4, con las extensiones `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `gd`, `zip`, `bcmath` y `fileinfo`
- Composer 2
- Node.js >= 18 y npm
- Un servidor MySQL o MariaDB

## Instalación

### 1. Dependencias

```bash
composer install
npm install
```

### 2. Variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

Ajuste la conexión a la base de datos en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=semilleros_db
DB_USERNAME=root
DB_PASSWORD=
```

Por defecto los correos se escriben en `storage/logs` (`MAIL_MAILER=log`). Para enviarlos de verdad configure las variables `MAIL_*` con su proveedor SMTP.

### 3. Base de datos

Cree la base de datos:

```sql
CREATE DATABASE semilleros_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Cree las tablas y cargue los datos de demostración (roles, usuarios, semilleros, proyectos, rúbricas, eventos, etc.):

```bash
php artisan migrate --seed
```

### 4. Enlace de almacenamiento público

```bash
php artisan storage:link
```

### 5. Verificar la instalación

```bash
php artisan db:check       # comprueba la conexión y muestra estadísticas
php artisan sistema:estado # estado general del sistema
```

## Usuarios de demostración

Tras ejecutar los seeders existe un usuario por cada rol:

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin.rol@umariana.edu.co` | `password` |
| Coordinador | `coordinador.rol@umariana.edu.co` | `password` |
| Docente/Tutor | `tutor.rol@umariana.edu.co` | `password` |
| Estudiante | `estudiante.rol@umariana.edu.co` | `password` |
| Jurado | `jurado.rol@umariana.edu.co` | `password` |

También se crea `admin@semilleros.edu` (contraseña `admin123`). Estas credenciales son solo para demostración y deben cambiarse en cualquier despliegue real.

## Ejecución

La aplicación necesita dos procesos en paralelo.

**macOS / Linux**

```bash
./iniciar.sh
```

**Manual** (dos terminales):

```bash
php artisan serve    # backend  -> http://127.0.0.1:8000
```

```bash
npm run dev          # frontend -> http://localhost:5173
```

La aplicación queda disponible en **http://127.0.0.1:8000**.

Para un despliegue en producción se compilan los assets en lugar de usar el servidor de Vite:

```bash
npm run build
```

## Estructura del proyecto

```
app/
  Console/Commands/    Comandos artisan propios del sistema
  Exports/             Exportaciones a Excel (maatwebsite/excel)
  Http/Controllers/    Controladores por módulo
  Jobs/                Envío de correos en cola
  Mail/                Plantillas de correo
  Models/              Modelos Eloquent
  Observers/           Registro automático de auditoría
  Policies/            Reglas de autorización
  Support/             Utilidades compartidas (catálogos de eventos, numeración de PDF)
database/
  migrations/          Migraciones del esquema
  seeders/             Datos iniciales y de demostración
resources/
  js/Pages/            Vistas Vue por módulo (Inertia)
  js/Components/       Componentes reutilizables
  js/Layouts/          Plantillas de página
  views/               Plantillas Blade para PDF y correo
routes/
  web.php              Rutas de la aplicación
  auth.php             Rutas de autenticación
docs/                  Documentación técnica, anexos de la tesis y manual de usuario
scripts/               Respaldos (backup-semilleros.sh) y preparación de la base de pruebas
tests/                 Pruebas unitarias, de funcionalidad y de usabilidad
```

## Módulos

1. **Usuarios y roles** — autenticación y control de acceso por roles (Administrador, Coordinador, Docente/Tutor, Estudiante, Jurado) con Spatie Laravel Permission.
2. **Semilleros** — creación, asignación de tutor y gestión de solicitudes de ingreso.
3. **Proyectos** — registro de proyectos con versionamiento de documentos y flujo de estados (Inscrito, En revisión, Aprobado, Rechazado).
4. **Eventos e inscripciones** — convocatorias y presentación de propuestas. Cada propuesta declara su **modalidad** y su **área de participación**; el coordinador la aprueba (asignando aula, horario y jurado) o la rechaza con un motivo, y el estudiante puede corregirla y reenviarla.
5. **Evaluaciones** — rúbricas con criterios ponderados, que pueden ligarse a un evento y a una modalidad. Los jurados califican con ellas tanto proyectos de semillero como propuestas de evento. La nota va de **0 a 100**. Cada jurado solo ve sus propias evaluaciones.
6. **Certificados** — generación de certificados en PDF con descarga directa.
7. **Notificaciones** — avisos en la aplicación y envío de correo mediante colas (Jobs).
8. **Auditoría** — registro automático de acciones sobre las entidades del sistema.
9. **Reportes** — vista previa y exportación a Excel y PDF de semilleros, proyectos, eventos, inscripciones, evaluaciones y participantes.

## Modelo de datos

Tablas principales:

| Tabla | Contenido |
|---|---|
| `users` | Usuarios del sistema |
| `semilleros`, `semillero_user` | Semilleros y sus miembros |
| `proyectos`, `versiones_proyecto` | Proyectos y su historial de versiones |
| `eventos`, `inscripciones_evento`, `evento_participantes` | Eventos, proyectos inscritos y propuestas presentadas |
| `rubricas`, `criterio_rubricas` | Plantillas de evaluación y sus criterios |
| `evaluaciones`, `detalle_evaluaciones` | Evaluaciones (de un proyecto o de una propuesta de evento) y su detalle por criterio |
| `certificados`, `certificates` | Certificados emitidos |
| `notificaciones` | Notificaciones a usuarios |
| `auditorias` | Registro de auditoría |
| `roles`, `permissions`, `model_has_roles` | Roles y permisos |

## Comandos artisan propios

```bash
php artisan db:check                       # Verifica la conexión y muestra estadísticas
php artisan sistema:estado                 # Resumen del estado del sistema
php artisan user:assign-role {email} {rol} # Asigna un rol a un usuario
```

## Pruebas

Hay dos suites.

**Pruebas unitarias y de funcionalidad**

```bash
php artisan test
```

Se ejecutan contra una base SQLite **en memoria**, definida en `phpunit.xml`. Esto es deliberado: usan el trait `RefreshDatabase`, que recrea el esquema desde cero, y sin ese aislamiento se borrarían los datos de `semilleros_db`. No comente esas dos líneas de `phpunit.xml`.

**Pruebas de usabilidad** (una por historia de usuario, por rol)

```bash
bash scripts/preparar-base-pruebas.sh               # copia semilleros_db en semilleros_db_test
php vendor/bin/phpunit -c phpunit.usabilidad.xml
```

Trabajan sobre la copia `semilleros_db_test` y cada prueba se revierte al terminar, de modo que la base real no se modifica. Requieren que `semilleros_db` tenga los datos de demostración (`php artisan migrate --seed`).

## Respaldos

`scripts/backup-semilleros.sh` genera un respaldo completo: volcado de la base de datos, archivo comprimido del proyecto y un manifiesto con las instrucciones de restauración.

```bash
./scripts/backup-semilleros.sh
```

Los respaldos se guardan en `backups/`, que no se versiona porque contiene datos reales. Ver [docs/BACKUPS_AUTOMATICOS.md](docs/BACKUPS_AUTOMATICOS.md).

## Solución de problemas

**No conecta con la base de datos**

```bash
php artisan db:check
```
Verifique que el servidor MySQL/MariaDB esté iniciado y que las credenciales de `.env` sean correctas.

**Las tablas no existen**

```bash
php artisan migrate
```

**El puerto 8000 está ocupado**

```bash
php artisan serve --port=8001
```

**Comportamiento extraño tras cambiar configuración**

```bash
php artisan optimize:clear
```

**Los estilos o la interfaz no cargan**

Asegúrese de que `npm run dev` esté en ejecución, o compile los assets con `npm run build`.

## Documentación adicional

- [docs/manual/Manual_de_Usuario_Sistema_Semilleros.pdf](docs/manual/Manual_de_Usuario_Sistema_Semilleros.pdf) — Manual de usuario
- [docs/ANEXO_ARQUITECTURA_TESIS.md](docs/ANEXO_ARQUITECTURA_TESIS.md) — Anexo de arquitectura
- [docs/DIAGRAMAS_ARQUITECTURA_TESIS.md](docs/DIAGRAMAS_ARQUITECTURA_TESIS.md) — Diagramas del sistema
- [docs/ROLES_Y_PERMISOS.md](docs/ROLES_Y_PERMISOS.md) — Matriz de roles y permisos
- [docs/MODULO_EVENTOS.md](docs/MODULO_EVENTOS.md) — Módulo de eventos
- [docs/MODULO_EVALUACION_PROYECTOS_EVENTOS.md](docs/MODULO_EVALUACION_PROYECTOS_EVENTOS.md) — Evaluación de propuestas de evento
- [docs/BACKUPS_AUTOMATICOS.md](docs/BACKUPS_AUTOMATICOS.md) — Sistema de respaldos

## Licencia

Software desarrollado como trabajo de grado para la gestión de semilleros de investigación.
