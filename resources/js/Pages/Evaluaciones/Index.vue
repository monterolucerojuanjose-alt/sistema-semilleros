<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    evaluaciones: { type: Object, default: () => ({ data: [] }) },
    // Calificaciones que el jurado puso a proyectos de eventos: viven en otra
    // tabla (evento_participantes), pero para él son igual de "evaluaciones".
    evaluacionesEventos: { type: Array, default: null },
    resumen: { type: Object, default: () => ({}) },
    rubricas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();

const isJurado = computed(() => page.props.auth?.user?.roles?.some((r) => r.name === 'Jurado'));
const esGestor = computed(() => page.props.auth?.user?.roles?.some((r) => ['Administrador', 'Coordinador'].includes(r.name)));

const search = ref(props.filters?.search ?? '');
const rubricaId = ref(props.filters?.rubrica_id ?? '');

let temporizador;
const aplicarFiltros = () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(() => {
        router.get(route('evaluaciones.index'), {
            search: search.value || undefined,
            rubrica_id: rubricaId.value || undefined,
        }, { preserveState: true, preserveScroll: true });
    }, 400);
};

const limpiarFiltros = () => {
    search.value = '';
    rubricaId.value = '';
    router.get(route('evaluaciones.index'), {}, { preserveState: true, preserveScroll: true });
};

const hayFiltros = computed(() => !!(search.value || rubricaId.value));

/**
 * La columna nota_final es decimal(5,2) y Laravel la entrega como texto
 * ("4.85"), de modo que llamar a .toFixed() sobre ella lanzaba una excepción
 * y dejaba la pantalla en blanco. Se convierte a número antes de formatear.
 */
const formatNota = (nota) => {
    const valor = Number(nota);
    return nota === null || nota === undefined || Number.isNaN(valor) ? 'N/A' : valor.toFixed(1);
};

const colorNota = (nota) => {
    const valor = Number(nota);
    if (nota === null || nota === undefined || Number.isNaN(valor)) return 'text-gray-400';
    if (valor >= 90) return 'text-emerald-600';
    if (valor >= 60) return 'text-blue-600';
    return 'text-red-600';
};

const fondoNota = (nota) => {
    const valor = Number(nota);
    if (nota === null || nota === undefined || Number.isNaN(valor)) return 'bg-gray-50';
    if (valor >= 90) return 'bg-emerald-50';
    if (valor >= 60) return 'bg-blue-50';
    return 'bg-red-50';
};

