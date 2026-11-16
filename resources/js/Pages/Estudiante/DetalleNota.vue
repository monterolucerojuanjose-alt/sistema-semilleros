<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import NotaCircular from '@/Components/NotaCircular.vue';
import { aprobada, formatoNota, nivelNota } from '@/utils/notas';

/**
 * Ficha de una calificación: sirve tanto para propuestas de evento como para
 * proyectos de semillero (el servidor entrega ambas con la misma estructura).
 */
const props = defineProps({
    nota: { type: Object, required: true },
    notaAprobatoria: { type: Number, default: 60 },
});

const nivel = computed(() => nivelNota(props.nota.nota));
const criterios = computed(() => props.nota.rubrica?.criterios ?? []);

const TONOS = {
    neutro: { caja: 'border-gray-200 bg-gray-50', icono: 'text-gray-500', d: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' },
    positivo: { caja: 'border-emerald-200 bg-emerald-50', icono: 'text-emerald-600', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    mejora: { caja: 'border-amber-200 bg-amber-50', icono: 'text-amber-600', d: 'M13 10V3L4 14h7v7l9-11h-7z' },
    sugerencia: { caja: 'border-indigo-200 bg-indigo-50', icono: 'text-indigo-600', d: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z' },
};

const porcentajeCriterio = (c) => (c.maximo > 0 ? Math.round((c.puntaje / c.maximo) * 100) : 0);

const fechaLarga = (fecha) => (fecha
    ? new Date(fecha).toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' })
    : 'Sin fecha');
</script>

<template>
    <AppLayout :title="`Nota: ${nota.titulo}`">
        <div class="mx-auto max-w-5xl space-y-6">
            <Link :href="route('mis-notas')" class="animar-entrada inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                Mis notas
            </Link>

            <!-- Cabecera: proyecto y nota -->
            <section class="animar-entrada overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col-reverse gap-6 p-5 sm:p-6 md:flex-row md:items-center">
                    <div class="min-w-0 flex-1">
                        <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', nota.tipo === 'evento' ? 'bg-violet-100 text-violet-700' : 'bg-teal-100 text-teal-700']">{{ nota.tipo_texto }}</span>
                        <h1 class="mt-3 break-words text-2xl font-bold leading-tight text-gray-900">{{ nota.titulo }}</h1>
                        <p v-if="nota.descripcion" class="mt-2 line-clamp-4 break-words text-sm leading-relaxed text-gray-600">{{ nota.descripcion }}</p>

                        <dl class="mt-5 grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ nota.contexto_etiqueta }}</dt>
                                <dd class="mt-0.5 break-words font-medium text-gray-900">{{ nota.contexto }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Jurado</dt>
                                <dd class="mt-0.5 font-medium text-gray-900">{{ nota.evaluador || 'Sin registro' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Fecha de evaluación</dt>
                                <dd class="mt-0.5 font-medium text-gray-900">{{ fechaLarga(nota.fecha) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="flex shrink-0 flex-col items-center rounded-xl bg-gray-50 px-8 py-5">
                        <NotaCircular :nota="nota.nota" tamano="lg" mostrar-nivel />
                        <p :class="['mt-3 text-xs font-medium', aprobada(nota.nota) ? 'text-emerald-700' : 'text-red-700']">
                            {{ aprobada(nota.nota) ? 'Aprobada' : `Por debajo del mínimo (${formatoNota(notaAprobatoria)})` }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <!-- Puntaje por criterio -->
                <section class="animar-entrada retraso-1 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 lg:col-span-3">
                    <h2 class="text-base font-semibold text-gray-900">Puntaje por criterio</h2>

                    <template v-if="nota.rubrica">
                        <p class="mt-1 text-sm text-gray-500">
                            Rúbrica: <span class="font-medium text-gray-700">{{ nota.rubrica.nombre }}</span>
                        </p>
                        <p v-if="nota.rubrica.descripcion" class="mt-2 break-words text-xs leading-relaxed text-gray-500">{{ nota.rubrica.descripcion }}</p>

                        <ul class="mt-5 space-y-5">
                            <li v-for="c in criterios" :key="c.nombre">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="min-w-0 break-words text-sm font-medium text-gray-900">{{ c.nombre }}</p>
                                    <p class="shrink-0 text-sm tabular-nums text-gray-700">
                                        <span class="font-semibold">{{ c.puntaje }}</span><span class="text-gray-400"> / {{ c.maximo }}</span>
                                    </p>
                                </div>
                                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div
                                        :class="['h-full rounded-full transition-all duration-700', nivelNota(porcentajeCriterio(c)).barra]"
                                        :style="{ width: porcentajeCriterio(c) + '%' }"
                                    ></div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    Peso {{ formatoNota(c.peso) }} % · aporta {{ formatoNota(c.aporte, 2) }} puntos a la nota
                                </p>
                            </li>
                        </ul>

                        <div class="mt-6 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                            <span class="text-sm font-medium text-gray-700">Nota final</span>
                            <span :class="['text-lg font-bold tabular-nums', nivel.texto_color]">{{ formatoNota(nota.nota, 2) }} <span class="text-sm font-normal text-gray-400">/ 100</span></span>
                        </div>
                    </template>

                    <div v-else class="mt-4 rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">
                        Esta calificación se registró antes de que el sistema guardara el puntaje por criterio, así que sólo está disponible la nota final.
                    </div>
                </section>

                <!-- Retroalimentación y datos -->
                <div class="space-y-6 lg:col-span-2">
                    <section class="animar-entrada retraso-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-base font-semibold text-gray-900">Retroalimentación del jurado</h2>

                        <div v-if="nota.retroalimentacion.length" class="mt-4 space-y-3">
                            <div v-for="bloque in nota.retroalimentacion" :key="bloque.titulo" :class="['rounded-lg border p-4', TONOS[bloque.tono]?.caja]">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <svg :class="['h-4 w-4 shrink-0', TONOS[bloque.tono]?.icono]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="TONOS[bloque.tono]?.d" /></svg>
                                    {{ bloque.titulo }}
                                </h3>
                                <p class="mt-1.5 whitespace-pre-line break-words text-sm leading-relaxed text-gray-700">{{ bloque.texto }}</p>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-gray-500">El jurado no dejó comentarios escritos en esta evaluación.</p>
                    </section>

                    <section v-if="nota.datos.length || nota.archivo_url" class="animar-entrada retraso-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-base font-semibold text-gray-900">Datos de la presentación</h2>
                        <dl v-if="nota.datos.length" class="mt-4 space-y-3 text-sm">
                            <div v-for="dato in nota.datos" :key="dato.etiqueta" class="flex flex-col gap-0.5 sm:flex-row sm:justify-between sm:gap-3">
                                <dt class="text-gray-500">{{ dato.etiqueta }}</dt>
                                <dd class="break-words font-medium text-gray-900 sm:text-right">{{ dato.valor }}</dd>
                            </div>
                        </dl>
                        <a
                            v-if="nota.archivo_url"
                            :href="nota.archivo_url"
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
