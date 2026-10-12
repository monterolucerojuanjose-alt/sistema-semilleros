# Sistema de Roles y Permisos

Este documento describe el sistema de roles y permisos implementado en el proyecto de gestión de semilleros de investigación.

## Roles Definidos

### 1. **Administrador**
- **Descripción**: Control total del sistema
- **Permisos**: Todos los permisos del sistema
- **Funciones**:
  - Gestiona usuarios y roles
  - Configura el sistema
  - Acceso a todos los reportes y estadísticas
  - Gestión completa de semilleros, proyectos, estudiantes, etc.

### 2. **Coordinador**
- **Descripción**: Gestiona semilleros, aprueba solicitudes, asigna jurados, genera reportes
- **Permisos principales**:
  - Gestión de semilleros (crear, editar, ver, gestionar)
  - Aprobar/rechazar solicitudes de estudiantes
  - Asignar jurados a proyectos y ponencias
  - Generar reportes y certificados
  - Ver estadísticas del sistema

### 3. **Docente/Tutor**
- **Descripción**: Supervisa proyectos de sus semilleros, revisa entregas de estudiantes
- **Permisos principales**:
  - Ver y supervisar proyectos de sus semilleros
  - Aprobar/rechazar solicitudes de sus semilleros
  - Ver estudiantes de sus semilleros
  - Gestionar ponencias y pósteres de sus semilleros

### 4. **Estudiante**
- **Descripción**: Registra proyectos, solicita unirse a semilleros, inscribe ponencias/pósteres
- **Permisos principales**:
  - Crear y gestionar sus propios proyectos
  - Solicitar unirse a semilleros
  - Inscribir ponencias y pósteres
  - Ver sus propias solicitudes y proyectos

### 5. **Jurado**
- **Descripción**: Evalúa proyectos y ponencias asignadas mediante rúbricas
- **Permisos principales**:
  - Ver proyectos asignados para evaluación
  - Evaluar ponencias y pósteres
  - Acceder a rúbricas de evaluación

## Permisos del Sistema

### Gestión de Usuarios
- `users.index` - Ver lista de usuarios
- `users.create` - Crear usuarios
- `users.edit` - Editar usuarios
- `users.delete` - Eliminar usuarios
- `users.view` - Ver detalles de usuario

### Gestión de Roles
- `roles.index` - Ver lista de roles
- `roles.create` - Crear roles
- `roles.edit` - Editar roles
- `roles.delete` - Eliminar roles
- `roles.assign` - Asignar roles

### Gestión de Semilleros
- `semilleros.index` - Ver lista de semilleros
- `semilleros.create` - Crear semilleros
- `semilleros.edit` - Editar semilleros
- `semilleros.delete` - Eliminar semilleros
- `semilleros.view` - Ver detalles de semillero
- `semilleros.approve` - Aprobar semilleros
- `semilleros.manage` - Gestionar semilleros

### Gestión de Proyectos
- `proyectos.index` - Ver lista de proyectos
- `proyectos.create` - Crear proyectos
- `proyectos.edit` - Editar proyectos
- `proyectos.delete` - Eliminar proyectos
- `proyectos.view` - Ver detalles de proyecto
- `proyectos.supervise` - Supervisar proyectos
- `proyectos.evaluate` - Evaluar proyectos

### Gestión de Estudiantes
- `estudiantes.index` - Ver lista de estudiantes
- `estudiantes.create` - Crear estudiantes
- `estudiantes.edit` - Editar estudiantes
- `estudiantes.delete` - Eliminar estudiantes
- `estudiantes.view` - Ver detalles de estudiante
- `estudiantes.approve` - Aprobar estudiantes
- `estudiantes.assign` - Asignar estudiantes

### Gestión de Solicitudes
- `solicitudes.index` - Ver lista de solicitudes
- `solicitudes.create` - Crear solicitudes
- `solicitudes.edit` - Editar solicitudes
- `solicitudes.delete` - Eliminar solicitudes
- `solicitudes.view` - Ver detalles de solicitud
- `solicitudes.approve` - Aprobar solicitudes
- `solicitudes.reject` - Rechazar solicitudes

### Gestión de Jurados
- `jurados.index` - Ver lista de jurados
- `jurados.create` - Crear jurados
- `jurados.edit` - Editar jurados
- `jurados.delete` - Eliminar jurados
- `jurados.view` - Ver detalles de jurado
- `jurados.assign` - Asignar jurados

### Gestión de Ponencias
- `ponencias.index` - Ver lista de ponencias
- `ponencias.create` - Crear ponencias
- `ponencias.edit` - Editar ponencias
- `ponencias.delete` - Eliminar ponencias
- `ponencias.view` - Ver detalles de ponencia
- `ponencias.evaluate` - Evaluar ponencias
- `ponencias.approve` - Aprobar ponencias

