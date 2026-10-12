<template>
    <Head :title="`Editar ${proyecto.titulo}`" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center">
                        <Link
                            :href="route('proyectos.show', proyecto.id)"
                            class="text-gray-500 hover:text-gray-700 mr-4"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Editar Proyecto</h1>
                            <p class="text-gray-600 mt-2">{{ proyecto.codigo }}</p>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
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

                            <!-- Información del semillero (solo lectura) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Semillero
                                </label>
                                <div class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-700">
                                    {{ proyecto.semillero?.nombre || 'N/A' }} - {{ proyecto.semillero?.linea_tematica || 'N/A' }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">El semillero no se puede cambiar después de crear el proyecto</p>
                            </div>

                            <!-- Información adicional -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Información importante</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>El código del proyecto no se puede cambiar</li>
                                                <li>El semillero no se puede modificar</li>
                                                <li>Los cambios se guardarán inmediatamente</li>
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
                                :href="route('proyectos.show', proyecto.id)"
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
                                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
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
    proyecto: Object,
    auth: Object,
});

const form = useForm({
    titulo: props.proyecto.titulo,
    resumen: props.proyecto.resumen,
});

const submit = () => {
    form.put(route('proyectos.update', props.proyecto.id));
};

const errors = computed(() => form.errors);
</script>





