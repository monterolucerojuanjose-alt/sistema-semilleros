<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AvisoFlash from '@/Components/AvisoFlash.vue';
import NotaCircular from '@/Components/NotaCircular.vue';
import { ESCALA, formatoNota, nivelNota } from '@/utils/notas';

const props = defineProps({
    evaluadas: { type: Array, default: () => [] },
    pendientes: { type: Array, default: () => [] },
    resumen: { type: Object, default: () => ({}) },
    notaAprobatoria: { type: Number, default: 60 },
});

// Filtro por origen de la nota: todas, proyectos de semillero o eventos.
const filtro = ref('todas');
const filtros = computed(() => [
    { valor: 'todas', texto: 'Todas', cantidad: props.evaluadas.length },
    { valor: 'proyecto', texto: 'Proyectos', cantidad: props.evaluadas.filter((n) => n.tipo === 'proyecto').length },
    { valor: 'evento', texto: 'Eventos', cantidad: props.evaluadas.filter((n) => n.tipo === 'evento').length },
]);

const evaluadasFiltradas = computed(() =>
    filtro.value === 'todas' ? props.evaluadas : props.evaluadas.filter((n) => n.tipo === filtro.value)
);

const indicadores = computed(() => [
    {
        etiqueta: 'Promedio general',
        valor: formatoNota(props.resumen.promedio),
        pie: props.resumen.promedio === null || props.resumen.promedio === undefined ? 'aún sin notas' : nivelNota(props.resumen.promedio).texto,
        color: nivelNota(props.resumen.promedio).texto_color,
        icono: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    },
    {
        etiqueta: 'Mejor nota',
        valor: formatoNota(props.resumen.mejor_nota),
        pie: 'tu puntaje más alto',
        color: nivelNota(props.resumen.mejor_nota).texto_color,
        icono: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
    },
    {
        etiqueta: 'Aprobadas',
        valor: `${props.resumen.aprobadas ?? 0} de ${props.resumen.total ?? 0}`,
        pie: `nota mínima ${formatoNota(props.notaAprobatoria)}`,
        color: 'text-gray-900',
        icono: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        etiqueta: 'Por calificar',
        valor: props.resumen.pendientes ?? 0,
        pie: (props.resumen.pendientes ?? 0) === 1 ? 'trabajo en espera' : 'trabajos en espera',
        color: (props.resumen.pendientes ?? 0) > 0 ? 'text-amber-700' : 'text-gray-900',
        icono: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
]);

const fechaLarga = (fecha) => {
    if (!fecha) return 'Sin fecha';
    return new Date(fecha).toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <AppLayout title="Mis notas">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="animar-entrada">
                <h1 class="text-2xl font-bold text-gray-900">Mis notas</h1>
                <p class="mt-1 text-sm text-gray-500">Calificaciones de tus proyectos de semillero y de tus propuestas en eventos, sobre 100.</p>
            </div>

            <AvisoFlash />

            <!-- Indicadores -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    v-for="(indicador, i) in indicadores"
                    :key="indicador.etiqueta"
                    :class="['animar-entrada flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm', `retraso-${i + 1}`]"
                >
                    <span class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 sm:flex lg:hidden xl:flex">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="indicador.icono" /></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ indicador.etiqueta }}</p>
                        <p :class="['mt-0.5 text-2xl font-bold tabular-nums', indicador.color]">{{ indicador.valor }}</p>
                        <p class="text-xs leading-snug text-gray-500">{{ indicador.pie }}</p>
                    </div>
                </div>
            </div>

            <!-- En espera de calificación -->
            <section v-if="pendientes.length" class="animar-entrada rounded-xl border border-amber-200 bg-amber-50/70 p-5">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-amber-900">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Esperando calificación ({{ pendientes.length }})
                </h2>
                <ul class="mt-3 space-y-2">
                    <li v-for="item in pendientes" :key="item.id" class="flex flex-col gap-2 rounded-lg bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="break-words text-sm font-medium text-gray-900">{{ item.titulo }}</p>
                            <p class="text-xs text-gray-500">
                                {{ item.tipo_texto }} · {{ item.contexto }}<template v-if="item.evaluador"> · Jurado: {{ item.evaluador }}</template>
                            </p>
                        </div>
                        <span class="w-fit shrink-0 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">{{ item.estado_texto }}</span>
                    </li>
                </ul>
            </section>

            <!-- Calificaciones -->
            <section class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Calificaciones recibidas</h2>
                    <div v-if="evaluadas.length" class="inline-flex w-fit rounded-lg border border-gray-200 bg-white p-1 shadow-sm" role="tablist">
                        <button
                            v-for="f in filtros"
                            :key="f.valor"
                            type="button"
                            role="tab"
                            :aria-selected="filtro === f.valor"
                            @click="filtro = f.valor"
                            :class="[
                                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                filtro === f.valor ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100',
                            ]"
                        >
                            {{ f.texto }} <span :class="filtro === f.valor ? 'text-indigo-200' : 'text-gray-400'">{{ f.cantidad }}</span>
                        </button>
                    </div>
                </div>

                <!-- Sin notas -->
                <div v-if="!evaluadas.length" class="animar-entrada rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50">
                        <svg class="h-7 w-7 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-gray-900">Aún no tienes notas</h3>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">Cuando un jurado califique tus proyectos o tus propuestas de evento, las verás aquí con su retroalimentación.</p>
                </div>

                <div v-else-if="!evaluadasFiltradas.length" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                    No tienes notas en esta categoría.
                </div>

                <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article
                        v-for="(nota, i) in evaluadasFiltradas"
                        :key="nota.id"
                        :class="['animar-entrada flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md', `retraso-${(i % 6) + 1}`]"
                    >
                        <div class="flex items-start gap-4">
                            <NotaCircular :nota="nota.nota" tamano="md" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span :class="['rounded-full px-2.5 py-0.5 text-[11px] font-semibold', nota.tipo === 'evento' ? 'bg-violet-100 text-violet-700' : 'bg-teal-100 text-teal-700']">
                                        {{ nota.tipo_texto }}
                                    </span>
                                    <span :class="['rounded-full px-2.5 py-0.5 text-[11px] font-semibold', nivelNota(nota.nota).insignia]">
                                        {{ nivelNota(nota.nota).texto }}
                                    </span>
                                </div>
                                <h3 class="mt-2 line-clamp-2 break-words text-base font-semibold leading-snug text-gray-900">{{ nota.titulo }}</h3>
                                <p class="mt-0.5 line-clamp-1 break-words text-sm text-gray-500">{{ nota.contexto }}</p>
                            </div>
                        </div>

                        <p v-if="nota.observaciones" class="mt-4 line-clamp-3 break-words rounded-lg bg-gray-50 px-3 py-2.5 text-sm italic text-gray-600">
                            «{{ nota.observaciones }}»
                        </p>

                        <div class="mt-auto flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between" :class="nota.observaciones ? 'mt-4' : 'mt-5'">
                            <p class="text-xs text-gray-500">
                                <template v-if="nota.evaluador">Jurado: <span class="font-medium text-gray-700">{{ nota.evaluador }}</span> · </template>{{ fechaLarga(nota.fecha) }}
                            </p>
                            <Link
                                v-if="nota.detalle_url"
                                :href="nota.detalle_url"
                                class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Ver detalle
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>
                    </article>
                </div>

                <!-- Leyenda de la escala -->
                <div v-if="evaluadas.length" class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs text-gray-600">
                    <span class="font-semibold text-gray-700">Escala:</span>
                    <span v-for="nivel in ESCALA" :key="nivel.texto" class="inline-flex items-center gap-1.5">
                        <span :class="['rounded-full px-2 py-0.5 font-semibold', nivel.insignia]">{{ nivel.texto }}</span>
                        {{ nivel.rango }}
                    </span>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