### Gestión de Pósteres
- `posteres.index` - Ver lista de pósteres
- `posteres.create` - Crear pósteres
- `posteres.edit` - Editar pósteres
- `posteres.delete` - Eliminar pósteres
- `posteres.view` - Ver detalles de póster
- `posteres.evaluate` - Evaluar pósteres
- `posteres.approve` - Aprobar pósteres

### Reportes y Certificados
- `reportes.index` - Ver lista de reportes
- `reportes.create` - Crear reportes
- `reportes.view` - Ver reportes
- `reportes.export` - Exportar reportes
- `certificados.generate` - Generar certificados
- `certificados.view` - Ver certificados

### Configuraciones
- `configuraciones.index` - Ver configuraciones
- `configuraciones.edit` - Editar configuraciones
- `configuraciones.view` - Ver configuraciones

### Dashboard y Estadísticas
- `dashboard.view` - Ver dashboard
- `estadisticas.view` - Ver estadísticas

## Uso en el Código

### En Controladores

```php
// Verificar rol
$this->middleware('role:Administrador');

// Verificar múltiples roles
$this->middleware('role:Administrador,Coordinador');

// Verificar permiso
$this->middleware('permission:users.create');

// Verificar múltiples permisos
$this->middleware('permission:users.create,users.edit');
```

### En Vistas (Blade)

```php
// Verificar rol
@if(auth()->user()->hasRole('Administrador'))
    <!-- Contenido para administradores -->
@endif

// Verificar permiso
@if(auth()->user()->can('users.create'))
    <!-- Botón de crear usuario -->
@endif

// Verificar múltiples roles
@if(auth()->user()->hasAnyRole(['Administrador', 'Coordinador']))
    <!-- Contenido para administradores y coordinadores -->
@endif
```

### En Código PHP

```php
use App\Helpers\RoleHelper;

// Verificar roles
if (RoleHelper::isAdmin()) {
    // Lógica para administradores
}

if (RoleHelper::isCoordinador()) {
    // Lógica para coordinadores
}

// Verificar permisos
if (RoleHelper::canManageUsers()) {
    // Lógica para gestión de usuarios
}

if (RoleHelper::canGenerateReports()) {
    // Lógica para generar reportes
}
```

## Comandos Artisan

### Asignar rol a usuario
```bash
php artisan user:assign-role usuario@ejemplo.com Administrador
```

### Ejecutar seeder de roles y permisos
```bash
php artisan db:seed --class=RolePermissionSeeder
```

## Middleware Personalizado

### CheckRole
Verifica que el usuario tenga uno de los roles especificados.

```php
Route::middleware(['role:Administrador'])->group(function () {
    // Rutas solo para administradores
});
```

### CheckPermission
Verifica que el usuario tenga uno de los permisos especificados.

```php
Route::middleware(['permission:users.create'])->group(function () {
    // Rutas para crear usuarios
});
```

## Helpers Disponibles

La clase `RoleHelper` proporciona métodos útiles para verificar roles y permisos:

- `RoleHelper::hasRole($role)` - Verificar rol específico
- `RoleHelper::hasAnyRole($roles)` - Verificar múltiples roles
- `RoleHelper::hasPermission($permission)` - Verificar permiso específico
- `RoleHelper::hasAnyPermission($permissions)` - Verificar múltiples permisos
- `RoleHelper::isAdmin()` - Verificar si es administrador
- `RoleHelper::isCoordinador()` - Verificar si es coordinador
- `RoleHelper::isDocente()` - Verificar si es docente
- `RoleHelper::isEstudiante()` - Verificar si es estudiante
- `RoleHelper::isJurado()` - Verificar si es jurado
- `RoleHelper::canManageUsers()` - Verificar si puede gestionar usuarios
- `RoleHelper::canManageSemilleros()` - Verificar si puede gestionar semilleros
- `RoleHelper::canGenerateReports()` - Verificar si puede generar reportes
- `RoleHelper::canEvaluate()` - Verificar si puede evaluar

## Configuración

El sistema utiliza el paquete `spatie/laravel-permission` que está configurado en:

- **Configuración**: `config/permission.php`
- **Migraciones**: Tablas `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- **Modelo User**: Incluye el trait `HasRoles`
- **Middleware**: Registrado en `bootstrap/app.php`

## Notas Importantes

1. **Roles por defecto**: Los nuevos usuarios se registran automáticamente con el rol "Estudiante"
2. **Cache**: Los permisos se almacenan en cache para mejor rendimiento
3. **Seguridad**: Siempre verificar permisos en el backend, no solo en el frontend
4. **Flexibilidad**: El sistema permite asignar múltiples roles a un usuario si es necesario
5. **Auditoría**: Considera implementar logs de cambios de roles para auditoría

