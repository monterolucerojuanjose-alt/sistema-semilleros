# ANEXO DE ARQUITECTURA DEL SISTEMA

## 1. Objetivo del anexo

Este documento resume la arquitectura real del sistema "SEMILLEROS" y presenta evidencia tecnica verificable para sustentarla dentro de una tesis. La verificacion tecnica se realizo el **30 de marzo de 2026** sobre el codigo fuente y mediante comandos ejecutados dentro del proyecto.

## 2. Clasificacion arquitectonica del sistema

El sistema se clasifica como:

- Arquitectura cliente-servidor.
- Arquitectura de 3 capas: presentacion, aplicacion y datos.
- Monolito modular.
- Patron MVC en el backend.
- Frontend SPA hibrida con Vue 3 + Inertia.js.

## 3. Justificacion tecnica de la arquitectura

### 3.1 Cliente-servidor

La aplicacion se ejecuta como un sistema web donde el navegador del usuario consume vistas y respuestas generadas por un servidor Laravel. La interfaz de usuario se monta en Vue 3, pero el enrutamiento principal, la seguridad, la logica y el acceso a datos se resuelven desde el backend.

Evidencia:

- `resources/views/app.blade.php:14-19` carga `@routes`, `@vite` y `@inertia`.
- `resources/js/app.js:11-24` crea la aplicacion con `createInertiaApp(...)`.
- `routes/web.php:18-176` define las rutas web protegidas del sistema.

### 3.2 Arquitectura de 3 capas

#### Capa de presentacion

Esta capa contiene la interfaz web y la interaccion con el usuario.

Evidencia:

- `resources/js/Pages/` contiene las vistas funcionales del sistema: Dashboard, Proyectos, Eventos, Rubricas, Auditoria, Notificaciones, Certificados, Usuarios y otras.
- `resources/js/app.js:11-24` resuelve paginas Vue por medio de `import.meta.glob('./Pages/**/*.vue')`.
- `app/Http/Middleware/HandleInertiaRequests.php:15-33` conecta Laravel con Inertia y comparte informacion del usuario autenticado.

Interpretacion:

La capa de presentacion esta implementada con Vue 3, Inertia.js y Vite, pero se integra dentro de la misma aplicacion Laravel sin separarse en un frontend independiente desplegado aparte.

#### Capa de aplicacion

Aqui se ubican las reglas de acceso, validaciones, coordinacion del flujo de negocio y exposicion de endpoints.

Evidencia:

- `bootstrap/app.php:8-22` registra el enrutamiento y los middleware de la aplicacion.
- `routes/web.php:32-176` agrupa rutas autenticadas y rutas por rol.
- `app/Http/Controllers/ProyectoController.php:78-352` coordina el modulo de proyectos.
- `app/Http/Controllers/EventoController.php:61-508` coordina eventos, postulaciones y evaluacion de propuestas.
- `app/Http/Controllers/ReporteController.php:47-379` coordina la generacion de reportes.
- `app/Http/Requests/StoreProyectoRequest.php:9-19` centraliza validaciones de entrada.
- `app/Http/Middleware/CheckRole.php:16-28` y `app/Http/Middleware/CheckPermission.php:16-27` implementan control de acceso.

Interpretacion:

La capa de aplicacion organiza la logica de negocio por modulos funcionales y protege el acceso mediante autenticacion, roles y permisos.

#### Capa de datos

Esta capa representa el dominio, las relaciones entre entidades y la persistencia en MySQL mediante Eloquent ORM y migraciones.

Evidencia:

- `app/Models/User.php:70-150` define relaciones con semilleros, proyectos, notificaciones, auditorias y eventos.
- `app/Models/Proyecto.php:32-138` relaciona proyectos con autor, semillero, versiones, inscripciones y evaluaciones.
- `app/Models/Semillero.php:20-73` relaciona semilleros con tutor, coordinador, usuarios y proyectos.
- `app/Models/InscripcionEvento.php:32-116` relaciona inscripciones con proyecto y evento.
- `app/Models/Evaluacion.php:30-94` relaciona evaluaciones con proyecto, jurado y rubrica.
- `database/migrations/2025_10_03_010838_create_proyectos_table.php:14-25` crea la tabla `proyectos` con claves foraneas e indices.
- `database/migrations/2025_10_03_002148_create_semillero_user_table.php:14-24` crea la tabla pivote `semillero_user`.
- `database/migrations/2025_10_04_151447_create_evaluaciones_table.php:14-29` crea la tabla `evaluaciones`.
- `database/migrations/2025_10_06_000535_create_notificaciones_table.php:14-25` crea la tabla `notificaciones`.
- `database/migrations/2025_10_06_000548_create_auditorias_table.php:14-28` crea la tabla `auditorias`.

