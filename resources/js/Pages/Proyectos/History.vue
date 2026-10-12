<template>
    <Head :title="`Historial - ${proyecto.titulo}`" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <Link :href="route('proyectos.show', proyecto.id)" class="mr-4 text-gray-500 hover:text-gray-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Link>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Historial de Versiones</h1>
                                <p class="mt-2 text-gray-600">{{ proyecto.titulo }} ({{ proyecto.codigo }})</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span :class="getEstadoBadgeClass(proyecto.estado)" class="rounded-full px-3 py-1 text-sm font-medium">
                                {{ proyecto.estado }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="feedbackMessage" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ feedbackMessage }}
                </div>

                <div v-if="errorMessage" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ errorMessage }}
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Versiones del Proyecto</h2>
                        <p class="mt-1 text-sm text-gray-600">Total de versiones: {{ versiones.length }}</p>
                    </div>

                    <div v-if="versiones.length > 0" class="divide-y divide-gray-200">
                        <div
                            v-for="(version, index) in versiones"
                            :key="version.id"
                            class="p-6 transition-colors duration-200 hover:bg-gray-50"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="mb-2 flex items-center">
                                        <div class="flex items-center">
                                            <svg class="mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <h3 class="text-lg font-medium text-gray-900">{{ version.nombre_archivo }}</h3>
                                        </div>
                                        <span
                                            v-if="index === 0"
                                            class="ml-3 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                        >
                                            Ultima version
                                        </span>
                                    </div>

                                    <div class="mb-3 grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Subido por</label>
                                            <p class="text-gray-600">{{ version.creador?.name || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Fecha</label>
                                            <p class="text-gray-600">{{ formatDate(version.created_at) }}</p>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Tamano</label>
                                            <p class="text-gray-600">{{ version.tamaño_archivo }}</p>
                                        </div>
                                    </div>

                                    <div v-if="version.comentario" class="mb-3">
                                        <label class="mb-1 block text-sm font-medium text-gray-700">Comentario</label>
                                        <p class="rounded-md bg-gray-50 p-3 text-gray-600">{{ version.comentario }}</p>
                                    </div>

                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Version {{ versiones.length - index }}
                                    </div>
                                </div>

                                <div class="ml-4 flex flex-shrink-0 space-x-2">
                                    <a
                                        :href="route('versiones.download', version.id)"
                                        target="_blank"
                                        class="flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700"
                                    >
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Descargar
                                    </a>
                                    <button
                                        @click="confirmDeleteVersion(version.id, version.nombre_archivo)"
                                        class="flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-700"
                                    >
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-12 text-center">
                        <svg class="mx-auto mb-4 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mb-2 text-lg font-medium text-gray-900">No hay versiones disponibles</h3>
                        <p class="mb-4 text-gray-500">Este proyecto aun no tiene versiones de documentos subidas.</p>
                        <Link
                            :href="route('proyectos.show', proyecto.id)"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700"
                        >
                            Volver al Proyecto
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    proyecto: Object,
    versiones: Array,
    auth: Object,
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const feedbackMessage = ref(props.flash?.success || '');
const errorMessage = ref('');

const getEstadoBadgeClass = (estado) => {
    const classes = {
        Inscrito: 'bg-blue-100 text-blue-800',
        'En revisión': 'bg-yellow-100 text-yellow-800',
        Aprobado: 'bg-green-100 text-green-800',
        Rechazado: 'bg-red-100 text-red-800',
    };

    return classes[estado] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

const confirmDeleteVersion = (versionId, fileName) => {
    if (confirm(`¿Estas seguro de que quieres eliminar la version "${fileName}"?\n\nEsta accion no se puede deshacer.`)) {
        deleteVersion(versionId);
    }
};

const deleteVersion = (versionId) => {
    feedbackMessage.value = '';
    errorMessage.value = '';

    router.delete(route('versiones.delete', versionId), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMessage.value = 'Version eliminada exitosamente.';
            router.reload({ only: ['versiones', 'flash'] });
        },
        onError: () => {
            errorMessage.value = 'No se pudo eliminar la version.';
        },
    });
};
</script>
