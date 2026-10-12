# DIAGRAMAS DE ARQUITECTURA PARA TESIS

Este documento contiene diagramas en Mermaid listos para copiar en la tesis, exportar como imagen o convertir a PDF. Los diagramas fueron elaborados con base en la estructura real del proyecto.

## 1. Diagrama general de arquitectura

```mermaid
flowchart LR
    U[Usuario] --> N[Navegador]
    N --> V[Vue 3 + Inertia.js]
    V --> R[Rutas Laravel]
    R --> M[Middleware Auth Role Permission]
    M --> C[Controladores]
    C --> Q[Requests y Validaciones]
    C --> E[Modelos Eloquent]
    E --> DB[(MySQL)]
    C --> ST[Storage de archivos]
    C --> REP[Reportes PDF y Excel]
    C --> JOB[Jobs en cola]
    JOB --> MAIL[Correo y notificaciones]
    E --> OBS[Observers de auditoria]
    OBS --> AUD[(Auditorias)]
    OBS --> NOTI[(Notificaciones)]
```

### Lectura recomendada

- El usuario interactua desde el navegador.
- Vue 3 e Inertia gestionan la presentacion sin separar el frontend en otro despliegue.
- Laravel centraliza rutas, middleware, controladores y acceso a datos.
- La base de datos MySQL persiste la informacion principal.
- Existen servicios transversales para storage, auditoria, notificaciones, colas y reportes.

## 2. Diagrama por capas

```mermaid
flowchart TB
    subgraph Presentacion
        P1[Blade raiz app.blade.php]
        P2[Vue Pages]
        P3[Layouts y Components]
        P4[Inertia.js]
    end

    subgraph Aplicacion
        A1[web.php]
        A2[Controllers]
        A3[Middleware]
        A4[Form Requests]
        A5[Policies]
    end

    subgraph Datos
        D1[Models Eloquent]
        D2[Migrations]
        D3[MySQL]
        D4[Storage]
    end

    subgraph Transversal
        T1[Observers]
        T2[Jobs]
        T3[Notificaciones]
        T4[Auditoria]
        T5[Reportes]
    end

    Presentacion --> Aplicacion
    Aplicacion --> Datos
    Aplicacion --> Transversal
    Datos --> Transversal
```

## 3. Flujo de una solicitud tipica

El siguiente diagrama modela un caso real: crear un proyecto.

```mermaid
sequenceDiagram
    actor Usuario
    participant Vue as Vue Page
    participant Route as Ruta Laravel
    participant MW as Middleware
    participant Req as StoreProyectoRequest
    participant Ctrl as ProyectoController
    participant Model as Proyecto
    participant DB as MySQL
    participant Obs as AuditoriaObserver
    participant Noti as Notificacion

    Usuario->>Vue: Envia formulario de proyecto
    Vue->>Route: POST /proyectos
    Route->>MW: Verificar auth y rol
    MW->>Req: Validar datos
    Req->>Ctrl: Datos validados
    Ctrl->>Model: crearConCodigo(...)
    Model->>DB: INSERT proyecto
    DB-->>Model: Proyecto creado
    Model-->>Ctrl: Resultado
    Ctrl-->>Vue: Redirect + mensaje success
    Model->>Obs: Evento created
    Obs->>DB: Registrar auditoria
    Obs->>Noti: Crear notificacion si aplica
```

## 4. Diagrama entidad-relacion resumido

