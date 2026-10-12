<template>
    <AppLayout title="Nueva Rúbrica" :auth="page.props.auth">
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('rubricas.index')"
                    class="mr-4 text-gray-600 hover:text-gray-900"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Nueva Rúbrica
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit">
                            <!-- Información básica -->
                            <div class="grid grid-cols-1 gap-6 mb-8">
                                <div>
                                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nombre de la Rúbrica *
                                    </label>
                                    <input
                                        id="nombre"
                                        v-model="form.nombre"
                                        type="text"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="{ 'border-red-500': errors.nombre }"
                                        placeholder="Ej: Rúbrica de Evaluación para Ponencias 2025"
                                    />
                                    <p v-if="errors.nombre" class="mt-1 text-sm text-red-600">
                                        {{ errors.nombre }}
                                    </p>
                                </div>

                                <div>
                                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                                        Descripción y criterio de evaluación *
                                    </label>
                                    <textarea
                                        id="descripcion"
                                        v-model="form.descripcion"
                                        required
                                        rows="4"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="{ 'border-red-500': errors.descripcion }"
                                        placeholder="Explica para qué tipo de proyecto o propuesta es esta rúbrica, y cómo debe interpretarla el jurado al calificar: qué se espera en cada nivel, a qué le debe dar prioridad, etc."
                                    ></textarea>
                                    <p class="mt-1 text-xs text-gray-500">
                                        El jurado verá este texto al calificar con esta rúbrica.
                                    </p>
                                    <p v-if="errors.descripcion" class="mt-1 text-sm text-red-600">
                                        {{ errors.descripcion }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="evento_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Evento
                                        </label>
                                        <select
                                            id="evento_id"
                                            v-model="form.evento_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">Genérica (todos los eventos)</option>
                                            <option v-for="evento in eventos" :key="evento.id" :value="evento.id">{{ evento.titulo }}</option>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Si la ligas a un evento, el jurado la verá al calificar las propuestas de ese evento.
                                        </p>
                                    </div>

                                    <div>
                                        <label for="modalidad" class="block text-sm font-medium text-gray-700 mb-2">
                                            Modalidad
                                        </label>
                                        <select
                                            id="modalidad"
                                            v-model="form.modalidad"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">Todas las modalidades</option>
                                            <option v-for="modalidad in modalidades" :key="modalidad" :value="modalidad">{{ modalidad }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Criterios -->
                            <div class="mb-8">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Criterios de Evaluación</h3>
                                    <button
                                        type="button"
                                        @click="addCriterio"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Agregar Criterio
                                    </button>
                                </div>

                                <div v-if="form.criterios.length === 0" class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay criterios</h3>
                                    <p class="mt-1 text-sm text-gray-500">Agrega al menos un criterio para crear la rúbrica.</p>
                                </div>

                                <div v-else class="space-y-4">
                                    <div
                                        v-for="(criterio, index) in form.criterios"
                                        :key="index"
                                        class="border border-gray-200 rounded-lg p-4"
                                    >
                                        <div class="flex justify-between items-start mb-4">
                                            <h4 class="text-sm font-medium text-gray-700">
                                                Criterio {{ index + 1 }}
                                            </h4>
                                            <button
                                                type="button"
                                                @click="removeCriterio(index)"
                                                class="text-red-600 hover:text-red-800"
                                                :disabled="form.criterios.length === 1"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Nombre del Criterio *
                                                </label>
                                                <input
                                                    v-model="criterio.criterio"
                                                    type="text"
                                                    required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                    placeholder="Ej: Originalidad, Claridad, Metodología..."
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Peso (%) *
                                                </label>
                                                <input
                                                    v-model.number="criterio.peso"
                                                    type="number"
                                                    min="0.01"
                                                    max="100"
                                                    step="0.01"
                                                    required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Puntaje Máximo *
                                                </label>
                                                <select
                                                    v-model.number="criterio.puntaje_maximo"
                                                    required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                >
                                                    <option value="3">3 puntos</option>
                                                    <option value="4">4 puntos</option>
                                                    <option value="5">5 puntos</option>
                                                    <option value="7">7 puntos</option>
                                                    <option value="10">10 puntos</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Resumen de pesos -->
                                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-700">Total de pesos:</span>
                                        <span
                                            :class="totalPesos === 100 ? 'text-green-600' : 'text-red-600'"
                                            class="font-semibold"
                                        >
                                            {{ totalPesos.toFixed(2) }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            :class="totalPesos === 100 ? 'bg-green-500' : 'bg-red-500'"
                                            class="h-2 rounded-full transition-all duration-300"
                                            :style="{ width: Math.min(totalPesos, 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p v-if="totalPesos !== 100" class="mt-2 text-sm text-red-600">
                                        El total de pesos debe ser exactamente 100%
                                    </p>
                                    <p v-else class="mt-2 text-sm text-green-600">
                                        ✓ Los pesos están correctamente distribuidos
                                    </p>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end space-x-4">
                                <Link
                                    :href="route('rubricas.index')"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing || totalPesos !== 100 || form.criterios.length === 0"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing">Guardando...</span>
                                    <span v-else>Guardar Rúbrica</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    eventos: { type: Array, default: () => [] },
    modalidades: { type: Array, default: () => [] },
});

const page = usePage();

const form = useForm({
    nombre: '',
    descripcion: '',
    evento_id: '',
    modalidad: '',
    criterios: []
});

const errors = computed(() => form.errors);

// Computed para calcular el total de pesos
const totalPesos = computed(() => {
    return form.criterios.reduce((total, criterio) => {
        return total + (parseFloat(criterio.peso) || 0);
    }, 0);
});

// Métodos
const addCriterio = () => {
    form.criterios.push({
        criterio: '',
        peso: 0,
        puntaje_maximo: 5
    });
};

const removeCriterio = (index) => {
    if (form.criterios.length > 1) {
        form.criterios.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('rubricas.store'));
};

// Agregar un criterio inicial
addCriterio();
</script>
