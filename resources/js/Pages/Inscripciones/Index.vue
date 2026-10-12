<template>
    <AppLayout title="Inscripciones al Evento" :auth="page.props.auth">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Inscripciones al Encuentro Institucional
                </h2>
                <Link
                    v-if="page.props.auth?.user?.roles?.some(role => role.name === 'Estudiante')"
                    :href="route('inscripciones.create')"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Nueva Inscripción
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filtros Avanzados -->
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <!-- Botón para expandir/colapsar filtros -->
                        <button
                            @click="showAdvancedFilters = !showAdvancedFilters"
                            class="flex items-center justify-between w-full text-left text-sm font-medium text-gray-700 hover:text-gray-900 mb-4"
                        >
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
                                </svg>
                                Filtros Avanzados
                            </span>
                            <svg
                                class="w-4 h-4 transition-transform duration-200"
                                :class="{ 'rotate-180': showAdvancedFilters }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Filtros básicos siempre visibles -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Buscar por código, proyecto, autor..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select
                                    v-model="selectedEstado"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="">Todos los estados</option>
                                    <option v-for="estado in estados" :key="estado" :value="estado">
                                        {{ estado }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                                <select
                                    v-model="selectedTipo"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="">Todos los tipos</option>
                                    <option v-for="tipo in tipos" :key="tipo" :value="tipo">
                                        {{ tipo }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Filtros avanzados (colapsables) -->
                        <div v-show="showAdvancedFilters" class="border-t pt-4 mt-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Filtro por rango de fechas -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Inicio</label>
                                    <input
                                        v-model="fechaInicio"
                                        type="date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Fin</label>
                                    <input
                                        v-model="fechaFin"
                                        type="date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                                <!-- Filtro por semillero (solo para admin/coordinador) -->
                                <div v-if="page.props.auth?.user?.roles?.some(role => ['Administrador', 'Coordinador'].includes(role.name))">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Semillero</label>
                                    <select
                                        v-model="selectedSemillero"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">Todos los semilleros</option>
                                        <option v-for="semillero in semilleros" :key="semillero.id" :value="semillero.id">
                                            {{ semillero.nombre }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="flex justify-end space-x-2 mt-4">
                                <button
                                    @click="limpiarFiltros"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors duration-200"
                                >
                                    Limpiar Filtros
                                </button>
                                <button
                                    @click="aplicarFiltros"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors duration-200"
                                >
                                    Aplicar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de inscripciones -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Tabla para pantallas grandes -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Proyecto
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Código
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Tipo
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Autor
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Semillero
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="inscripcion in filteredInscripciones" :key="inscripcion.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ inscripcion.proyecto?.titulo || 'N/A' }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ inscripcion.proyecto?.autor?.name || 'N/A' }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ inscripcion.codigo_inscripcion }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ inscripcion.tipo === 'ponencia' ? 'Ponencia' : 'Póster' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getEstadoBadgeClass(inscripcion.estado)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ inscripcion.estado }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ inscripcion.proyecto?.autor?.name || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ inscripcion.proyecto?.semillero?.nombre || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(inscripcion.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <Link
                                                :href="route('inscripciones.show', inscripcion.id)"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                Ver
                                            </Link>
                                            <Link
                                                :href="route('inscripciones.comprobante', inscripcion.id)"
                                                class="text-green-600 hover:text-green-900"
                                            >
                                                PDF
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Cards para pantallas pequeñas -->
                        <div class="md:hidden space-y-4">
                            <div
                                v-for="inscripcion in filteredInscripciones"
                                :key="inscripcion.id"
                                class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
                            >
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">
                                            {{ inscripcion.proyecto?.titulo || 'N/A' }}
                                        </h3>
                                        <p class="text-sm text-gray-500">{{ inscripcion.proyecto?.autor?.name || 'N/A' }}</p>
                                    </div>
                                    <span :class="getEstadoBadgeClass(inscripcion.estado)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ inscripcion.estado }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-3 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700">Código:</span>
                                        <p class="text-gray-600">{{ inscripcion.codigo_inscripcion }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Tipo:</span>
                                        <p class="text-gray-600">{{ inscripcion.tipo === 'ponencia' ? 'Ponencia' : 'Póster' }}</p>
                                    </div>
                                </div>

                                <div class="text-sm text-gray-500 mb-3">
                                    {{ formatDate(inscripcion.created_at) }}
                                </div>

                                <div class="flex space-x-2">
                                    <Link
                                        :href="route('inscripciones.show', inscripcion.id)"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-3 rounded-md text-sm font-medium transition-colors duration-200"
                                    >
                                        Ver Detalles
                                    </Link>
                                    <Link
                                        :href="route('inscripciones.comprobante', inscripcion.id)"
                                        class="bg-green-600 hover:bg-green-700 text-white py-2 px-3 rounded-md text-sm font-medium transition-colors duration-200"
                                    >
                                        PDF
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Paginación -->
                        <div v-if="inscripciones.data && inscripciones.data.length > 0" class="mt-6">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Mostrando {{ inscripciones.from }} a {{ inscripciones.to }} de {{ inscripciones.total }} resultados
                                </div>
                                <div class="flex space-x-2">
                                    <Link
                                        v-if="inscripciones.prev_page_url"
                                        :href="inscripciones.prev_page_url"
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    >
                                        Anterior
                                    </Link>
                                    <Link
                                        v-if="inscripciones.next_page_url"
                                        :href="inscripciones.next_page_url"
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    >
                                        Siguiente
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje si no hay inscripciones -->
                        <div v-else-if="inscripciones.data && inscripciones.data.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay inscripciones</h3>
                            <p class="mt-1 text-sm text-gray-500">Comienza creando una nueva inscripción al evento.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    inscripciones: Object,
    semilleros: Array,
    estados: Array,
    tipos: Array,
    filtros: Object,
});

const page = usePage();


// Variables reactivas para filtros
const search = ref(props.filtros?.buscar || '');
const selectedEstado = ref(props.filtros?.estado || '');
const selectedTipo = ref(props.filtros?.tipo || '');
const fechaInicio = ref(props.filtros?.fecha_inicio || '');
const fechaFin = ref(props.filtros?.fecha_fin || '');
const selectedSemillero = ref(props.filtros?.semillero_id || '');
const showAdvancedFilters = ref(false);

// Usar los datos del servidor directamente (ya filtrados)
const filteredInscripciones = computed(() => {
    return props.inscripciones?.data || [];
});

// Métodos para manejar filtros
const aplicarFiltros = () => {
    const filtros = {};

    if (search.value) filtros.buscar = search.value;
    if (selectedEstado.value) filtros.estado = selectedEstado.value;
    if (selectedTipo.value) filtros.tipo = selectedTipo.value;
    if (fechaInicio.value) filtros.fecha_inicio = fechaInicio.value;
    if (fechaFin.value) filtros.fecha_fin = fechaFin.value;
    if (selectedSemillero.value) filtros.semillero_id = selectedSemillero.value;

    // Usar Inertia router para navegar manteniendo el estado
    // Detectar si estamos en mis-inscripciones o inscripciones
    const currentRoute = window.location.pathname;
    const targetRoute = currentRoute.includes('/mis-inscripciones') ? 'mis-inscripciones' : 'inscripciones.index';

    router.get(route(targetRoute), filtros, {
        preserveState: true,
        preserveScroll: true
    });
};

const limpiarFiltros = () => {
    search.value = '';
    selectedEstado.value = '';
    selectedTipo.value = '';
    fechaInicio.value = '';
    fechaFin.value = '';
    selectedSemillero.value = '';

    // Navegar sin filtros
    // Detectar si estamos en mis-inscripciones o inscripciones
    const currentRoute = window.location.pathname;
    const targetRoute = currentRoute.includes('/mis-inscripciones') ? 'mis-inscripciones' : 'inscripciones.index';

    router.get(route(targetRoute), {}, {
        preserveState: false,
        preserveScroll: false
    });
};

// Auto-aplicar filtros básicos con debounce
let searchTimeout;
watch([search, selectedEstado, selectedTipo], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        aplicarFiltros();
    }, 500);
}, { deep: true });

const getEstadoBadgeClass = (estado) => {
    switch (estado) {
        case 'Inscrito':
            return 'bg-blue-100 text-blue-800';
        case 'En revisión':
            return 'bg-yellow-100 text-yellow-800';
        case 'Aceptado':
            return 'bg-green-100 text-green-800';
        case 'Rechazado':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>
