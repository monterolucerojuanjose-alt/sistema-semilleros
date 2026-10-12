# Módulo de Eventos Académicos

## Descripción General

Se ha implementado un módulo completo de gestión de eventos académicos en el sistema de semilleros de investigación. Este módulo permite a los administradores y coordinadores crear y gestionar eventos, mientras que los estudiantes pueden ver e inscribirse en los eventos disponibles.

## Características Implementadas

### 1. Base de Datos

#### Tabla: `eventos`
Contiene toda la información de los eventos académicos:
- `id`: Identificador único
- `titulo`: Título del evento
- `descripcion`: Descripción detallada
- `imagen`: Ruta de la imagen del evento (opcional)
- `precio`: Precio de inscripción (0 para eventos gratuitos)
- `fecha_inicio`: Fecha y hora de inicio
- `fecha_fin`: Fecha y hora de finalización (opcional)
- `ubicacion`: Lugar donde se realizará el evento
- `cupo_maximo`: Límite de participantes (null para ilimitado)
- `estado`: activo, inactivo, finalizado
- `creado_por`: ID del usuario que creó el evento
- `timestamps`: created_at, updated_at

#### Tabla: `evento_participantes`
Tabla intermedia que relaciona estudiantes con eventos:
- `id`: Identificador único
- `evento_id`: ID del evento
- `user_id`: ID del estudiante
- `estado`: inscrito, asistio, no_asistio, cancelado
- `observaciones`: Notas adicionales (opcional)
- `timestamps`: created_at, updated_at
- **Restricción única**: Un estudiante no puede inscribirse dos veces al mismo evento (unique: evento_id, user_id)

### 2. Modelos Eloquent

#### Modelo: `Evento`
**Ubicación**: `app/Models/Evento.php`

**Relaciones**:
- `creador()`: Pertenece a un usuario (quien creó el evento)
- `participantes()`: Tiene muchos EventoParticipante
- `usuarios()`: Relación muchos a muchos con User a través de evento_participantes
- `estudiantesInscritos()`: Usuarios con estado 'inscrito'

**Scopes**:
- `activos()`: Filtra eventos activos
- `proximos()`: Eventos futuros ordenados por fecha
- `finalizados()`: Eventos finalizados

**Métodos útiles**:
- `tieneCuposDisponibles()`: Verifica si hay cupos disponibles
- `usuarioEstaInscrito($userId)`: Verifica si un usuario está inscrito
- `getCuposDisponiblesAttribute()`: Obtiene cupos disponibles

#### Modelo: `EventoParticipante`
**Ubicación**: `app/Models/EventoParticipante.php`

**Relaciones**:
- `evento()`: Pertenece a un Evento
- `usuario()`: Pertenece a un User

**Scopes**:
- `inscritos()`: Participantes con estado inscrito
- `asistieron()`: Participantes que asistieron
- `porEvento($eventoId)`: Filtrar por evento
- `porUsuario($userId)`: Filtrar por usuario

#### Modelo: `User` (actualizado)
Se agregaron las siguientes relaciones:
- `eventosInscritos()`: Eventos en los que está inscrito
- `participacionesEventos()`: Registros de EventoParticipante
- `eventosCreados()`: Eventos creados por el usuario

### 3. Controlador

#### EventoController
**Ubicación**: `app/Http/Controllers/EventoController.php`

**Métodos implementados**:

1. **index()**: Lista todos los eventos
   - Admin/Coordinador: Ve todos los eventos con información de participantes
   - Estudiante: Ve solo eventos activos con estado de inscripción

2. **create()**: Formulario para crear evento (Admin/Coordinador)

3. **store()**: Guarda un nuevo evento
   - Valida todos los campos
   - Maneja la subida de imagen
   - Solo Admin/Coordinador

4. **show($evento)**: Muestra detalles de un evento
   - Estudiantes ven información de inscripción
   - Admin/Coordinador ven lista de participantes

5. **edit($evento)**: Formulario de edición (Admin/Coordinador)

6. **update($evento)**: Actualiza un evento existente
   - Valida campos
   - Maneja reemplazo de imagen
   - Solo Admin/Coordinador

7. **destroy($evento)**: Elimina un evento (Admin/Coordinador)

8. **inscribir($evento)**: Inscribe un estudiante en un evento
   - Valida que sea estudiante
   - Verifica que el evento esté activo
   - Verifica que no esté ya inscrito
   - Valida cupos disponibles

9. **cancelarInscripcion($evento)**: Cancela inscripción de un estudiante

10. **participantes($evento)**: Lista estudiantes inscritos (Admin/Coordinador)

### 4. Rutas

**Ubicación**: `routes/web.php`

#### Rutas Generales (todos los usuarios autenticados):
```php
GET  /eventos                    // Lista de eventos
```

