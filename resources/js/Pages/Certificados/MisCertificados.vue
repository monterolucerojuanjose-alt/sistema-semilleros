<template>
    <Head title="Mis Certificados" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="text-center">
                        <h1 class="text-3xl font-bold text-gray-900">Mis Certificados</h1>
                        <p class="text-gray-600 mt-2">Certificados de participación en eventos y actividades</p>
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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por título o código..."
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
                <div v-if="filteredCertificados.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="certificado in filteredCertificados"
                        :key="certificado.id"
                        class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
                    >
                        <!-- Header del certificado -->
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-bold">{{ certificado.titulo }}</h3>
                                        <p class="text-blue-100 text-sm">{{ certificado.codigo }}</p>
                                    </div>
                                </div>
                                <span :class="getEstadoBadgeClass(certificado.estado)" class="px-2 py-1 text-xs font-medium rounded-full">
                                    {{ getEstadoFormateado(certificado.estado) }}
                                </span>
                            </div>
                        </div>

                        <!-- Contenido del certificado -->
                        <div class="p-6">
                            <div class="space-y-4">
                                <!-- Descripción -->
                                <div v-if="certificado.descripcion">
                                    <p class="text-gray-600 text-sm">{{ certificado.descripcion }}</p>
                                </div>

                                <!-- Evento -->
                                <div v-if="certificado.evento">
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-sm font-medium">{{ certificado.evento.titulo }}</span>
                                    </div>
                                </div>

                                <!-- Tipo -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        <span class="text-sm font-medium">Tipo:</span>
                                    </div>
                                    <span :class="getTipoBadgeClass(certificado.tipo)" class="px-2 py-1 text-xs font-medium rounded-full">
                                        {{ getTipoFormateado(certificado.tipo) }}
                                    </span>
                                </div>

                                <!-- Fecha de emisión -->
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm font-medium">Emitido el {{ formatDate(certificado.fecha_emision) }}</span>
                                </div>

                                <!-- Emisor -->
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="text-sm font-medium">Emitido por: {{ certificado.emisor.name }}</span>
                                </div>
                            </div>

                            <!-- Acciones -->
                            <div class="mt-6 flex space-x-3">
                                <Link
                                    :href="route('certificados.show', certificado.id)"
                                    class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 text-center"
                                >
                                    Ver Detalles
                                </Link>
                                <a
                                    v-if="certificado.archivo_certificado"
                                    :href="route('certificados.descargar', certificado.id)"
                                    class="flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 text-center flex items-center justify-center"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Descargar PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje cuando no hay certificados -->
                <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-r from-gray-100 to-gray-200 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No tienes certificados aún</h3>
                    <p class="text-gray-600 mb-6">
                        {{ search || selectedTipo ? 'No se encontraron certificados con los filtros aplicados.' : 'Los certificados aparecerán aquí cuando participes en eventos y actividades.' }}
                    </p>
                    <div v-if="!search && !selectedTipo" class="space-y-4">
                        <p class="text-sm text-gray-500">¡Participa en eventos para obtener tus certificados!</p>
                        <Link
                            :href="route('eventos.index')"
                            class="inline-flex items-center px-6 py-3 border border-transparent shadow-lg text-sm font-bold rounded-xl text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hover:shadow-xl"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Ver Eventos Disponibles
                        </Link>
                    </div>
                </div>

                <!-- Paginación -->
                <div v-if="certificados.links && certificados.links.length > 3" class="flex justify-center mt-8">
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

const filteredCertificados = computed(() => {
    return props.certificados?.data || [];
});

const clearFilters = () => {
    search.value = '';
    selectedTipo.value = '';
    applyFilters();
};

const applyFilters = () => {
    router.get(route('mis-certificados'), {
        buscar: search.value,
        tipo: selectedTipo.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Aplicar filtros con debounce
let searchTimeout;
watch([search, selectedTipo], () => {
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
</script>