Interpretacion:

La persistencia sigue un modelo relacional normalizado en MySQL y se accede desde modelos Eloquent con relaciones uno a muchos y muchos a muchos.

### 3.3 Monolito modular

El sistema no esta dividido en microservicios. Toda la logica se encuentra en una sola aplicacion Laravel, con una sola base de codigo, un solo registro de rutas y una misma base de datos.

Evidencia:

- `composer.json:9-15` concentra el stack principal del backend en un solo proyecto.
- `routes/web.php:18-176` contiene el mapa centralizado de rutas funcionales.
- `php artisan route:list --except-vendor` reporto **146 rutas** dentro de la misma aplicacion.
- `php artisan about --only=environment,drivers` reporto `Database: mysql`, `Queue: database`, `Session: database`.

Interpretacion:

La aplicacion esta separada por modulos, pero su despliegue y su ejecucion siguen siendo monoliticos.

### 3.4 Patron MVC

Laravel organiza el sistema en modelos, vistas y controladores.

Evidencia:

- Modelos en `app/Models/`.
- Controladores en `app/Http/Controllers/`.
- Vistas base Blade en `resources/views/`.
- Vistas funcionales del frontend en `resources/js/Pages/`.

Ejemplos:

- `app/Http/Controllers/DashboardController.php:27-232`
- `app/Models/Proyecto.php:9-138`
- `resources/views/app.blade.php:14-19`
- `resources/js/Pages/Dashboard.vue`

## 4. Componentes transversales de la arquitectura

El sistema incorpora componentes transversales que extienden la arquitectura mas alla de un CRUD convencional.

### 4.1 Seguridad por autenticacion, roles y permisos

Evidencia:

- `composer.json:15` usa `spatie/laravel-permission`.
- `bootstrap/app.php:20-22` registra los alias `role` y `permission`.
- `routes/web.php:71-168` protege grupos de rutas por rol.
- `app/Http/Middleware/CheckRole.php:25-26` bloquea acceso no autorizado.

### 4.2 Auditoria automatica

Evidencia:

- `app/Providers/AppServiceProvider.php:39-46` registra `AuditoriaObserver` sobre varios modelos.
- `app/Observers/AuditoriaObserver.php:64-102` registra actividad de creacion, actualizacion y eliminacion.
- `database/migrations/2025_10_06_000548_create_auditorias_table.php:16-28` almacena usuario, accion, entidad, datos antes y despues.

Interpretacion:

La auditoria es un mecanismo transversal que monitorea cambios sobre entidades criticas del sistema.

### 4.3 Notificaciones y procesamiento asincrono

Evidencia:

- `app/Jobs/EnviarNotificacionCorreo.php:15-82` implementa `ShouldQueue`.
- `app/Jobs/EnviarCorreoInscripcionConfirmacion.php:16-118` implementa `ShouldQueue`.
- `app/Models/Notificacion.php:84-131` crea notificaciones individuales o masivas.
- `php artisan about --only=environment,drivers` reporto `Queue: database`.

Interpretacion:

El sistema desacopla parte de la comunicacion por correo y las notificaciones internas usando jobs en cola.

### 4.4 Reportes, exportacion y documentos

Evidencia:

- `composer.json:9` usa `barryvdh/laravel-dompdf`.
- `composer.json:14` usa `maatwebsite/excel`.
- `app/Http/Controllers/ReporteController.php:98-125` genera PDF y Excel.
- `app/Http/Controllers/AuditoriaController.php:104-153` exporta auditoria en PDF y Excel.

Interpretacion:

La arquitectura incluye servicios documentales y de explotacion de datos para soporte administrativo y academico.

### 4.5 Gestion de archivos

Evidencia:

- `app/Http/Controllers/ProyectoController.php:211-212` elimina archivos de versiones.
- `app/Http/Controllers/ProyectoController.php:239-244` almacena nuevas versiones de proyectos.
- `app/Http/Controllers/ProyectoController.php:332` descarga documentos del storage.
- `app/Http/Controllers/EventoController.php:150-172` administra imagenes de eventos en disco `public`.

## 5. Evidencia ejecutable verificada

### 5.1 Resultado de `php artisan about --only=environment,drivers`

Se verifico lo siguiente:

- Application Name: `SEMILLEROS`
- Laravel Version: `11.46.1`
- PHP Version: `8.2.12`
- Environment: `local`
- Database: `mysql`
- Queue: `database`
- Session: `database`
- Mail: `log`
- Locale: `es`