#### Rutas para Admin y Coordinador:
```php
GET    /eventos/create           // Formulario crear evento
POST   /eventos                  // Guardar nuevo evento
GET    /eventos/{evento}/edit    // Formulario editar evento
PUT    /eventos/{evento}         // Actualizar evento
DELETE /eventos/{evento}         // Eliminar evento
GET    /eventos/{evento}/participantes // Ver inscritos
```

#### Rutas para Estudiantes:
```php
POST /eventos/{evento}/inscribir              // Inscribirse
POST /eventos/{evento}/cancelar-inscripcion   // Cancelar inscripción
```

#### Ruta compartida:
```php
GET /eventos/{evento}            // Ver detalles del evento
```

### 5. Vistas Vue.js

#### Index.vue
**Ubicación**: `resources/js/Pages/Eventos/Index.vue`

**Características**:
- Vista diferente según el rol (Admin vs Estudiante)
- Filtros por búsqueda y estado
- Tarjetas de eventos con imagen
- Información de cupos y fecha
- Botones de inscripción para estudiantes
- Botones de gestión para administradores
- Indicador visual de inscripción del estudiante

#### Create.vue
**Ubicación**: `resources/js/Pages/Eventos/Create.vue`

**Características**:
- Formulario completo de creación
- Validación de campos requeridos
- Vista previa de imagen
- Selector de fecha y hora
- Campo de precio con formato
- Selector de estado del evento

#### Edit.vue
**Ubicación**: `resources/js/Pages/Eventos/Edit.vue`

**Características**:
- Similar a Create.vue
- Muestra imagen actual del evento
- Permite cambiar la imagen
- Pre-llena todos los campos con datos existentes

#### Show.vue
**Ubicación**: `resources/js/Pages/Eventos/Show.vue`

**Características**:
- Vista detallada del evento
- Información completa en sidebar
- Botón de inscripción para estudiantes
- Panel de acciones para administradores
- Lista de primeros 5 participantes (admin)
- Estado visual de inscripción

#### Participantes.vue
**Ubicación**: `resources/js/Pages/Eventos/Participantes.vue`

**Características**:
- Tabla completa de inscritos
- Filtros por búsqueda y estado
- Información del estudiante (nombre, email)
- Estado de participación
- Fecha de inscripción
- Observaciones
- Estadísticas de cupos

### 6. Validaciones Implementadas

#### Validación a nivel de Base de Datos:
- Restricción única: Un estudiante no puede inscribirse dos veces en el mismo evento
- Claves foráneas con cascada en eliminación
- Campos requeridos definidos en migraciones

#### Validación a nivel de Controlador:
```php
// Al crear/editar evento
'titulo' => 'required|string|max:255',
'descripcion' => 'required|string',
'imagen' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
'precio' => 'required|numeric|min:0',
'fecha_inicio' => 'required|date',
'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
'ubicacion' => 'nullable|string|max:255',
'cupo_maximo' => 'nullable|integer|min:1',
'estado' => 'required|in:activo,inactivo,finalizado',
```

#### Validación de Inscripción:
- Usuario debe tener rol de Estudiante
- Evento debe estar activo
- Usuario no debe estar ya inscrito
- Debe haber cupos disponibles

### 7. Control de Acceso por Roles

#### Administrador y Coordinador pueden:
- ✅ Ver todos los eventos (activos, inactivos, finalizados)
- ✅ Crear nuevos eventos
- ✅ Editar eventos existentes
- ✅ Eliminar eventos
- ✅ Ver lista completa de estudiantes inscritos
- ✅ Ver estadísticas de participación

#### Estudiante puede:
- ✅ Ver eventos activos
- ✅ Ver detalles de cualquier evento
- ✅ Inscribirse en eventos activos
- ✅ Cancelar su inscripción
- ❌ No puede crear/editar/eliminar eventos
- ❌ No puede ver información de otros estudiantes

### 8. Seeder de Datos de Prueba

**Ubicación**: `database/seeders/EventosSeeder.php`

**Datos generados**:
- 6 eventos de ejemplo (5 activos, 1 finalizado)
- Eventos con diferentes características:
  - Gratuitos y de pago
  - Con y sin límite de cupos
  - Con y sin imagen
  - Diferentes fechas y ubicaciones
- Inscripciones aleatorias de estudiantes en eventos

**Eventos de ejemplo**:
1. Congreso Nacional de Investigación Científica 2025 (Gratis, 100 cupos)
2. Workshop: Metodologías de Investigación Cualitativa ($50,000, 30 cupos)
3. Simposio de Innovación y Emprendimiento ($25,000, 50 cupos)
4. Encuentro de Semilleros de Investigación (Gratis, sin límite)
5. Conferencia: Tendencias en Inteligencia Artificial ($30,000, 200 cupos)
6. Feria de Proyectos de Investigación 2024 (Finalizado)

## Instalación y Configuración

### 1. Las migraciones ya están ejecutadas
```bash
php artisan migrate:fresh --seed
```

