<template>
    <Head title="Certificados" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Gestión de Certificados</h1>
                            <p class="text-gray-600 mt-2">Administra y emite certificados para estudiantes</p>
                        </div>
                        <div class="flex space-x-3">
                            <Link
                                :href="route('certificados.create')"
                                class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-6 py-3 rounded-xl text-sm font-bold transition-all duration-200 shadow-lg hover:shadow-xl flex items-center"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Emitir Certificado
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Mensajes de éxito/error -->
                <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por título, código o estudiante..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                            <select
                                v-model="selectedTipo"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Todos los tipos</option>
                                <option v-for="tipo in tipos" :key="tipo" :value="tipo">
                                    {{ getTipoFormateado(tipo) }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select
                                v-model="selectedEstado"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Todos los estados</option>
                                <option v-for="estado in estados" :key="estado" :value="estado">
                                    {{ getEstadoFormateado(estado) }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="w-full bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200"
                            >
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de certificados -->
                <div v-if="filteredCertificados.length > 0" class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Certificado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estudiante
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Evento
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha Emisión
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="certificado in filteredCertificados" :key="certificado.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ certificado.titulo }}</div>
                                            <div class="text-sm text-gray-500">Código: {{ certificado.codigo }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ certificado.usuario.name }}</div>
                                        <div class="text-sm text-gray-500">{{ certificado.usuario.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="certificado.evento" class="text-sm text-gray-900">
                                            {{ certificado.evento.titulo }}
                                        </div>
                                        <div v-else class="text-sm text-gray-500 italic">
                                            Sin evento asociado
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getTipoBadgeClass(certificado.tipo)" class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ getTipoFormateado(certificado.tipo) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(certificado.fecha_emision) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getEstadoBadgeClass(certificado.estado)" class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ getEstadoFormateado(certificado.estado) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <Link
                                                :href="route('certificados.show', certificado.id)"
                                                class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg text-xs font-medium transition-colors duration-200"
                                            >
                                                Ver
                                            </Link>
                                            <Link
                                                :href="route('certificados.edit', certificado.id)"
                                                class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-medium transition-colors duration-200"
                                            >
                                                Editar
                                            </Link>
                                            <a
                                                v-if="certificado.archivo_certificado"
                                                :href="route('certificados.descargar', certificado.id)"
                                                class="inline-flex items-center px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-xs font-medium transition-colors duration-200"
                                            >
                                                PDF
                                            </a>
                                            <button
                                                @click="confirmDelete(certificado)"
                                                class="inline-flex items-center px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-xs font-medium transition-colors duration-200"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mensaje cuando no hay certificados -->
                <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay certificados</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ search || selectedTipo || selectedEstado ? 'No se encontraron certificados con los filtros aplicados.' : 'Comienza emitiendo un certificado.' }}
                    </p>
                    <div v-if="!search && !selectedTipo && !selectedEstado" class="mt-6">
                        <Link
                            :href="route('certificados.create')"
                            class="inline-flex items-center px-6 py-3 border border-transparent shadow-lg text-sm font-bold rounded-xl text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 hover:shadow-xl"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Emitir Certificado
                        </Link>
                    </div>
                </div>

                <!-- Paginación -->
                <div v-if="certificados.links && certificados.links.length > 3" class="flex justify-center mt-6">
                    <div class="flex space-x-2">
                        <Link
                            v-for="(link, index) in certificados.links"
                            :key="index"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 text-sm font-medium rounded-md',
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : link.url
                                        ? 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    certificados: Object,
    tipos: Array,
    estados: Array,
    filtros: Object,
    auth: Object,
});

const search = ref(props.filtros?.buscar || '');
const selectedTipo = ref(props.filtros?.tipo || '');
const selectedEstado = ref(props.filtros?.estado || '');

const filteredCertificados = computed(() => {
    return props.certificados?.data || [];
});

const clearFilters = () => {
    search.value = '';
    selectedTipo.value = '';
    selectedEstado.value = '';
    applyFilters();
};

const applyFilters = () => {
    router.get(route('certificados.index'), {
        buscar: search.value,
        tipo: selectedTipo.value,
        estado: selectedEstado.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Aplicar filtros con debounce
let searchTimeout;
watch([search, selectedTipo, selectedEstado], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getTipoFormateado = (tipo) => {
    const tipos = {
        'participacion': 'Participación',
        'ponencia': 'Ponencia',
        'poster': 'Póster',
        'organizacion': 'Organización',
        'voluntariado': 'Voluntariado',
    };
    return tipos[tipo] || tipo;
};

const getEstadoFormateado = (estado) => {
    const estados = {
        'activo': 'Activo',
        'revocado': 'Revocado',
    };
    return estados[estado] || estado;
};

const getTipoBadgeClass = (tipo) => {
    const classes = {
        'participacion': 'bg-blue-100 text-blue-800',
        'ponencia': 'bg-green-100 text-green-800',
        'poster': 'bg-purple-100 text-purple-800',
        'organizacion': 'bg-yellow-100 text-yellow-800',
        'voluntariado': 'bg-pink-100 text-pink-800',
    };
    return classes[tipo] || 'bg-gray-100 text-gray-800';
};

const getEstadoBadgeClass = (estado) => {
    const classes = {
        'activo': 'bg-green-100 text-green-800',
        'revocado': 'bg-red-100 text-red-800',
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

const confirmDelete = (certificado) => {
    if (confirm(`¿Estás seguro de eliminar el certificado "${certificado.titulo}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('certificados.destroy', certificado.id));
    }
};
</script>
