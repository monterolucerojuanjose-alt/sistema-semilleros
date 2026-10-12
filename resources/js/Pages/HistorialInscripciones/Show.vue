<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    inscripcion: { type: Object, required: true },
});

const ESTADOS = {
    pendiente: { texto: 'Pendiente de revisión', clase: 'bg-amber-100 text-amber-800' },
    aprobado: { texto: 'Aprobada', clase: 'bg-emerald-100 text-emerald-800' },
    rechazado: { texto: 'Rechazada', clase: 'bg-red-100 text-red-800' },
    cancelado: { texto: 'Cancelada', clase: 'bg-gray-100 text-gray-700' },
    asistio: { texto: 'Asistió', clase: 'bg-blue-100 text-blue-800' },
};

const ESTADOS_EVALUACION = {
    sin_evaluar: { texto: 'Sin evaluar', clase: 'bg-gray-100 text-gray-700' },
    en_revision: { texto: 'En revisión', clase: 'bg-amber-100 text-amber-800' },
    evaluado: { texto: 'Evaluada', clase: 'bg-emerald-100 text-emerald-800' },
};

const estado = computed(() => ESTADOS[props.inscripcion.estado] ?? { texto: props.inscripcion.estado, clase: 'bg-gray-100 text-gray-700' });
const estadoEvaluacion = computed(() => ESTADOS_EVALUACION[props.inscripcion.estado_evaluacion] ?? null);

// La nota llega como decimal en texto; se muestra siempre sobre la escala 0.0 – 5.0.
const nota = computed(() => {
    const valor = props.inscripcion.nota_final;
    return valor === null || valor === undefined ? null : Number(valor).toFixed(1);
});

const colorNota = computed(() => {
    if (nota.value === null) return 'text-gray-400';
    const n = Number(nota.value);
    if (n >= 90) return 'text-emerald-600';
    if (n >= 60) return 'text-blue-600';
    return 'text-red-600';
});

/**
 * Los participantes se guardan como JSON; según cómo se haya grabado la fila
 * pueden llegar ya decodificados o como cadena, así que se normaliza aquí.
 */
const participantes = computed(() => {
    const dato = props.inscripcion.participantes;
    if (!dato) return [];
    if (Array.isArray(dato)) return dato;

    try {
        const decodificado = JSON.parse(dato);
        return Array.isArray(decodificado) ? decodificado : [];
    } catch {
        return [];
    }
});

