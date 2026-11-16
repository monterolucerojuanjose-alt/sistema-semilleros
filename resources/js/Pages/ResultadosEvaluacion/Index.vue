<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import NotaCircular from '@/Components/NotaCircular.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatoNota, nivelNota } from '@/utils/notas';

const props = defineProps({
    resultados: Object,
    estadisticas: Object,
    eventos: Array,
    estadosEvaluacion: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const eventoId = ref(props.filters.evento_id || '');
const estadoEvaluacion = ref(props.filters.estado_evaluacion || '');
const sortBy = ref(props.filters.sort_by || 'fecha_evaluacion');
const sortOrder = ref(props.filters.sort_order || 'desc');

const hayFiltros = computed(() => Boolean(search.value || eventoId.value || estadoEvaluacion.value));

const aplicarFiltros = () => {
    router.get(route('resultados-evaluacion.index'), {
        search: search.value || undefined,
        evento_id: eventoId.value || undefined,
        estado_evaluacion: estadoEvaluacion.value || undefined,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const limpiarFiltros = () => {
    search.value = '';
    eventoId.value = '';
    estadoEvaluacion.value = '';
};

const ordenarPor = (campo) => {
    if (sortBy.value === campo) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = campo;
        sortOrder.value = 'desc';
    }
    aplicarFiltros();
};

let espera;
watch(search, () => { clearTimeout(espera); espera = setTimeout(aplicarFiltros, 400); });
watch([eventoId, estadoEvaluacion], aplicarFiltros);

const ESTADOS = {
    sin_evaluar: 'bg-gray-100 text-gray-700',
    en_revision: 'bg-amber-100 text-amber-800',
    evaluado: 'bg-emerald-100 text-emerald-800',
};

const fechaCorta = (fecha) => (fecha
    ? new Date(fecha).toLocaleDateString('es-CO', { day: 'numeric', month: 'short', year: 'numeric' })
    : '—');

const tarjetas = computed(() => [
    { etiqueta: 'Asignadas a jurado', valor: props.estadisticas.total_evaluaciones, color: 'text-gray-900' },
    { etiqueta: 'Evaluadas', valor: props.estadisticas.evaluaciones_completadas, color: 'text-emerald-700' },
    { etiqueta: 'Pendientes', valor: props.estadisticas.evaluaciones_pendientes, color: props.estadisticas.evaluaciones_pendientes ? 'text-amber-700' : 'text-gray-900' },
    {
        etiqueta: 'Promedio general',
        valor: formatoNota(props.estadisticas.promedio_general),
        color: nivelNota(props.estadisticas.promedio_general).texto_color,
        pie: props.estadisticas.promedio_general === null ? 'sin notas aún' : nivelNota(props.estadisticas.promedio_general).texto,
    },
]);

const COLUMNAS_ORDENABLES = { nota: 'nota_final', estado: 'estado_evaluacion', fecha: 'fecha_evaluacion' };
const flecha = (campo) => (sortBy.value !== campo ? '↕' : sortOrder.value === 'asc' ? '↑' : '↓');
</script>

<template>
    <AppLayout title="Resultados de evaluación">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="animar-entrada">
                <h1 class="text-2xl font-bold text-gray-900">Resultados de evaluación</h1>
                <p class="mt-1 text-sm text-gray-500">Calificaciones que los jurados asignaron a las propuestas presentadas en los eventos.</p>
            </div>

            <!-- Indicadores -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div v-for="(t, i) in tarjetas" :key="t.etiqueta" :class="['animar-entrada rounded-xl border border-gray-200 bg-white p-4 shadow-sm', `retraso-${i + 1}`]">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ t.etiqueta }}</p>
                    <p :class="['mt-1 text-2xl font-bold tabular-nums', t.color]">{{ t.valor }}</p>
                    <p v-if="t.pie" class="text-xs text-gray-500">{{ t.pie }}</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="animar-entrada rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                    <div class="relative md:col-span-5">
                        <label for="buscar" class="sr-only">Buscar estudiante</label>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input id="buscar" v-model="search" type="search" placeholder="Buscar por nombre o correo del estudiante" class="campo pl-9" />
                    </div>
                    <div class="md:col-span-4">
                        <label for="evento" class="sr-only">Evento</label>
                        <select id="evento" v-model="eventoId" class="campo">
                            <option value="">Todos los eventos</option>
                            <option v-for="evento in eventos" :key="evento.id" :value="evento.id">{{ evento.titulo }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label for="estado" class="sr-only">Estado</label>
                        <select id="estado" v-model="estadoEvaluacion" class="campo">
                            <option value="">Todos los estados</option>
                            <option v-for="(texto, valor) in estadosEvaluacion" :key="valor" :value="valor">{{ texto }}</option>
                        </select>
                    </div>
                </div>
                <div v-if="hayFiltros" class="mt-3 flex items-center justify-between text-sm">
                    <span class="text-gray-500">{{ resultados.total }} {{ resultados.total === 1 ? 'resultado' : 'resultados' }}</span>
                    <button type="button" @click="limpiarFiltros" class="font-medium text-indigo-600 hover:text-indigo-700">Limpiar filtros</button>
                </div>
            </div>

            <!-- Listado -->
            <div v-if="resultados.data.length" class="animar-entrada overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Estudiante</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Propuesta y evento</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <button type="button" @click="ordenarPor(COLUMNAS_ORDENABLES.nota)" class="inline-flex items-center gap-1 uppercase hover:text-gray-900">Nota <span class="text-gray-400">{{ flecha(COLUMNAS_ORDENABLES.nota) }}</span></button>
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <button type="button" @click="ordenarPor(COLUMNAS_ORDENABLES.estado)" class="inline-flex items-center gap-1 uppercase hover:text-gray-900">Estado <span class="text-gray-400">{{ flecha(COLUMNAS_ORDENABLES.estado) }}</span></button>
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Jurado</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <button type="button" @click="ordenarPor(COLUMNAS_ORDENABLES.fecha)" class="inline-flex items-center gap-1 uppercase hover:text-gray-900">Fecha <span class="text-gray-400">{{ flecha(COLUMNAS_ORDENABLES.fecha) }}</span></button>
                                </th>
                                <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="r in resultados.data" :key="r.id" class="transition-colors hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <Avatar :usuario="r.usuario" tamano="h-9 w-9" />
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-gray-900">{{ r.usuario?.name }}</p>
                                            <p class="truncate text-xs text-gray-500">{{ r.usuario?.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="max-w-xs px-5 py-4">
                                    <p class="line-clamp-2 text-sm text-gray-900">{{ r.titulo_proyecto || 'Propuesta sin título' }}</p>
                                    <p class="mt-0.5 truncate text-xs text-gray-500">{{ r.evento?.titulo }}</p>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <NotaCircular v-if="r.estado_evaluacion === 'evaluado'" :nota="r.nota_final" tamano="sm" />
                                    <span v-else class="text-sm text-gray-400">—</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', ESTADOS[r.estado_evaluacion] ?? 'bg-gray-100 text-gray-700']">
                                        {{ estadosEvaluacion[r.estado_evaluacion] ?? r.estado_evaluacion }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ r.evaluador?.name ?? 'Sin asignar' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">{{ fechaCorta(r.fecha_evaluacion) }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <Link :href="route('resultados-evaluacion.show', r.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Ver detalle</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Celular -->
                <ul class="divide-y divide-gray-100 md:hidden">
                    <li v-for="r in resultados.data" :key="r.id">
                        <Link :href="route('resultados-evaluacion.show', r.id)" class="flex items-center gap-3 p-4">
                            <NotaCircular v-if="r.estado_evaluacion === 'evaluado'" :nota="r.nota_final" tamano="sm" />
                            <span v-else class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs text-gray-500">—</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">{{ r.usuario?.name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ r.titulo_proyecto || 'Propuesta sin título' }}</p>
                                <span :class="['mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold', ESTADOS[r.estado_evaluacion] ?? 'bg-gray-100 text-gray-700']">
                                    {{ estadosEvaluacion[r.estado_evaluacion] ?? r.estado_evaluacion }}
                                </span>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </li>
                </ul>
            </div>

            <div v-else class="animar-entrada rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                <h3 class="mt-3 text-sm font-semibold text-gray-900">{{ hayFiltros ? 'Ningún resultado coincide' : 'Todavía no hay resultados' }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ hayFiltros ? 'Prueba con otra búsqueda o quita los filtros.' : 'Aparecerán cuando se asignen jurados a las propuestas de los eventos.' }}</p>
                <button v-if="hayFiltros" type="button" @click="limpiarFiltros" class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-700">Limpiar filtros</button>
            </div>

            <Pagination
                v-if="resultados.last_page > 1"
                :links="resultados.links"
                :from="resultados.from"
                :to="resultados.to"
                :total="resultados.total"
            />
        </div>
    </AppLayout>
</template>

<style scoped>
.campo {
    @apply block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
}
</style>
