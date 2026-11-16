<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import NotaCircular from '@/Components/NotaCircular.vue';
import { aprobada, formatoNota, nivelNota } from '@/utils/notas';

const props = defineProps({
    resultado: Object,
    rubrica: { type: Object, default: null },
    observacionesRubrica: { type: String, default: null },
    archivoUrl: { type: String, default: null },
});

const evaluado = computed(() => props.resultado.estado_evaluacion === 'evaluado');
const nivel = computed(() => nivelNota(props.resultado.nota_final));

// Estado de la propuesta en el evento (no confundir con el de la evaluación).
const ESTADO_PROPUESTA = {
    inscrito: ['Inscrita', 'bg-blue-100 text-blue-800'],
    pendiente: ['Pendiente de revisión', 'bg-amber-100 text-amber-800'],
    aprobado: ['Aprobada', 'bg-emerald-100 text-emerald-800'],
    rechazado: ['Rechazada', 'bg-red-100 text-red-800'],
};
const ESTADO_EVALUACION = {
    sin_evaluar: ['Sin evaluar', 'bg-gray-100 text-gray-700'],
    en_revision: ['En revisión', 'bg-amber-100 text-amber-800'],
    evaluado: ['Evaluado', 'bg-emerald-100 text-emerald-800'],
};
const estadoPropuesta = computed(() => ESTADO_PROPUESTA[props.resultado.estado] ?? [props.resultado.estado ?? 'Sin estado', 'bg-gray-100 text-gray-700']);
const estadoEvaluacion = computed(() => ESTADO_EVALUACION[props.resultado.estado_evaluacion] ?? [props.resultado.estado_evaluacion, 'bg-gray-100 text-gray-700']);

const retroalimentacion = computed(() => [
    { titulo: 'Análisis general', texto: props.resultado.analisis_proyecto, caja: 'border-gray-200 bg-gray-50' },
    { titulo: 'Fortalezas', texto: props.resultado.fortalezas, caja: 'border-emerald-200 bg-emerald-50' },
    { titulo: 'Aspectos por mejorar', texto: props.resultado.debilidades, caja: 'border-amber-200 bg-amber-50' },
    { titulo: 'Recomendaciones', texto: props.resultado.recomendaciones, caja: 'border-indigo-200 bg-indigo-50' },
    { titulo: 'Observaciones de la rúbrica', texto: props.observacionesRubrica, caja: 'border-gray-200 bg-gray-50' },
].filter((b) => b.texto && String(b.texto).trim() !== ''));

const datos = computed(() => [
    ['Modalidad', props.resultado.modalidad],
    ['Área de participación', props.resultado.area_participacion ?? props.resultado.carrera],
    ['Aula', props.resultado.aula],
    ['Horario', props.resultado.horario],
    ['Inscripción', fechaLarga(props.resultado.created_at)],
].filter(([, valor]) => valor));

const porcentajeCriterio = (c) => (c.maximo > 0 ? Math.round((c.puntaje / c.maximo) * 100) : 0);

function fechaLarga(fecha) {
    return fecha ? new Date(fecha).toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' }) : '';
}
</script>