### 2. Para ejecutar solo el seeder de eventos
```bash
php artisan db:seed --class=EventosSeeder
```

### 3. Compilar assets de frontend
```bash
npm run build
# o para desarrollo
npm run dev
```

## Uso del Sistema

### Como Administrador/Coordinador:

1. **Crear un evento**:
   - Ir a `/eventos`
   - Clic en "Nuevo Evento"
   - Completar formulario
   - Subir imagen (opcional)
   - Guardar

2. **Ver estudiantes inscritos**:
   - En la lista de eventos, clic en "Ver Inscritos"
   - O en el detalle del evento, clic en "Ver Participantes"

3. **Editar/Eliminar evento**:
   - Botones disponibles en cada tarjeta de evento
   - O en el detalle del evento

### Como Estudiante:

1. **Ver eventos disponibles**:
   - Ir a `/eventos`
   - Ver solo eventos activos

2. **Inscribirse a un evento**:
   - Clic en "Inscribirse" en la tarjeta del evento
   - O en el detalle del evento
   - Confirmar inscripción

3. **Cancelar inscripción**:
   - Clic en "Cancelar Inscripción"
   - Confirmar cancelación

## Características Técnicas

### Seguridad:
- ✅ Middleware de autenticación en todas las rutas
- ✅ Middleware de roles para acciones administrativas
- ✅ Validación de permisos en controlador
- ✅ CSRF protection en formularios
- ✅ Validación de tipos de archivo en subida de imágenes

### Optimización:
- ✅ Eager loading de relaciones para evitar N+1 queries
- ✅ Uso de scopes para queries reutilizables
- ✅ Índices en claves foráneas
- ✅ Compilación optimizada de assets con Vite

### Experiencia de Usuario:
- ✅ Interfaz responsive (móvil, tablet, desktop)
- ✅ Mensajes de confirmación antes de acciones críticas
- ✅ Flash messages para feedback de operaciones
- ✅ Indicadores visuales de estado
- ✅ Filtros y búsqueda en tiempo real
- ✅ Formateo de fechas y moneda en español

## Archivos Creados/Modificados

### Archivos Nuevos:
- `database/migrations/2025_10_09_150330_create_eventos_table.php` (actualizado)
- `database/migrations/2025_10_09_150419_create_evento_participantes_table.php` (actualizado)
- `database/seeders/EventosSeeder.php`
- `resources/js/Pages/Eventos/Index.vue`
- `resources/js/Pages/Eventos/Create.vue`
- `resources/js/Pages/Eventos/Edit.vue`
- `resources/js/Pages/Eventos/Show.vue`
- `resources/js/Pages/Eventos/Participantes.vue`

### Archivos Modificados:
- `app/Models/Evento.php` (completamente implementado)
- `app/Models/EventoParticipante.php` (completamente implementado)
- `app/Models/User.php` (agregadas relaciones con eventos)
- `app/Http/Controllers/EventoController.php` (completamente implementado)
- `routes/web.php` (agregadas rutas de eventos con control de acceso)
- `database/seeders/DatabaseSeeder.php` (agregado EventosSeeder)

## Pruebas del Sistema

### Cuentas de Prueba:
```
Administrador:
Email: admin@semilleros.edu
Password: admin123

Estudiante:
Email: estudiante@proyectos.edu
Password: password123
```

### Escenarios de Prueba:

1. **Crear Evento** (Admin):
   - Login como admin
   - Ir a /eventos
   - Crear nuevo evento con todos los campos
   - Verificar que aparece en la lista

2. **Inscripción de Estudiante**:
   - Login como estudiante
   - Ver eventos disponibles
   - Inscribirse en un evento
   - Verificar mensaje de éxito
   - Verificar que aparece "Ya estás inscrito"

3. **Validación de Doble Inscripción**:
   - Intentar inscribirse nuevamente
   - Verificar mensaje de error

4. **Verificar Cupos**:
   - Crear evento con cupo limitado
   - Inscribir estudiantes hasta llenar cupos
   - Verificar que no permite más inscripciones

5. **Ver Participantes** (Admin):
   - Login como admin
   - Ver participantes de un evento
   - Verificar lista completa con filtros

## Próximas Mejoras Sugeridas

- [ ] Sistema de notificaciones por email al inscribirse
- [ ] Recordatorios automáticos antes del evento
- [ ] Generación de certificados de asistencia
- [ ] QR codes para registro de asistencia
- [ ] Exportar lista de participantes a Excel/PDF
- [ ] Galería de fotos del evento
- [ ] Sistema de evaluación del evento
- [ ] Calendario de eventos integrado
- [ ] Pagos en línea para eventos de pago

## Soporte y Contacto

Para cualquier duda o problema con el módulo de eventos, contactar al equipo de desarrollo.

---

**Fecha de Implementación**: Octubre 2025  
**Versión**: 1.0  
**Estado**: ✅ Completado y Funcional