const formatDate = (date) => {
    if (!date) return 'Sin fecha';
    return new Date(date).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Evaluaciones" />

    <AppLayout title="Evaluaciones">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ isJurado ? 'Mis evaluaciones' : 'Evaluaciones' }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ isJurado
                            ? 'Las notas que has puesto, en proyectos de semillero y en propuestas de evento.'
                            : 'Evaluaciones registradas por los jurados sobre los proyectos.' }}
                    </p>
                </div>
                <Link
                    v-if="isJurado"
                    :href="route('evaluaciones.proyectos-asignados')"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Evaluar proyectos
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Resumen -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="animar-entrada rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Evaluados</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ resumen.total ?? 0 }}</p>
                </div>
                <div class="animar-entrada retraso-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Promedio</p>
                    <p :class="['mt-1 text-2xl font-bold', colorNota(resumen.promedio)]">{{ formatNota(resumen.promedio) }}</p>
                </div>
                <div class="animar-entrada retraso-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Mejor nota</p>
                    <p :class="['mt-1 text-2xl font-bold', colorNota(resumen.mejor_nota)]">{{ formatNota(resumen.mejor_nota) }}</p>
                </div>
                <div v-if="resumen.jurados_distintos !== null" class="animar-entrada retraso-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Jurados</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ resumen.jurados_distintos ?? 0 }}</p>
                </div>
                <div v-else class="animar-entrada retraso-3 flex items-center rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs text-gray-400">Escala institucional: 0.0 a 100.0</p>
                </div>
            </div>

            <!-- Proyectos de eventos que el jurado ya calificó -->
            <section v-if="evaluacionesEventos" class="animar-entrada overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Propuestas de evento calificadas</h3>
                        <p class="text-sm text-gray-500">
                            {{ evaluacionesEventos.length > 0
                                ? 'Notas que ya registraste, en escala de 0.0 a 100.0'
                                : 'Todavía no has calificado ninguna propuesta de evento' }}
                        </p>
                    </div>
                    <Link
                        href="/mis-proyectos-evaluar"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Ir a evaluar
                    </Link>
                </div>

                <div v-if="evaluacionesEventos.length > 0" class="divide-y divide-gray-100">
                    <article v-for="ev in evaluacionesEventos" :key="ev.id" class="px-6 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="break-words text-sm font-semibold text-gray-900">{{ ev.titulo }}</p>
                                <p class="mt-1 flex flex-wrap gap-x-3 text-xs text-gray-500">
                                    <span v-if="ev.estudiante">{{ ev.estudiante }}</span>
                                    <span v-if="ev.evento">· {{ ev.evento }}</span>
                                    <span v-if="ev.fecha_evaluacion">· Evaluado el {{ formatDate(ev.fecha_evaluacion) }}</span>
                                </p>
                            </div>
                            <span :class="['shrink-0 rounded-lg px-3 py-1.5 text-base font-bold', fondoNota(ev.nota_final), colorNota(ev.nota_final)]">
                                {{ formatNota(ev.nota_final) }}
                            </span>
                        </div>

                        <dl v-if="ev.fortalezas || ev.debilidades || ev.recomendaciones" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div v-if="ev.fortalezas" class="rounded-lg bg-emerald-50 p-3">
                                <dt class="text-xs font-semibold text-emerald-800">Fortalezas</dt>
                                <dd class="mt-1 break-words text-xs text-emerald-700">{{ ev.fortalezas }}</dd>
                            </div>
                            <div v-if="ev.debilidades" class="rounded-lg bg-amber-50 p-3">
                                <dt class="text-xs font-semibold text-amber-800">Debilidades</dt>
                                <dd class="mt-1 break-words text-xs text-amber-700">{{ ev.debilidades }}</dd>
                            </div>
                            <div v-if="ev.recomendaciones" class="rounded-lg bg-blue-50 p-3">
                                <dt class="text-xs font-semibold text-blue-800">Recomendaciones</dt>
                                <dd class="mt-1 break-words text-xs text-blue-700">{{ ev.recomendaciones }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <div v-else class="px-6 py-10 text-center text-sm text-gray-500">
                    Las notas que registres aparecerán aquí.
                </div>
            </section>

            <!-- Proyectos de semillero calificados con rúbrica -->
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                    Proyectos de semillero calificados con rúbrica
                </h3>

                <!-- Filtros -->
                <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="relative min-w-0 flex-1 sm:max-w-xs">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 110-16 8 8 0 010 16z" />
                        </svg>
                        <input
                            v-model="search"
                            @input="aplicarFiltros"
                            type="search"
                            placeholder="Buscar por proyecto o jurado…"
                            class="w-full rounded-lg border-gray-300 py-1.5 pl-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <select
                        v-model="rubricaId"
                        @change="aplicarFiltros"
                        class="rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Todas las rúbricas</option>
                        <option v-for="r in rubricas" :key="r.id" :value="r.id">{{ r.nombre }}</option>
                    </select>
                    <button v-if="hayFiltros" @click="limpiarFiltros" class="text-xs font-medium text-indigo-600 hover:underline">
                        Quitar filtros
                    </button>
                    <span class="ml-auto text-xs text-gray-500">
                        {{ evaluaciones.total ?? evaluaciones.data.length }}
                        {{ (evaluaciones.total ?? evaluaciones.data.length) === 1 ? 'resultado' : 'resultados' }}
                    </span>
                </div>

                <!-- Listado -->
                <div v-if="evaluaciones.data.length" class="space-y-3">
                    <Link
                        v-for="(evaluacion, i) in evaluaciones.data" :key="evaluacion.id"
                        :href="route('evaluaciones.show', evaluacion.id)"
                        :class="['tarjeta-interactiva animar-entrada block rounded-xl border border-gray-200 bg-white p-4 shadow-sm', `retraso-${Math.min(i + 1, 6)}`]"
                    >
                        <div class="flex flex-wrap items-center gap-4">
                            <div v-if="esGestor" class="shrink-0">
                                <Avatar :usuario="evaluacion.jurado" tamano="h-10 w-10" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ evaluacion.proyecto?.titulo ?? 'Proyecto eliminado' }}</p>
                                <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-500">
                                    <span class="font-mono">{{ evaluacion.proyecto?.codigo }}</span>
                                    <span v-if="esGestor">· {{ evaluacion.jurado?.name }}</span>
                                    <span>· {{ evaluacion.rubrica?.nombre }}</span>
                                    <span>· {{ formatDate(evaluacion.created_at) }}</span>
                                </p>
                            </div>

                            <span :class="['shrink-0 rounded-lg px-3 py-1.5 text-base font-bold', fondoNota(evaluacion.nota_final), colorNota(evaluacion.nota_final)]">
                                {{ formatNota(evaluacion.nota_final) }}
                            </span>

                            <svg class="h-4 w-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </Link>

                    <Pagination
                        :links="evaluaciones.links ?? []"
                        :from="evaluaciones.from"
                        :to="evaluaciones.to"
                        :total="evaluaciones.total"
                    />
                </div>

                <!-- Sin resultados -->
                <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                    <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        {{ hayFiltros ? 'Ninguna evaluación coincide con el filtro' : 'Todavía no hay evaluaciones' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ hayFiltros ? 'Prueba con otra búsqueda o rúbrica.' : 'Las evaluaciones que se registren aparecerán aquí.' }}
                    </p>
                    <button v-if="hayFiltros" @click="limpiarFiltros" class="mt-4 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Quitar filtros
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
