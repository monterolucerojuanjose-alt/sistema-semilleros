<template>
    <Head title="Detalles del Certificado" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Detalles del Certificado</h1>
                            <p class="text-gray-600 mt-2">Información completa del certificado</p>
                        </div>
                        <div class="flex space-x-3">
                            <Link
                                :href="isAdmin ? route('certificados.index') : route('mis-certificados')"
                                class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Volver
                            </Link>
                            <a
                                v-if="certificado.archivo_certificado"
                                :href="route('certificados.descargar', certificado.id)"
                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-medium rounded-lg transition-all duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Descargar PDF
                            </a>
                            <div
                                v-else
                                class="inline-flex items-center px-4 py-2 bg-gray-400 text-white font-medium rounded-lg cursor-not-allowed"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Sin PDF
                            </div>
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

                <!-- Contenido del certificado -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                    <!-- Header del certificado -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-8 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-6">
                                    <h2 class="text-2xl font-bold">{{ certificado.titulo }}</h2>
                                    <p class="text-blue-100 text-lg">Código: {{ certificado.codigo }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span :class="getEstadoBadgeClass(certificado.estado)" class="px-4 py-2 text-sm font-bold rounded-full">
                                    {{ getEstadoFormateado(certificado.estado) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Información del certificado -->
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Información principal -->
                            <div class="space-y-6">
                                <h3 class="text-xl font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                    Información del Certificado
                                </h3>

                                <!-- Descripción -->
                                <div v-if="certificado.descripcion">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                                    <p class="text-gray-600 bg-gray-50 p-3 rounded-lg">{{ certificado.descripcion }}</p>
                                </div>

                                <!-- Tipo -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Certificado</label>
                                    <span :class="getTipoBadgeClass(certificado.tipo)" class="px-3 py-1 text-sm font-medium rounded-full">
                                        {{ getTipoFormateado(certificado.tipo) }}
                                    </span>
                                </div>

                                <!-- Fecha de emisión -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Emisión</label>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="font-medium">{{ formatDate(certificado.fecha_emision) }}</span>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                    <span :class="getEstadoBadgeClass(certificado.estado)" class="px-3 py-1 text-sm font-medium rounded-full">
                                        {{ getEstadoFormateado(certificado.estado) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Información de personas y eventos -->
                            <div class="space-y-6">
                                <h3 class="text-xl font-semibold text-gray-900 border-b border-gray-200 pb-2">
                                    Información Adicional
                                </h3>

                                <!-- Destinatario -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Destinatario</label>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <div>
                                            <p class="font-medium">{{ certificado.usuario.name }}</p>
                                            <p class="text-sm text-gray-500">{{ certificado.usuario.email }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Emisor -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Emitido por</label>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div>
                                            <p class="font-medium">{{ certificado.emisor.name }}</p>
                                            <p class="text-sm text-gray-500">{{ certificado.emisor.email }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Evento relacionado -->
                                <div v-if="certificado.evento">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Evento Relacionado</label>
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <div>
                                            <p class="font-medium">{{ certificado.evento.titulo }}</p>
                                            <p v-if="certificado.evento.descripcion" class="text-sm text-gray-500">
                                                {{ certificado.evento.descripcion.substring(0, 100) }}{{ certificado.evento.descripcion.length > 100 ? '...' : '' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Archivo PDF -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Archivo PDF</label>
                                    <div v-if="certificado.archivo_certificado" class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <div>
                                                <p class="font-medium text-green-800">Certificado PDF disponible</p>
                                                <p class="text-sm text-green-600">{{ getFileName(certificado.archivo_certificado) }}</p>
                                            </div>
                                        </div>
                                        <a
                                            :href="route('certificados.descargar', certificado.id)"
                                            class="inline-flex items-center px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Descargar
                                        </a>
                                    </div>
                                    <div v-else class="flex items-center p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                        <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <div>
                                            <p class="font-medium text-gray-600">No hay archivo PDF disponible</p>
                                            <p class="text-sm text-gray-500">El certificado no tiene un archivo PDF asociado</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Observaciones -->
                                <div v-if="certificado.observaciones">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                    <p class="text-gray-600 bg-gray-50 p-3 rounded-lg">{{ certificado.observaciones }}</p>
                                </div>

                                <!-- Metadata -->
                                <div v-if="certificado.metadata && Object.keys(certificado.metadata).length > 0">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Información Adicional</label>
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <dl class="space-y-2">
                                            <div v-for="(value, key) in certificado.metadata" :key="key" class="flex justify-between">
                                                <dt class="font-medium text-gray-600 capitalize">{{ key }}:</dt>
                                                <dd class="text-gray-900">{{ value }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fechas de creación y actualización -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                                <div>
                                    <span class="font-medium">Creado:</span> {{ formatDateTime(certificado.created_at) }}
                                </div>
                                <div>
                                    <span class="font-medium">Última actualización:</span> {{ formatDateTime(certificado.updated_at) }}
                                </div>
                            </div>
                        </div>

                        <!-- Acciones adicionales para administradores -->
                        <div v-if="isAdmin" class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-3">
                            <Link
                                :href="route('certificados.edit', certificado.id)"
                                class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editar
                            </Link>
                            <button
                                @click="confirmDelete"
                                class="inline-flex items-center px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    certificado: Object,
    auth: Object,
});

const isAdmin = computed(() => {
    return props.auth?.user?.roles?.some(role =>
        role.name === 'Administrador' || role.name === 'Coordinador'
    );
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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

const getFileName = (path) => {
    return path ? path.split('/').pop() : '';
};

const confirmDelete = () => {
    if (confirm(`¿Estás seguro de eliminar el certificado "${props.certificado.titulo}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('certificados.destroy', props.certificado.id));
    }
};
</script>
