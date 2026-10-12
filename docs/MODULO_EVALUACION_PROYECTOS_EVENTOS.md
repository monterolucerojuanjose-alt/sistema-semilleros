# Módulo de evaluación de propuestas de evento

Describe cómo se revisan y califican las propuestas que los estudiantes presentan a un evento, desde la inscripción hasta que el estudiante consulta su nota.

## Flujo

1. **Estudiante** se inscribe a un evento enviando su propuesta: título, descripción, archivo (PDF/DOC/DOCX), integrantes, **modalidad de participación** y **área de participación**.
2. **Coordinador o Administrador** revisa la propuesta en *Propuestas pendientes* y puede:
   - **Aprobarla**, asignando aula, horario, programación y un **jurado evaluador**.
   - **Rechazarla**, indicando el motivo. El estudiante puede corregir y reenviar la misma propuesta; el contador `intentos_envio` registra cada reenvío.
3. **Jurado** ve la propuesta en *Evaluar proyectos*, descarga el archivo y la califica con una **rúbrica**.
4. **Estudiante** consulta en *Mis inscripciones* el estado de su propuesta y el jurado asignado, y en *Mis notas* la calificación y las observaciones.

## Catálogos

Las listas de modalidades y áreas de participación están definidas una sola vez en `app/Support/CatalogosEvento.php` y se reutilizan en migraciones, validación y vistas.

**Modalidades:** propuesta o idea de investigación; investigación en curso; investigación terminada; ideas de negocio; plan de negocio; empresa puesta en marcha; proyecto de desarrollo tecnológico e innovación.

**Áreas de participación:** Ciencias Agrarias; Biológicas y del Mar; Ciencias de la Salud; Ciencias Exactas y de la Tierra; Ciencias Humanas; Ciencias Sociales; Navales y de Seguridad; Ingeniería; Lingüística, Artes y Letras.

## Modelo de datos

Tabla `evento_participantes` (modelo `EventoParticipante`):

| Campo | Descripción |
|---|---|
| `evento_id`, `user_id` | Evento y estudiante (par único) |
| `modalidad`, `area_participacion` | Clasificación de la propuesta |
| `carrera` | Campo anterior a la convocatoria actual; se conserva solo para inscripciones antiguas |
| `titulo_proyecto`, `descripcion_proyecto`, `archivo_proyecto`, `participantes` | Contenido de la propuesta |
| `estado` | Admisión: `pendiente`, `aprobado`, `rechazado`, `cancelado`… |
| `intentos_envio` | Número de veces que se ha enviado la propuesta |
| `aula`, `horario`, `programacion` | Logística, asignada al aprobar |
| `evaluador_id` | Jurado asignado |
| `estado_evaluacion` | `sin_evaluar`, `en_revision`, `evaluado` |
| `nota_final`, `analisis_proyecto`, `fortalezas`, `debilidades`, `recomendaciones`, `fecha_evaluacion` | Resultado de la evaluación |

El estado de admisión y el de evaluación son independientes: una propuesta puede estar aprobada y aún sin calificar.

## Evaluación con rúbrica

Cada calificación se registra en `evaluaciones` (con `evento_participante_id`) y su detalle por criterio en `detalle_evaluaciones`. Una fila de `evaluaciones` califica o un proyecto del catálogo (`proyecto_id`) o una propuesta de evento (`evento_participante_id`), nunca ambos. Un jurado solo puede evaluar una vez la misma propuesta.

- **Selección de la rúbrica.** Las rúbricas pueden ligarse a un evento y a una modalidad. Al calificar, el jurado ve primero la rúbrica de ese evento y modalidad; si no existe ninguna específica se ofrecen las rúbricas genéricas (sin evento ni modalidad).
- **Cálculo.** La nota de cada criterio se normaliza sobre su puntaje máximo y se pondera por su peso porcentual. La nota final va **de 0 a 100** (`Evaluacion::NOTA_MAXIMA`).
- **Sincronización.** Además del detalle, la nota y el estado se guardan en `evento_participantes` (`nota_final`, `estado_evaluacion`, `fecha_evaluacion`), que es lo que leen los reportes y las pantallas del estudiante.
- **Descripción de la rúbrica.** Es obligatoria y se muestra al jurado mientras califica, para que sepa cómo aplicar los criterios.

## Rutas principales

| Ruta | Acción |
|---|---|
| `GET /propuestas/pendientes` | Bandeja de propuestas por revisar (Coordinador/Administrador) |
| `POST /propuestas/{participante}/aprobar` | Aprueba y asigna jurado, aula y horario |
| `POST /propuestas/{participante}/rechazar` | Rechaza indicando el motivo |
| `GET /mis-proyectos-evaluar` | Propuestas asignadas al jurado |
| `GET /proyectos/{participante}/evaluar` | Formulario de calificación con rúbrica |
| `POST /proyectos/{participante}/evaluar` | Guarda la evaluación |

## Archivos relevantes

- `app/Http/Controllers/EventoController.php` — inscripción, aprobación/rechazo y calificación.
- `app/Models/EventoParticipante.php`, `app/Models/Evaluacion.php`, `app/Models/Rubrica.php`.
- `resources/js/Pages/Eventos/` — `PropuestasPendientes`, `VerPropuesta`, `ProyectosAsignados`, `EvaluarProyecto`.
- `resources/js/Pages/Inscripciones/MisInscripciones.vue` — vista del estudiante.
- `tests/Usabilidad/` — `CoordinadorTest`, `JuradoTest`, `EstudianteTest`, `CorreccionPropuestaTest`.
