<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    evaluadas: { type: Array, default: () => [] },
    pendientes: { type: Array, default: () => [] },
    resumen: { type: Object, default: () => ({}) },
    notaAprobatoria: { type: Number, default: 60.0 },
    auth: Object,
});

// Filtro por origen de la nota: todas, proyectos de semillero o eventos.
const filtro = ref('todas');
const filtros = [
    { valor: 'todas', texto: 'Todas' },
    { valor: 'proyecto', texto: 'Proyectos' },
    { valor: 'evento', texto: 'Eventos' },
];

const evaluadasFiltradas = computed(() =>
    filtro.value === 'todas'
        ? props.evaluadas
        : props.evaluadas.filter((n) => n.tipo === filtro.value)
);

// La nota llega como decimal; se formatea siempre con un decimal (0.0 – 5.0).
const formatNota = (nota) => (nota === null || nota === undefined ? '—' : Number(nota).toFixed(1));

const aprobada = (nota) => nota !== null && Number(nota) >= props.notaAprobatoria;

// Color según el desempeño, para que la nota se lea de un vistazo.
const colorNota = (nota) => {
    if (nota === null) return 'text-gray-400';
    const n = Number(nota);
    if (n >= 90) return 'text-emerald-600';
    if (n >= props.notaAprobatoria) return 'text-blue-600';
    return 'text-red-600';
};

const barraNota = (nota) => {
    if (nota === null) return 'bg-gray-300';
    const n = Number(nota);
    if (n >= 90) return 'bg-emerald-500';
    if (n >= props.notaAprobatoria) return 'bg-blue-500';
    return 'bg-red-500';
};

// Ancho de la barra de progreso: la nota ya viene en escala de 0 a 100.
const porcentaje = (nota) => (nota === null ? 0 : Math.min(100, Number(nota)));

const escalaTexto = (nota) => {
    if (nota === null) return '';
    const n = Number(nota);
    if (n >= 90) return 'Excelente';
    if (n >= 80) return 'Sobresaliente';
    if (n >= props.notaAprobatoria) return 'Aprobado';
    return 'No aprobado';
};

const formatDate = (date) => {
    if (!date) return 'Sin fecha';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Mis Notas" />

    <AppLayout :auth="auth">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Mis Notas</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div v-if="$page.props.flash?.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Resumen académico -->
                <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-blue-600 shadow-lg">
                    <div class="grid grid-cols-2 divide-y divide-white/15 sm:divide-y-0 md:grid-cols-4 md:divide-x">
                        <div class="p-6">
                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-100">Promedio general</p>
                            <p class="mt-1 text-4xl font-bold text-white">{{ formatNota(resumen.promedio) }}</p>
                            <p class="text-xs text-indigo-100">sobre 100</p>
                        </div>
                        <div class="p-6">
                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-100">Mejor nota</p>
                            <p class="mt-1 text-4xl font-bold text-white">{{ formatNota(resumen.mejor_nota) }}</p>
                            <p class="text-xs text-indigo-100">tu puntaje más alto</p>
                        </div>
                        <div class="p-6">
                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-100">Aprobadas</p>
                            <p class="mt-1 text-4xl font-bold text-white">
                                {{ resumen.aprobadas ?? 0 }}<span class="text-xl font-normal text-indigo-200">/{{ resumen.total ?? 0 }}</span>
                            </p>
                            <p class="text-xs text-indigo-100">desde {{ formatNota(notaAprobatoria) }}</p>
                        </div>
                        <div class="p-6">
                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-100">En espera</p>
                            <p class="mt-1 text-4xl font-bold text-white">{{ resumen.pendientes ?? 0 }}</p>
                            <p class="text-xs text-indigo-100">por calificar</p>
                        </div>
                    </div>
                </div>

                <!-- Trabajos aún sin calificar -->
                <section v-if="pendientes.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-amber-900">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Esperando calificación ({{ pendientes.length }})
                    </h3>
                    <ul class="mt-4 space-y-2">
                        <li
                            v-for="item in pendientes"
                            :key="item.id"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-4 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">{{ item.titulo }}</p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ item.tipo_texto }} · {{ item.contexto }}
                                    <template v-if="item.evaluador"> · Evaluador: {{ item.evaluador }}</template>
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                                {{ item.estado_texto }}
                            </span>
                        </li>
                    </ul>
                </section>

                <!-- Notas obtenidas -->
                <section>
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">Calificaciones recibidas</h3>
                        <div v-if="evaluadas.length" class="inline-flex rounded-lg border border-gray-200 bg-white p-1">
                            <button
                                v-for="f in filtros"
                                :key="f.valor"
                                @click="filtro = f.valor"
                                :class="[
                                    'rounded-md px-3 py-1.5 text-xs font-medium transition',
                                    filtro === f.valor ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                ]"
                            >
                                {{ f.texto }}
                            </button>
                        </div>
                    </div>

                    <!-- Sin notas todavía -->
                    <div v-if="!evaluadas.length" class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h4 class="mt-3 text-sm font-semibold text-gray-900">Aún no tienes notas</h4>
                        <p class="mt-1 text-sm text-gray-500">
                            Cuando un jurado califique tus proyectos o tus propuestas de evento, las verás aquí.
                        </p>
                    </div>

                    <!-- Filtro sin coincidencias -->
                    <div v-else-if="!evaluadasFiltradas.length" class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                        No tienes notas en esta categoría.
                    </div>

                    <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <article
                            v-for="nota in evaluadasFiltradas"
                            :key="nota.id"
                            class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <span
                                        :class="[
                                            'inline-block rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                                            nota.tipo === 'evento'
                                                ? 'bg-purple-100 text-purple-700'
                                                : 'bg-teal-100 text-teal-700',
                                        ]"
                                    >
                                        {{ nota.tipo_texto }}
                                    </span>
                                    <h4 class="mt-2 truncate text-base font-semibold text-gray-900" :title="nota.titulo">
                                        {{ nota.titulo }}
                                    </h4>
                                    <p class="truncate text-sm text-gray-500" :title="nota.contexto">{{ nota.contexto }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p :class="['text-4xl font-bold leading-none', colorNota(nota.nota)]">
                                        {{ formatNota(nota.nota) }}
                                    </p>
                                    <p class="mt-1 text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                        {{ escalaTexto(nota.nota) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Nota como barra sobre la escala 0.0 – 5.0 -->
                            <div class="mt-4">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div :class="['h-full rounded-full transition-all', barraNota(nota.nota)]" :style="{ width: porcentaje(nota.nota) + '%' }"></div>
                                </div>
                                <div class="mt-1 flex justify-between text-[11px] text-gray-400">
                                    <span>0.0</span>
                                    <span>5.0</span>
                                </div>
                            </div>

                            <p v-if="nota.observaciones" class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-600 line-clamp-3 break-words">
                                {{ nota.observaciones }}
                            </p>

                            <div class="mt-auto flex flex-wrap items-center justify-between gap-2 pt-4 text-xs text-gray-500">
                                <span class="truncate">
                                    <template v-if="nota.evaluador">Evaluó {{ nota.evaluador }} · </template>{{ formatDate(nota.fecha) }}
                                </span>
                                <Link
                                    v-if="nota.detalle_id"
                                    :href="route('mis-notas.show', nota.detalle_id)"
                                    class="shrink-0 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                                >
                                    Ver retroalimentación
                                </Link>
                                <span
                                    v-else-if="!aprobada(nota.nota)"
                                    class="shrink-0 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-medium text-red-700"
                                >
                                    Por debajo de {{ formatNota(notaAprobatoria) }}
                                </span>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