```mermaid
erDiagram
    USERS ||--o{ PROYECTOS : autor
    USERS ||--o{ SEMILLEROS : tutor
    USERS }o--o{ SEMILLEROS : miembro
    SEMILLEROS ||--o{ PROYECTOS : contiene
    PROYECTOS ||--o{ VERSIONES_PROYECTO : tiene
    PROYECTOS ||--o{ INSCRIPCIONES_EVENTO : se_inscribe
    EVENTOS ||--o{ INSCRIPCIONES_EVENTO : recibe
    EVENTOS ||--o{ EVENTO_PARTICIPANTES : registra
    USERS ||--o{ EVENTO_PARTICIPANTES : participa
    USERS ||--o{ EVALUACIONES : jurado
    RUBRICAS ||--o{ EVALUACIONES : define
    PROYECTOS ||--o{ EVALUACIONES : recibe
    USERS ||--o{ NOTIFICACIONES : recibe
    USERS ||--o{ AUDITORIAS : genera
    USERS ||--o{ CERTIFICATES : posee
    EVENTOS ||--o{ CERTIFICATES : asocia

    USERS {
        bigint id PK
        string name
        string email
        string estado
    }

    SEMILLEROS {
        bigint id PK
        string nombre
        string linea_tematica
        bigint tutor_id FK
        string estado
    }

    PROYECTOS {
        bigint id PK
        string titulo
        string codigo
        string estado
        bigint semillero_id FK
        bigint autor_id FK
    }

    VERSIONES_PROYECTO {
        bigint id PK
        bigint proyecto_id FK
        string archivo
        bigint creado_por FK
    }

    EVENTOS {
        bigint id PK
        string titulo
        datetime fecha_inicio
        datetime fecha_fin
        string estado
    }

    EVENTO_PARTICIPANTES {
        bigint id PK
        bigint evento_id FK
        bigint user_id FK
        string estado
        bigint evaluador_id FK
        string estado_evaluacion
    }

    INSCRIPCIONES_EVENTO {
        bigint id PK
        bigint proyecto_id FK
        bigint evento_id FK
        string tipo
        string estado
        string codigo_inscripcion
    }

    RUBRICAS {
        bigint id PK
        string nombre
    }

    EVALUACIONES {
        bigint id PK
        bigint proyecto_id FK
        bigint jurado_id FK
        bigint rubrica_id FK
        decimal nota_final
        string estado
    }

    NOTIFICACIONES {
        bigint id PK
        bigint usuario_id FK
        string tipo
        boolean leida
    }

    AUDITORIAS {
        bigint id PK
        bigint usuario_id FK
        string accion
        string entidad_afectada
    }

    CERTIFICATES {
        bigint id PK
        bigint user_id FK
        bigint evento_id FK
        string codigo
        string estado
    }
```

## 5. Diagrama de modulos funcionales

```mermaid
flowchart LR
    CORE[Autenticacion y autorizacion]
    SEM[Modulo de semilleros]
    PRO[Modulo de proyectos]
    INS[Modulo de inscripciones]
    EVT[Modulo de eventos]
    RUB[Modulo de rubricas]
    EVA[Modulo de evaluaciones]
    CER[Modulo de certificados]
    NOT[Modulo de notificaciones]
    AUD[Modulo de auditoria]
    REP[Modulo de reportes]

    CORE --> SEM
    CORE --> PRO
    CORE --> INS
    CORE --> EVT
    CORE --> RUB
    CORE --> EVA
    CORE --> CER
    CORE --> NOT
    CORE --> AUD
    CORE --> REP

    SEM --> PRO
    PRO --> INS
    EVT --> INS
    RUB --> EVA
    PRO --> EVA
    AUD --> NOT
    REP --> PRO
    REP --> INS
    REP --> EVA
    REP --> AUD
```

## 6. Fuente tecnica de los diagramas

Los diagramas se sustentan en los siguientes archivos del proyecto:

- `composer.json`
- `package.json`
- `bootstrap/app.php`
- `routes/web.php`
- `app/Http/Controllers/`
- `app/Models/`
- `app/Providers/AppServiceProvider.php`
- `app/Observers/AuditoriaObserver.php`
- `database/migrations/`

## 7. Como convertir estos diagramas en evidencia visual

Opciones recomendadas:

1. Copiar el bloque Mermaid a [Mermaid Live Editor](https://mermaid.live/) y exportarlo como PNG o SVG.
2. Pegarlo en GitHub, Obsidian, Typora o VS Code con extension Mermaid para renderizarlo.
3. Exportar el diagrama como imagen y usarlo directamente en el capitulo de arquitectura o en anexos.

## 8. Recomendacion de uso en la tesis

- Usa el diagrama general de arquitectura en el capitulo de diseno o arquitectura.
- Usa el diagrama por capas en la seccion metodologica o de construccion.
- Usa el DER resumido en el capitulo de base de datos.
- Usa el diagrama de flujo de solicitud para explicar un caso de uso critico.
- Usa el diagrama de modulos funcionales para mostrar el alcance del sistema.
