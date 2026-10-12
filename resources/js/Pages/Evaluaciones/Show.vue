<template>
    <AppLayout :title="'Evaluación: ' + (evaluacion.proyecto?.titulo ?? evaluacion.evento_participante?.titulo_proyecto ?? 'propuesta')" :auth="page.props.auth">
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('evaluaciones.index')"
                    class="mr-4 text-gray-600 hover:text-gray-900"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalle de Evaluación
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Información principal -->
                    <div class="lg:col-span-2">
                        <!-- Información del proyecto -->
                        <div v-if="evaluacion.proyecto" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Proyecto</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Título</label>
                                        <p class="text-sm text-gray-900">{{ evaluacion.proyecto.titulo }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Código</label>
                                        <p class="text-sm text-gray-900">{{ evaluacion.proyecto.codigo }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Autor</label>
                                        <p class="text-sm text-gray-900">{{ evaluacion.proyecto.autor.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Semillero</label>
                                        <p class="text-sm text-gray-900">{{ evaluacion.proyecto.semillero.nombre }}</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-500">Resumen</label>
                                    <p class="text-sm text-gray-900 mt-1">{{ evaluacion.proyecto.resumen }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Información de la propuesta de evento -->
                        <div v-else-if="evaluacion.evento_participante" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información de la Propuesta</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Título</label>
                                        <p class="text-sm text-gray-900">{{ evaluacion.evento_participante.titulo_proyecto }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Evento</label>
                                        <p class="text-sm text-gray-900">{{ evaluacion.evento_participante.evento?.titulo }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Autor</label>
                                        <p class="text-sm text-gray-900">{{ evaluacion.evento_participante.usuario?.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Modalidad</label>
                                        <p class="text-sm text-gray-900">{{ evaluacion.evento_participante.modalidad }}</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-500">Descripción</label>
                                    <p class="text-sm text-gray-900 mt-1">{{ evaluacion.evento_participante.descripcion_proyecto }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Detalle de la evaluación -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    Evaluación con Rúbrica: {{ evaluacion.rubrica.nombre }}
                                </h3>

                                <div class="overflow-x-auto">
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
                                                    Puntaje Otorgado
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Puntuación
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="criterio in evaluacion.rubrica.criterios" :key="criterio.id">
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
                                                    <span class="text-sm text-gray-900">
                                                        {{ getPuntajeOtorgado(criterio.id) }}/{{ criterio.puntaje_maximo }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="text-sm text-gray-900">
                                                        {{ calcularPuntuacionCriterio(criterio) }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div v-if="evaluacion.observaciones" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Observaciones del Jurado</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ evaluacion.observaciones }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel lateral -->
                    <div class="space-y-6">
                        <!-- Resumen de la evaluación -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Resumen</h3>

                                <div class="space-y-4">
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-blue-600">
                                            {{ formatNota(evaluacion.nota_final) }}
                                        </div>
                                        <div class="text-sm text-gray-500">Nota Final</div>
                                    </div>

                                    <div class="border-t pt-4">
                                        <div class="flex justify-between text-sm mb-2">
                                            <span class="text-gray-500">Estado:</span>
                                            <span
                                                :class="evaluacion.estado === 'Evaluado' ? 'text-green-600' : 'text-yellow-600'"
                                                class="font-medium"
                                            >
                                                {{ evaluacion.estado }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between text-sm mb-2">
                                            <span class="text-gray-500">Puntaje Total:</span>
                                            <span class="font-medium">
                                                {{ evaluacion.puntaje_total || 0 }}/{{ evaluacion.puntaje_maximo || 0 }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Fecha:</span>
                                            <span class="font-medium">{{ formatDate(evaluacion.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Barra de progreso -->
                                <div class="mt-4">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Progreso</span>
                                        <span>{{ evaluacion.detalle_evaluaciones?.length || 0 }}/{{ evaluacion.rubrica.criterios.length }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: ((evaluacion.detalle_evaluaciones?.length || 0) / evaluacion.rubrica.criterios.length) * 100 + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del jurado -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Jurado Evaluador</h3>

                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ evaluacion.jurado.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ evaluacion.jurado.email }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de la rúbrica -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Rúbrica Utilizada</h3>

                                <div class="space-y-2 text-sm text-gray-600">
                                    <div>
                                        <span class="font-medium">Nombre:</span>
                                        {{ evaluacion.rubrica.nombre }}
                                    </div>
                                    <div v-if="evaluacion.rubrica.descripcion">
                                        <span class="font-medium">Descripción:</span>
                                        <p class="mt-1">{{ evaluacion.rubrica.descripcion }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium">Total criterios:</span>
                                        {{ evaluacion.rubrica.criterios.length }}
                                    </div>
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
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    evaluacion: Object,
});

// Métodos
const getPuntajeOtorgado = (criterioId) => {
    const detalle = props.evaluacion.detalle_evaluaciones?.find(d => d.criterio_id === criterioId);
    return detalle?.puntaje || 0;
};

const calcularPuntuacionCriterio = (criterio) => {
    const puntaje = getPuntajeOtorgado(criterio.id);
    if (!puntaje) return 0;

    const puntuacionNormalizada = (puntaje / criterio.puntaje_maximo) * 100;
    return (puntuacionNormalizada * (criterio.peso / 100)).toFixed(2);
};

/**
 * nota_final llega como texto ("97.00") porque la columna es decimal(5,2);
 * hay que convertirla antes de formatear o .toFixed() rompe la vista.
 */
const formatNota = (nota) => {
    const valor = Number(nota);
    return nota === null || nota === undefined || Number.isNaN(valor)
        ? 'N/A'
        : valor.toFixed(2);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>
