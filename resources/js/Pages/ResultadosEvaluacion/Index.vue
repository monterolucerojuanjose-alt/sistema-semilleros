<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    resultados: Object,
    estadisticas: Object,
    eventos: Array,
    estadosEvaluacion: Object,
    filters: Object
});

// Variables reactivas
const search = ref(props.filters.search || '');
const eventoId = ref(props.filters.evento_id || '');
const estadoEvaluacion = ref(props.filters.estado_evaluacion || '');
const sortBy = ref(props.filters.sort_by || 'fecha_evaluacion');
const sortOrder = ref(props.filters.sort_order || 'desc');

// Función para aplicar filtros
const aplicarFiltros = () => {
    router.get(route('resultados-evaluacion.index'), {
        search: search.value,
        evento_id: eventoId.value,
        estado_evaluacion: estadoEvaluacion.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value
    }, {
        preserveState: true,
        replace: true
    });
};

// Función para limpiar filtros
const limpiarFiltros = () => {
    search.value = '';
    eventoId.value = '';
    estadoEvaluacion.value = '';
    sortBy.value = 'fecha_evaluacion';
    sortOrder.value = 'desc';
    aplicarFiltros();
};

// Función para cambiar ordenamiento
const cambiarOrdenamiento = (campo) => {
    if (sortBy.value === campo) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = campo;
        sortOrder.value = 'desc';
    }
    aplicarFiltros();
};

// Función para obtener el ícono de ordenamiento
const getSortIcon = (campo) => {
    if (sortBy.value !== campo) {
        return `<svg class="w-4 h-4 inline ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
        </svg>`;
    }
    return sortOrder.value === 'asc'
        ? `<svg class="w-4 h-4 inline ml-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
        </svg>`
        : `<svg class="w-4 h-4 inline ml-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>`;
};

// Función para formatear fecha
const formatearFecha = (fecha) => {
    if (!fecha) return 'N/A';
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Función para obtener el color del badge según el estado
const getEstadoBadgeClass = (estado) => {
    const classes = {
        'sin_evaluar': 'bg-yellow-100 text-yellow-800',
        'evaluado': 'bg-green-100 text-green-800',
        'rechazado': 'bg-red-100 text-red-800'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

// Función para obtener el texto del estado
const getEstadoTexto = (estado) => {
    const textos = {
        'sin_evaluar': 'Sin Evaluar',
        'evaluado': 'Evaluado',
        'rechazado': 'Rechazado'
    };
    return textos[estado] || estado;
};

// Función para obtener el color de la nota
const getNotaColor = (nota) => {
    const notaNum = nota || 0;
    if (notaNum >= 90) return 'text-green-600 font-bold';
    if (notaNum >= 70) return 'text-blue-600 font-bold';
    if (notaNum >= 60) return 'text-yellow-600 font-bold';
    return 'text-red-600 font-bold';
};

// Watch para aplicar filtros automáticamente
let searchTimeout;
watch(search, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        aplicarFiltros();
    }, 500);
});

watch([eventoId, estadoEvaluacion], () => {
    aplicarFiltros();
});
</script>

<template>
    <Head title="Resultados de Evaluación" />

    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Resultados de Evaluación</h1>
                    <p class="mt-2 text-gray-600">Visualiza y analiza los resultados de las evaluaciones de proyectos</p>
                </div>

                <!-- Estadísticas Generales -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Evaluaciones</p>
                                <p class="text-2xl font-bold text-gray-900">{{ estadisticas.total_evaluaciones }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Completadas</p>
                                <p class="text-2xl font-bold text-gray-900">{{ estadisticas.evaluaciones_completadas }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Pendientes</p>
                                <p class="text-2xl font-bold text-gray-900">{{ estadisticas.evaluaciones_pendientes }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Promedio General</p>
                                <p class="text-2xl font-bold text-gray-900">{{ Number(estadisticas.promedio_general || 0).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filtros de Búsqueda</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <TextInput
                                v-model="search"
                                type="text"
                                placeholder="Nombre o email del estudiante..."
                                class="w-full"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Evento</label>
                            <select
                                v-model="eventoId"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Todos los eventos</option>
                                <option v-for="evento in eventos" :key="evento.id" :value="evento.id">
                                    {{ evento.titulo }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select
                                v-model="estadoEvaluacion"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Todos los estados</option>
                                <option v-for="(texto, valor) in estadosEvaluacion" :key="valor" :value="valor">
                                    {{ texto }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <PrimaryButton @click="limpiarFiltros" class="w-full">
                                Limpiar Filtros
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Resultados -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Resultados de Evaluación</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estudiante
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Evento
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="cambiarOrdenamiento('nota_final')">
                                        Nota <span v-html="getSortIcon('nota_final')"></span>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="cambiarOrdenamiento('estado_evaluacion')">
                                        Estado <span v-html="getSortIcon('estado_evaluacion')"></span>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Evaluador
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="cambiarOrdenamiento('fecha_evaluacion')">
                                        Fecha Evaluación <span v-html="getSortIcon('fecha_evaluacion')"></span>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="resultado in resultados.data" :key="resultado.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                                <span class="text-sm font-bold text-white">
                                                    {{ resultado.usuario.name.charAt(0).toUpperCase() }}
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ resultado.usuario.name }}</div>
                                                <div class="text-sm text-gray-500">{{ resultado.usuario.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ resultado.evento.titulo }}</div>
                                        <div class="text-sm text-gray-500">{{ formatearFecha(resultado.evento.fecha_inicio) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="resultado.nota_final" :class="getNotaColor(resultado.nota_final)" class="text-lg">
                                            {{ Number(resultado.nota_final || 0).toFixed(2) }}
                                        </span>
                                        <span v-else class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getEstadoBadgeClass(resultado.estado_evaluacion)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ getEstadoTexto(resultado.estado_evaluacion) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="resultado.evaluador" class="text-sm text-gray-900">{{ resultado.evaluador.name }}</div>
                                        <div v-else class="text-sm text-gray-400">Sin asignar</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatearFecha(resultado.fecha_evaluacion) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link
                                            :href="route('resultados-evaluacion.show', resultado.id)"
                                            class="text-blue-600 hover:text-blue-900 mr-3"
                                        >
                                            Ver Detalles
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="resultados.links" class="px-6 py-3 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Mostrando {{ resultados.from }} a {{ resultados.to }} de {{ resultados.total }} resultados
                            </div>
                            <div class="flex space-x-2">
                                <template v-for="(link, index) in resultados.links" :key="index">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        v-html="link.label"
                                        :class="[
                                            'px-3 py-2 text-sm rounded-md',
                                            link.active
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                                        ]"
                                    />
                                    <span
                                        v-else
                                        v-html="link.label"
                                        class="px-3 py-2 text-sm rounded-md bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
