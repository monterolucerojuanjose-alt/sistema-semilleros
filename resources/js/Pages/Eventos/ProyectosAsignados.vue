<template>
    <Head title="Evaluar propuestas de evento" />

    <AppLayout title="Evaluar propuestas de evento" :auth="auth">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Encabezado -->
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Evaluar propuestas de evento</h1>
                <p class="mt-1 text-sm text-gray-500">Las propuestas de evento que te asignaron, pendientes de calificar.</p>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="animar-entrada rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Pendientes</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600">{{ proyectosPendientes }}</p>
                </div>
                <Link
                    href="/evaluaciones"
                    class="animar-entrada retraso-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:border-indigo-300 hover:bg-indigo-50/40"
                >
                    <p class="text-xs font-medium text-gray-500">Ya evaluadas</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">{{ totalEvaluados }}</p>
                    <p class="mt-1 text-xs font-medium text-indigo-600">Ver tus notas →</p>
                </Link>
                <div class="animar-entrada retraso-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Total asignadas</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ proyectos.length + totalEvaluados }}</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="relative min-w-0 flex-1 sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 110-16 8 8 0 010 16z" />
                    </svg>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por título, estudiante o evento…"
                        class="w-full rounded-lg border-gray-300 py-1.5 pl-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <select
                    v-model="filtroEstado"
                    class="rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Todos los estados</option>
                    <option value="sin_evaluar">Sin evaluar</option>
                    <option value="en_revision">En revisión</option>
                </select>
                <button v-if="hayFiltros" @click="limpiarFiltros" class="text-xs font-medium text-indigo-600 hover:underline">
                    Quitar filtros
                </button>
                <span class="ml-auto text-xs text-gray-500">
                    {{ proyectosFiltrados.length }} {{ proyectosFiltrados.length === 1 ? 'resultado' : 'resultados' }}
                </span>
            </div>

            <!-- Listado -->
            <div v-if="proyectosFiltrados.length > 0" class="space-y-4">
                <article
                    v-for="(proyecto, i) in proyectosFiltrados" :key="proyecto.id"
                    :class="['animar-entrada overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm', `retraso-${Math.min(i + 1, 6)}`]"
                >
                    <div class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ proyecto.evento?.titulo }}</p>
                                <h3 class="mt-0.5 break-words text-base font-semibold text-gray-900">{{ proyecto.titulo_proyecto }}</h3>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span v-if="proyecto.modalidad" class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                        {{ proyecto.modalidad }}
                                    </span>
                                    <span v-if="proyecto.area_participacion" class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                        {{ proyecto.area_participacion }}
                                    </span>
                                </div>
                            </div>
                            <span :class="['shrink-0 rounded-full px-3 py-1 text-xs font-semibold', getEstadoBadgeClass(proyecto.estado_evaluacion)]">
                                {{ getEstadoTexto(proyecto.estado_evaluacion) }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <Avatar :usuario="proyecto.usuario" tamano="h-9 w-9" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">{{ proyecto.usuario?.name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ proyecto.usuario?.email }}</p>
                            </div>
                        </div>

                        <p class="mt-3 line-clamp-2 text-sm text-gray-600">{{ proyecto.descripcion_proyecto }}</p>

                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                            <span>Inscrito: {{ formatDate(proyecto.created_at) }}</span>
                            <span v-if="proyecto.aula || proyecto.horario">
                                Programación: {{ proyecto.aula || 'Sin aula' }} · {{ proyecto.horario || 'Sin horario' }}
                            </span>
                        </div>

                        <!-- Nota, si ya fue evaluada -->
                        <div v-if="proyecto.estado_evaluacion === 'evaluado'" class="mt-4 flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-emerald-800">Evaluado el {{ formatDate(proyecto.fecha_evaluacion) }}</p>
                            </div>
                            <span class="text-lg font-bold text-emerald-700">{{ formatNota(proyecto.nota_final) }} / 100</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 border-t border-gray-100 bg-gray-50 px-5 py-3">
                        <a
                            v-if="proyecto.archivo_proyecto"
                            :href="`/storage/${proyecto.archivo_proyecto}`"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Descargar propuesta
                        </a>
                        <Link
                            :href="route('proyectos.formulario-evaluacion', proyecto.id)"
                            :class="proyecto.estado_evaluacion === 'evaluado'
                                ? 'bg-gray-600 hover:bg-gray-700'
                                : 'bg-indigo-600 hover:bg-indigo-700'"
                            class="ml-auto inline-flex items-center gap-1.5 rounded-lg px-4 py-1.5 text-sm font-medium text-white transition-colors"
                        >
                            {{ proyecto.estado_evaluacion === 'evaluado' ? 'Ver evaluación' : 'Evaluar propuesta' }}
                        </Link>
                    </div>
                </article>
            </div>

            <!-- Sin resultados -->
            <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                    {{ hayFiltros ? 'Ninguna propuesta coincide con el filtro' : 'No tienes propuestas asignadas' }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ hayFiltros ? 'Prueba con otra búsqueda o estado.' : 'Cuando te asignen una propuesta de evento para calificar, aparecerá aquí.' }}
                </p>
                <button v-if="hayFiltros" @click="limpiarFiltros" class="mt-4 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Quitar filtros
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    proyectos: Array,
    // Los ya calificados no se listan aquí; sólo se cuentan para enlazar a
    // "Mis evaluaciones", que es donde se revisan las notas.
    totalEvaluados: {
        type: Number,
        default: 0,
    },
    auth: Object,
});

const search = ref('');
const filtroEstado = ref('');

const proyectosPendientes = computed(() => props.proyectos.length);

const hayFiltros = computed(() => !!(search.value || filtroEstado.value));

const proyectosFiltrados = computed(() => {
    let filtered = props.proyectos || [];

    if (search.value) {
        const searchLower = search.value.toLowerCase();
        filtered = filtered.filter(proyecto =>
            proyecto.titulo_proyecto?.toLowerCase().includes(searchLower) ||
            proyecto.usuario?.name?.toLowerCase().includes(searchLower) ||
            proyecto.evento?.titulo?.toLowerCase().includes(searchLower)
        );
    }

    if (filtroEstado.value) {
        filtered = filtered.filter(proyecto => proyecto.estado_evaluacion === filtroEstado.value);
    }

    return filtered;
});

const limpiarFiltros = () => {
    search.value = '';
    filtroEstado.value = '';
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

/** nota_final llega como texto por ser decimal(5,2); .toFixed() sobre un texto rompería la vista. */
const formatNota = (nota) => {
    const valor = Number(nota);
    return nota === null || nota === undefined || Number.isNaN(valor) ? 'N/A' : valor.toFixed(2);
};

const getEstadoTexto = (estado) => {
    const textos = {
        'sin_evaluar': 'Sin Evaluar',
        'en_revision': 'En Revisión',
        'evaluado': 'Evaluado',
    };
    return textos[estado] || estado;
};

const getEstadoBadgeClass = (estado) => {
    const classes = {
        'sin_evaluar': 'bg-amber-100 text-amber-800',
        'en_revision': 'bg-blue-100 text-blue-800',
        'evaluado': 'bg-emerald-100 text-emerald-800',
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};
</script>