const formatFecha = (fecha) => {
    if (!fecha) return 'Sin registrar';
    return new Date(fecha).toLocaleDateString('es-ES', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

// Bloques de retroalimentación del jurado, para no repetir el mismo marcado.
const retroalimentacion = computed(() => [
    { titulo: 'Análisis del proyecto', texto: props.inscripcion.analisis_proyecto },
    { titulo: 'Fortalezas', texto: props.inscripcion.fortalezas },
    { titulo: 'Debilidades', texto: props.inscripcion.debilidades },
    { titulo: 'Recomendaciones', texto: props.inscripcion.recomendaciones },
].filter((bloque) => bloque.texto));
</script>

<template>
    <AppLayout title="Detalle de la inscripción">
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Volver al listado -->
            <Link
                :href="route('historial-inscripciones.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Volver al historial
            </Link>

            <!-- Encabezado -->
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-6 text-white">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-100">
                                {{ inscripcion.evento?.titulo ?? 'Evento eliminado' }}
                            </p>
                            <h1 class="mt-1 text-2xl font-bold">
                                {{ inscripcion.titulo_proyecto || 'Propuesta sin título' }}
                            </h1>
                            <p v-if="inscripcion.modalidad || inscripcion.area_participacion" class="mt-1 text-sm text-indigo-100">
                                {{ [inscripcion.modalidad, inscripcion.area_participacion].filter(Boolean).join(' · ') }}
                            </p>
                            <p v-else-if="inscripcion.carrera" class="mt-1 text-sm text-indigo-100">
                                {{ inscripcion.carrera }}
                            </p>
                        </div>

                        <div v-if="nota !== null" class="shrink-0 rounded-xl bg-white/15 px-5 py-3 text-center backdrop-blur">
                            <p class="text-3xl font-bold leading-none">{{ nota }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-wide text-indigo-100">sobre 100</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span :class="['rounded-full px-3 py-1 text-xs font-semibold', estado.clase]">
                            {{ estado.texto }}
                        </span>
                        <span v-if="estadoEvaluacion" :class="['rounded-full px-3 py-1 text-xs font-semibold', estadoEvaluacion.clase]">
                            Evaluación: {{ estadoEvaluacion.texto }}
                        </span>
                    </div>
                </div>

                <!-- Participante y fechas -->
                <div class="grid grid-cols-1 gap-6 px-6 py-5 md:grid-cols-2">
                    <div>
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Estudiante</h2>
                        <div class="flex items-center gap-3">
                            <Avatar :usuario="inscripcion.usuario" tamano="h-11 w-11" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ inscripcion.usuario?.name ?? 'Usuario eliminado' }}
                                </p>
                                <p class="truncate text-xs text-gray-500">{{ inscripcion.usuario?.email }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Fechas</h2>
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Inscripción</dt>
                                <dd class="text-right text-gray-900">{{ formatFecha(inscripcion.created_at) }}</dd>
                            </div>
                            <div v-if="inscripcion.fecha_revision" class="flex justify-between gap-4">
                                <dt class="text-gray-500">Revisión</dt>
                                <dd class="text-right text-gray-900">{{ formatFecha(inscripcion.fecha_revision) }}</dd>
                            </div>
                            <div v-if="inscripcion.fecha_evaluacion" class="flex justify-between gap-4">
                                <dt class="text-gray-500">Evaluación</dt>
                                <dd class="text-right text-gray-900">{{ formatFecha(inscripcion.fecha_evaluacion) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <!-- Motivo del rechazo -->
            <section v-if="inscripcion.motivo_rechazo" class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <h2 class="text-sm font-semibold text-red-900">Motivo del rechazo</h2>
                <p class="mt-1.5 whitespace-pre-line break-words text-sm text-red-800">{{ inscripcion.motivo_rechazo }}</p>
            </section>

            <!-- Propuesta -->
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold text-gray-900">Propuesta presentada</h2>

                <p v-if="inscripcion.descripcion_proyecto" class="whitespace-pre-line break-words text-sm leading-relaxed text-gray-700">
                    {{ inscripcion.descripcion_proyecto }}
                </p>
                <p v-else class="text-sm text-gray-400">No se registró una descripción.</p>

                <a
                    v-if="inscripcion.archivo_proyecto"
                    :href="`/storage/${inscripcion.archivo_proyecto}`"
                    target="_blank"
                    rel="noopener"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Abrir el archivo del proyecto
                </a>
            </section>

            <!-- Integrantes del grupo -->
            <section v-if="participantes.length" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold text-gray-900">Integrantes ({{ participantes.length }})</h2>
                <ul class="divide-y divide-gray-100">
                    <li v-for="(persona, i) in participantes" :key="i" class="flex flex-wrap items-center justify-between gap-2 py-2.5">
                        <span class="text-sm text-gray-900">{{ persona.nombre }}</span>
                        <span class="text-xs text-gray-500">{{ persona.email }}</span>
                    </li>
                </ul>
            </section>

            <!-- Programación asignada por el coordinador -->
            <section
                v-if="inscripcion.aula || inscripcion.horario || inscripcion.programacion"
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-3 font-semibold text-gray-900">Programación</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-if="inscripcion.aula" class="rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700">
                        Aula {{ inscripcion.aula }}
                    </span>
                    <span v-if="inscripcion.horario" class="rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700">
                        {{ inscripcion.horario }}
                    </span>
                </div>
                <p v-if="inscripcion.programacion" class="mt-3 whitespace-pre-line break-words text-sm text-gray-700">
                    {{ inscripcion.programacion }}
                </p>
            </section>

            <!-- Evaluación del jurado -->
            <section v-if="inscripcion.evaluador || retroalimentacion.length" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold text-gray-900">Evaluación</h2>
                    <span v-if="nota !== null" :class="['text-2xl font-bold', colorNota]">{{ nota }}</span>
                </div>

                <div v-if="inscripcion.evaluador" class="mb-4 flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                    <Avatar :usuario="inscripcion.evaluador" tamano="h-9 w-9" texto-tamano="text-xs" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ inscripcion.evaluador.name }}</p>
                        <p class="text-xs text-gray-500">Jurado asignado</p>
                    </div>
                </div>
                <p v-else class="mb-4 text-sm text-gray-500">Todavía no se ha asignado un jurado.</p>

                <div v-if="retroalimentacion.length" class="space-y-4">
                    <div v-for="bloque in retroalimentacion" :key="bloque.titulo">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ bloque.titulo }}</h3>
                        <p class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-gray-700">{{ bloque.texto }}</p>
                    </div>
                </div>
                <p v-else-if="inscripcion.evaluador" class="text-sm text-gray-400">
                    El jurado aún no ha registrado su retroalimentación.
                </p>
            </section>

            <!-- Observaciones internas -->
            <section v-if="inscripcion.observaciones" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-2 font-semibold text-gray-900">Observaciones</h2>
                <p class="whitespace-pre-line break-words text-sm text-gray-700">{{ inscripcion.observaciones }}</p>
            </section>
        </div>
    </AppLayout>
</template>
