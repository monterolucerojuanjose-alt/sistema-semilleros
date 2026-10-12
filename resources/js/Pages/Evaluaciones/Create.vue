<template>
    <AppLayout title="Evaluar Proyecto" :auth="auth">
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('evaluaciones.proyectos-asignados')"
                    class="mr-4 text-gray-600 hover:text-gray-900"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Evaluar Proyecto
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Mensaje de error si no hay datos -->
                <div v-if="!proyecto || !rubrica" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <h3 class="font-medium text-red-800">Error</h3>
                    <p class="text-red-700">No se pudieron cargar los datos del proyecto o la rúbrica. Por favor, intenta nuevamente.</p>
                </div>

                <!-- Información del proyecto -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Proyecto</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Título</label>
                                <p class="text-sm text-gray-900">{{ proyecto.titulo }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Código</label>
                                <p class="text-sm text-gray-900">{{ proyecto.codigo }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Autor</label>
                                <p class="text-sm text-gray-900">{{ proyecto.autor.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Semillero</label>
                                <p class="text-sm text-gray-900">{{ proyecto.semillero.nombre }}</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-500">Resumen</label>
                            <p class="text-sm text-gray-900 mt-1">{{ proyecto.resumen }}</p>
                        </div>
                    </div>
                </div>

                <!-- Formulario de evaluación -->
                <div v-if="proyecto && rubrica" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Evaluación con Rúbrica: {{ rubrica.nombre }}
                        </h3>
                        <div v-if="rubrica.descripcion" class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Cómo calificar con esta rúbrica</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-indigo-900">{{ rubrica.descripcion }}</p>
                        </div>

                        <form @submit.prevent="submit">
                            <!-- Tabla de criterios -->
                            <div class="overflow-x-auto mb-6">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Criterio
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Peso
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Puntaje
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Puntuación
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="criterio in rubrica.criterios" :key="criterio.id">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ criterio.criterio }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <span class="text-sm text-gray-900">{{ criterio.peso }}%</span>
                                                    <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                                        <div
                                                            class="bg-blue-500 h-2 rounded-full"
                                                            :style="{ width: criterio.peso + '%' }"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <select
                                                    v-model="form.puntajes[criterio.id]"
                                                    required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                    :class="{ 'border-red-500': errors[`puntajes.${criterio.id}`] }"
                                                >
                                                    <option value="">Selecciona puntaje</option>
                                                    <option
                                                        v-for="puntaje in range(1, criterio.puntaje_maximo + 1)"
                                                        :key="puntaje"
                                                        :value="puntaje"
                                                    >
                                                        {{ puntaje }} punto{{ puntaje !== 1 ? 's' : '' }}
                                                    </option>
                                                </select>
                                                <p v-if="errors[`puntajes.${criterio.id}`]" class="mt-1 text-xs text-red-600">
                                                    {{ errors[`puntajes.${criterio.id}`] }}
                                                </p>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ calcularPuntuacionCriterio(criterio) }}%
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Observaciones -->
                            <div class="mb-6">
                                <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-2">
                                    Observaciones
                                </label>
                                <textarea
                                    id="observaciones"
                                    v-model="form.observaciones"
                                    rows="4"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Escribe aquí tus comentarios y observaciones sobre la evaluación..."
                                ></textarea>
                            </div>

                            <!-- Resumen de la evaluación -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Resumen de la Evaluación</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <span class="text-sm text-gray-500">Nota Final:</span>
                                        <div class="text-2xl font-bold text-blue-600">
                                            {{ calcularNotaFinal().toFixed(2) }}
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-500">Puntaje Total:</span>
                                        <div class="text-lg font-semibold text-gray-900">
                                            {{ calcularPuntajeTotal() }}/{{ calcularPuntajeMaximo() }}
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-500">Estado:</span>
                                        <div class="text-lg font-semibold text-green-600">
                                            {{ todosLosCriteriosEvaluados ? 'Completa' : 'Incompleta' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Barra de progreso -->
                                <div class="mt-4">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Progreso de evaluación</span>
                                        <span>{{ criteriosEvaluados }}/{{ rubrica.criterios.length }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: (criteriosEvaluados / rubrica.criterios.length) * 100 + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end space-x-4">
                                <Link
                                    :href="route('evaluaciones.proyectos-asignados')"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing || !todosLosCriteriosEvaluados"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing">Guardando...</span>
                                    <span v-else>Guardar Evaluación</span>
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
    proyecto: Object,
    rubrica: Object,
    auth: Object,
    debug_info: Object,
    error: String,
});

const page = usePage();

const form = useForm({
    proyecto_id: props.proyecto?.id || null,
    rubrica_id: props.rubrica?.id || null,
    observaciones: '',
    puntajes: {}
});

const errors = computed(() => form.errors);

// Computed properties
const criteriosEvaluados = computed(() => {
    return Object.keys(form.puntajes).filter(criterioId =>
        form.puntajes[criterioId] !== null && form.puntajes[criterioId] !== undefined && form.puntajes[criterioId] !== ''
    ).length;
});

const todosLosCriteriosEvaluados = computed(() => {
    if (!props.rubrica?.criterios) return false;
    return criteriosEvaluados.value === props.rubrica.criterios.length;
});

// Métodos
const range = (start, end) => {
    const result = [];
    for (let i = start; i <= end; i++) {
        result.push(i);
    }
    return result;
};

const calcularPuntuacionCriterio = (criterio) => {
    const puntaje = form.puntajes[criterio.id];
    if (!puntaje) return 0;

    const puntuacionNormalizada = (puntaje / criterio.puntaje_maximo) * 100;
    return (puntuacionNormalizada * (criterio.peso / 100)).toFixed(2);
};

const calcularPuntajeTotal = () => {
    return Object.values(form.puntajes).reduce((total, puntaje) => {
        return total + (parseInt(puntaje) || 0);
    }, 0);
};

const calcularPuntajeMaximo = () => {
    if (!props.rubrica?.criterios) return 0;
    return props.rubrica.criterios.reduce((total, criterio) => {
        return total + criterio.puntaje_maximo;
    }, 0);
};

const calcularNotaFinal = () => {
    let notaFinal = 0;

    if (!props.rubrica?.criterios) return 0;

    props.rubrica.criterios.forEach(criterio => {
        const puntaje = form.puntajes[criterio.id];
        if (puntaje) {
            const puntuacionNormalizada = (puntaje / criterio.puntaje_maximo) * 100;
            notaFinal += puntuacionNormalizada * (criterio.peso / 100);
        }
    });

    return notaFinal;
};

const submit = () => {
    form.post(route('evaluaciones.store'));
};
</script>