Interpretacion:

La aplicacion usa Laravel 11 y una arquitectura apoyada en MySQL como base de datos principal, con colas y sesiones persistidas en base de datos.

### 5.2 Resultado de `php artisan route:list --except-vendor`

Se verifico un total de **146 rutas**. Entre ellas se observan:

- Rutas de dashboard y estadisticas.
- Rutas de proyectos, versiones y descargas.
- Rutas de eventos, postulaciones y evaluacion.
- Rutas de rubricas y evaluaciones.
- Rutas de auditoria, reportes y notificaciones.
- Rutas de certificados y verificacion de certificados.
- Rutas restringidas por roles administrativos, docentes, jurados y estudiantes.

Interpretacion:

El sistema posee una estructura funcional modular, pero centralizada dentro del mismo backend Laravel.

## 6. Procedimiento recomendado para sacar evidencia para la tesis

Para defender la arquitectura del sistema, se recomienda presentar evidencia en cuatro bloques.

### 6.1 Evidencia del stack tecnologico

Tomar capturas o anexar fragmentos de:

- `composer.json`
- `package.json`
- Resultado de `php artisan about --only=environment,drivers`

Esto demuestra oficialmente el uso de Laravel, Vue, Inertia, Vite, MySQL, colas y servicios complementarios.

### 6.2 Evidencia de la arquitectura por capas

Tomar capturas o anexar fragmentos de:

- `resources/views/app.blade.php`
- `resources/js/app.js`
- `routes/web.php`
- `app/Http/Controllers/`
- `app/Models/`

Esto demuestra la separacion entre interfaz, logica de aplicacion y persistencia.

### 6.3 Evidencia del modelo de datos

Tomar capturas o anexar fragmentos de:

- `database/migrations/2025_10_03_002136_create_semilleros_table.php`
- `database/migrations/2025_10_03_002148_create_semillero_user_table.php`
- `database/migrations/2025_10_03_010838_create_proyectos_table.php`
- `database/migrations/2025_10_03_195344_create_inscripciones_evento_table.php`
- `database/migrations/2025_10_04_151447_create_evaluaciones_table.php`
- `database/migrations/2025_10_06_000535_create_notificaciones_table.php`
- `database/migrations/2025_10_06_000548_create_auditorias_table.php`

Esto permite construir un DER o diagrama entidad-relacion sustentado por el codigo.

### 6.4 Evidencia de modulos transversales

Tomar capturas o anexar fragmentos de:

- `app/Providers/AppServiceProvider.php`
- `app/Observers/AuditoriaObserver.php`
- `app/Jobs/EnviarNotificacionCorreo.php`
- `app/Http/Controllers/ReporteController.php`

Esto demuestra seguridad, auditoria, reportes y procesamiento asincrono.

## 7. Recomendaciones para anexos de tesis

Se recomienda crear los siguientes anexos:

1. Anexo A: evidencia del stack tecnologico.
2. Anexo B: mapa de rutas y modulos funcionales.
3. Anexo C: evidencia del patron MVC.
4. Anexo D: DER basado en migraciones.
5. Anexo E: evidencia de seguridad por roles y permisos.
6. Anexo F: evidencia de auditoria, reportes y notificaciones.

## 8. Texto listo para usar en la tesis

Se puede usar el siguiente parrafo, ajustando el estilo de redaccion segun la universidad:

> El sistema de gestion de semilleros fue implementado bajo una arquitectura cliente-servidor de tres capas. A nivel de organizacion logica, corresponde a un monolito modular desarrollado con Laravel 11 bajo el patron MVC, donde la capa de presentacion se implementa con Vue 3 e Inertia.js, la capa de aplicacion se materializa en rutas, middleware, controladores y validaciones, y la capa de datos se soporta en MySQL mediante modelos Eloquent y migraciones. Adicionalmente, la solucion integra modulos transversales de autenticacion, control de acceso por roles, auditoria automatica, notificaciones, colas de procesamiento, almacenamiento de archivos y generacion de reportes en PDF y Excel.

## 9. Advertencias metodologicas

- No se recomienda usar el archivo `.env` como evidencia publica, porque puede exponer configuraciones sensibles.
- Es preferible sustentar la arquitectura con archivos de codigo, migraciones, rutas y comandos reproducibles.
- Si el jurado solicita evidencia funcional, conviene acompanar este anexo con capturas de pantalla del dashboard, gestion de proyectos, eventos, rubricas, auditoria y reportes.
