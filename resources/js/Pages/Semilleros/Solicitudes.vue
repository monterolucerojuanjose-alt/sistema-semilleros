<template>
    <AppLayout title="Solicitudes de Estudiantes" :auth="page.props.auth">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Solicitudes de Estudiantes
                </h2>
                <div class="flex items-center space-x-2">
                    <span class="bg-yellow-100 text-yellow-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
                        {{ solicitudes.length }} pendientes
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="solicitudes.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay solicitudes pendientes</h3>
                            <p class="mt-1 text-sm text-gray-500">Todos los estudiantes han sido procesados.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="solicitud in solicitudes"
                                :key="solicitud.id"
                                class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm"
                            >
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ solicitud.estudiante_nombre }}
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            {{ solicitud.estudiante_email }}
                                        </p>
                                    </div>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pendiente
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Semillero Solicitado
                                        </label>
                                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                                            {{ solicitud.semillero_nombre }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Fecha de Solicitud
                                        </label>
                                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                                            {{ formatDate(solicitud.fecha) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <button
                                        @click="aprobarSolicitud(solicitud)"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Aprobar
                                    </button>
                                    <button
                                        @click="rechazarSolicitud(solicitud)"
                                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Rechazar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    solicitudes: Array,
});

const page = usePage();

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const aprobarSolicitud = (solicitud) => {
    if (confirm('¿Estás seguro de que quieres aprobar esta solicitud?')) {
        router.put(route('semilleros.approve-join', {
            semillero: solicitud.semillero_id,
            user: solicitud.user_id
        }), {
            estado: 'aprobado'
        }, {
            onSuccess: () => {
                // Recargar la página para actualizar la lista
                router.reload();
            }
        });
    }
};

const rechazarSolicitud = (solicitud) => {
    if (confirm('¿Estás seguro de que quieres rechazar esta solicitud?')) {
        router.put(route('semilleros.approve-join', {
            semillero: solicitud.semillero_id,
            user: solicitud.user_id
        }), {
            estado: 'rechazado'
        }, {
            onSuccess: () => {
                // Recargar la página para actualizar la lista
                router.reload();
            }
        });
    }
};
</script>