<template>
    <AppLayout title="Detalle de evaluación">
        <div class="mx-auto max-w-5xl space-y-6">
            <Link :href="route('resultados-evaluacion.index')" class="animar-entrada inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                Resultados de evaluación
            </Link>

            <!-- Cabecera -->
            <section class="animar-entrada rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col-reverse gap-6 md:flex-row md:items-center">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap gap-2">
                            <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', estadoEvaluacion[1]]">{{ estadoEvaluacion[0] }}</span>
                            <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', estadoPropuesta[1]]">Propuesta {{ estadoPropuesta[0].toLowerCase() }}</span>
                        </div>
                        <h1 class="mt-3 break-words text-2xl font-bold leading-tight text-gray-900">{{ resultado.titulo_proyecto || 'Propuesta sin título' }}</h1>
                        <p class="mt-1 text-sm text-gray-500">{{ resultado.evento?.titulo }}</p>
                        <p v-if="resultado.descripcion_proyecto" class="mt-3 line-clamp-4 break-words text-sm leading-relaxed text-gray-600">{{ resultado.descripcion_proyecto }}</p>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex items-center gap-3">
                                <Avatar :usuario="resultado.usuario" tamano="h-10 w-10" />
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-500">Estudiante</p>
                                    <p class="truncate text-sm font-medium text-gray-900">{{ resultado.usuario?.name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ resultado.usuario?.email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Avatar :usuario="resultado.evaluador" tamano="h-10 w-10" />
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-500">Jurado</p>
                                    <p class="truncate text-sm font-medium text-gray-900">{{ resultado.evaluador?.name ?? 'Sin asignar' }}</p>
                                    <p v-if="resultado.fecha_evaluacion" class="text-xs text-gray-500">Calificó el {{ fechaLarga(resultado.fecha_evaluacion) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col items-center rounded-xl bg-gray-50 px-8 py-5">
                        <template v-if="evaluado">
                            <NotaCircular :nota="resultado.nota_final" tamano="lg" mostrar-nivel />
                            <p :class="['mt-3 text-xs font-medium', aprobada(resultado.nota_final) ? 'text-emerald-700' : 'text-red-700']">
                                {{ aprobada(resultado.nota_final) ? 'Aprobada' : 'Por debajo del mínimo' }}
                            </p>
                        </template>
                        <template v-else>
                            <span class="flex h-[120px] w-[120px] items-center justify-center rounded-full border-[10px] border-gray-100 text-center text-xs font-medium text-gray-500">Aún sin<br />calificar</span>
                        </template>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <section class="animar-entrada retraso-1 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 lg:col-span-3">
                    <h2 class="text-base font-semibold text-gray-900">Puntaje por criterio</h2>
                    <template v-if="rubrica">
                        <p class="mt-1 text-sm text-gray-500">Rúbrica: <span class="font-medium text-gray-700">{{ rubrica.nombre }}</span></p>
                        <ul class="mt-5 space-y-5">
                            <li v-for="c in rubrica.criterios" :key="c.nombre">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="min-w-0 break-words text-sm font-medium text-gray-900">{{ c.nombre }}</p>
                                    <p class="shrink-0 text-sm tabular-nums"><span class="font-semibold text-gray-900">{{ c.puntaje }}</span><span class="text-gray-400"> / {{ c.maximo }}</span></p>
                                </div>
                                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div :class="['h-full rounded-full transition-all duration-700', nivelNota(porcentajeCriterio(c)).barra]" :style="{ width: porcentajeCriterio(c) + '%' }"></div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Peso {{ formatoNota(c.peso) }} % · aporta {{ formatoNota(c.aporte, 2) }} puntos</p>
                            </li>
                        </ul>
                        <div class="mt-6 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                            <span class="text-sm font-medium text-gray-700">Nota final</span>
                            <span :class="['text-lg font-bold tabular-nums', nivel.texto_color]">{{ formatoNota(resultado.nota_final, 2) }} <span class="text-sm font-normal text-gray-400">/ 100</span></span>
                        </div>
                    </template>
                    <p v-else class="mt-4 rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">
                        {{ evaluado ? 'Esta propuesta se calificó antes de que el sistema guardara el puntaje por criterio; sólo está disponible la nota final.' : 'El desglose aparecerá cuando el jurado califique con la rúbrica.' }}
                    </p>
                </section>

                <div class="space-y-6 lg:col-span-2">
                    <section class="animar-entrada retraso-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-base font-semibold text-gray-900">Retroalimentación del jurado</h2>
                        <div v-if="retroalimentacion.length" class="mt-4 space-y-3">
                            <div v-for="b in retroalimentacion" :key="b.titulo" :class="['rounded-lg border p-4', b.caja]">
                                <h3 class="text-sm font-semibold text-gray-900">{{ b.titulo }}</h3>
                                <p class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-gray-700">{{ b.texto }}</p>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-gray-500">{{ evaluado ? 'El jurado no dejó comentarios escritos.' : 'Todavía no hay retroalimentación.' }}</p>
                    </section>

                    <section class="animar-entrada retraso-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-base font-semibold text-gray-900">Datos de la propuesta</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div v-for="[etiqueta, valor] in datos" :key="etiqueta" class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-3">
                                <dt class="text-gray-500">{{ etiqueta }}</dt>
                                <dd class="break-words font-medium text-gray-900 sm:text-right">{{ valor }}</dd>
                            </div>
                        </dl>
                        <a
                            v-if="archivoUrl"
                            :href="archivoUrl"
                            target="_blank"
                            rel="noopener"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            Descargar documento presentado
                        </a>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
