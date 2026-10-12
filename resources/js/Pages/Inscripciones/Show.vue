<template>
    <AppLayout title="Detalle de Inscripción">
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('inscripciones.index')"
                    class="mr-4 text-gray-600 hover:text-gray-900"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle de Inscripción
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Información principal -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                            <!-- Información del proyecto -->
                            <div class="lg:col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Proyecto</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Título</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ inscripcion.proyecto.titulo }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Código del Proyecto</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ inscripcion.proyecto.codigo }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Autor</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ inscripcion.proyecto.autor.name }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Semillero</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ inscripcion.proyecto.semillero.nombre }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700">Resumen</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ inscripcion.proyecto.resumen }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de la inscripción -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información de la Inscripción</h3>
                                <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Código de Inscripción</label>
                                        <p class="mt-1 text-lg font-mono text-gray-900">{{ inscripcion.codigo_inscripcion }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ inscripcion.tipo === 'ponencia' ? 'Ponencia' : 'Póster' }}
                                        </span>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                                        <span :class="getEstadoBadgeClass(inscripcion.estado)" class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ inscripcion.estado }}
                                        </span>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Fecha de Inscripción</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(inscripcion.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div v-if="inscripcion.observaciones" class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Observaciones</h3>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <p class="text-sm text-yellow-800">{{ inscripcion.observaciones }}</p>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="flex flex-wrap gap-4">
                            <!-- Descargar comprobante -->
                            <Link
                                :href="route('inscripciones.comprobante', inscripcion.id)"
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Descargar Comprobante
                            </Link>

                            <!-- Actualizar estado (solo para coordinadores y administradores) -->
                            <button
                                v-if="canUpdateEstado"
                                @click="showUpdateModal = true"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Actualizar Estado
                            </button>

                            <!-- Cancelar inscripción (solo para el autor o administradores) -->
                            <button
                                v-if="canCancel"
                                @click="confirmCancel"
                                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Cancelar Inscripción
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para actualizar estado -->
        <div v-if="showUpdateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Actualizar Estado</h3>
                    <form @submit.prevent="updateEstado">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Estado</label>
                            <select
                                v-model="updateForm.estado"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="En revisión">En revisión</option>
                                <option value="Aceptado">Aceptado</option>
                                <option value="Rechazado">Rechazado</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                            <textarea
                                v-model="updateForm.observaciones"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Ingresa observaciones sobre el estado..."
                            ></textarea>
                            <p class="text-xs text-gray-500 mt-1">Las observaciones son obligatorias al aprobar o rechazar</p>
                        </div>
                        <div class="flex justify-end space-x-4">
                            <button
                                type="button"
                                @click="showUpdateModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="updateForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50"
                            >
                                <span v-if="updateForm.processing">Actualizando...</span>
                                <span v-else>Actualizar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    inscripcion: Object,
    auth: Object,
});

const showUpdateModal = ref(false);

const updateForm = useForm({
    estado: props.inscripcion.estado,
    observaciones: props.inscripcion.observaciones || '',
});

const canUpdateEstado = computed(() => {
    return props.auth.user.roles.some(role =>
        ['Administrador', 'Coordinador'].includes(role.name)
    );
});

const canCancel = computed(() => {
    if (props.auth.user.roles.some(role => role.name === 'Administrador')) {
        return true;
    }
    return props.inscripcion.proyecto.autor_id === props.auth.user.id;
});

const updateEstado = () => {
    updateForm.put(route('inscripciones.update', props.inscripcion.id), {
        onSuccess: () => {
            showUpdateModal.value = false;
        }
    });
};

const confirmCancel = () => {
    if (confirm('¿Estás seguro de que quieres cancelar esta inscripción?')) {
        router.delete(route('inscripciones.destroy', props.inscripcion.id));
    }
};

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
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
