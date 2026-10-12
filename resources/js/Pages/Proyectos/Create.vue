<template>
    <Head title="Crear Proyecto" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center">
                        <Link
                            :href="route('proyectos.index')"
                            class="text-gray-500 hover:text-gray-700 mr-4"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Crear Nuevo Proyecto</h1>
                            <p class="text-gray-600 mt-2">Registra un nuevo proyecto de investigación</p>
                        </div>
                    </div>
                </div>

                <!-- Error message -->
                <div v-if="error" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Error</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>{{ error }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sin semillero aprobado no se puede registrar un proyecto -->
                <div v-if="!puedeCrear" class="rounded-xl border border-amber-200 bg-amber-50 p-6">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-semibold text-amber-900">
                                Primero debes pertenecer a un semillero
                            </h2>

                            <p v-if="solicitudPendiente" class="mt-2 text-sm text-amber-800">
                                Ya enviaste una solicitud y está <strong>pendiente de aprobación</strong>.
                                En cuanto el coordinador o el tutor la acepte, podrás registrar tus proyectos aquí.
                                Te avisaremos con una notificación.
                            </p>
                            <p v-else class="mt-2 text-sm text-amber-800">
                                Los proyectos de investigación se registran dentro de un semillero.
                                Solicita unirte a uno y, cuando aprueben tu solicitud, podrás crear tu proyecto.
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <Link
                                    v-if="!solicitudPendiente"
                                    :href="route('semilleros.index')"
                                    class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-700"
                                >
                                    Ver semilleros disponibles
                                </Link>
                                <Link
                                    :href="route('proyectos.index')"
                                    class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-medium text-amber-800 transition-colors hover:bg-amber-100"
                                >
                                    Volver a mis proyectos
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Título del proyecto -->
                            <div>
                                <label for="titulo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Título del Proyecto *
                                </label>
                                <input
                                    id="titulo"
                                    v-model="form.titulo"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :class="{ 'border-red-500': errors.titulo }"
                                    placeholder="Ingresa el título del proyecto"
                                />
                                <p v-if="errors.titulo" class="mt-1 text-sm text-red-600">{{ errors.titulo }}</p>
                            </div>

                            <!-- Semillero -->
                            <div>
                                <label for="semillero_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Semillero *
                                </label>
                                <select
                                    id="semillero_id"
                                    v-model="form.semillero_id"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :class="{ 'border-red-500': errors.semillero_id }"
                                >
                                    <option value="">Selecciona un semillero</option>
                                    <option v-for="semillero in semilleros" :key="semillero.id" :value="semillero.id">
                                        {{ semillero.nombre }} - {{ semillero.linea_tematica }}
                                    </option>
                                </select>
                                <p v-if="errors.semillero_id" class="mt-1 text-sm text-red-600">{{ errors.semillero_id }}</p>
                            </div>

                            <!-- Resumen -->
                            <div>
                                <label for="resumen" class="block text-sm font-medium text-gray-700 mb-2">
                                    Resumen del Proyecto *
                                </label>
                                <textarea
                                    id="resumen"
                                    v-model="form.resumen"
                                    required
                                    rows="6"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :class="{ 'border-red-500': errors.resumen }"
                                    placeholder="Describe brevemente el proyecto (mínimo 50 caracteres)"
                                ></textarea>
                                <div class="flex justify-between items-center mt-1">
                                    <p v-if="errors.resumen" class="text-sm text-red-600">{{ errors.resumen }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ form.resumen.length }}/500 caracteres
                                    </p>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">Información importante</h3>
                                        <div class="mt-2 text-sm text-blue-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>El código del proyecto se generará automáticamente</li>
                                                <li>El estado inicial será "Inscrito"</li>
                                                <li>Podrás subir documentos después de crear el proyecto</li>
                                                <li>El resumen debe tener al menos 50 caracteres</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                            <Link
                                :href="route('proyectos.index')"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Creando...' : 'Crear Proyecto' }}
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    semilleros: Array,
    auth: Object,
    error: String,
    // El estudiante sólo puede registrar proyectos si su solicitud a algún
    // semillero ya fue aprobada.
    puedeCrear: {
        type: Boolean,
        default: true,
    },
    solicitudPendiente: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    titulo: '',
    resumen: '',
    semillero_id: '',
});

const submit = () => {
    form.post(route('proyectos.store'));
};

const errors = computed(() => form.errors);
</script>
