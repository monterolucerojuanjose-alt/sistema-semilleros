<template>
    <AppLayout :title="rubrica.nombre" :auth="page.props.auth">
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <Link
                        :href="route('rubricas.index')"
                        class="mr-4 text-gray-600 hover:text-gray-900"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ rubrica.nombre }}
                        </h2>
                        <p v-if="rubrica.evento || rubrica.modalidad" class="text-xs font-medium text-indigo-700 mt-1">
                            {{ [rubrica.evento?.titulo, rubrica.modalidad].filter(Boolean).join(' · ') }}
                        </p>
                        <p v-if="rubrica.descripcion" class="text-sm text-gray-600 mt-1">
                            {{ rubrica.descripcion }}
                        </p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <Link
                        v-if="canEdit"
                        :href="route('rubricas.edit', rubrica.id)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Información general -->
                    <div class="lg:col-span-2">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-6">Criterios de Evaluación</h3>

                                <div v-if="rubrica.criterios.length === 0" class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h4 class="mt-2 text-sm font-medium text-gray-900">No hay criterios</h4>
                                    <p class="mt-1 text-sm text-gray-500">Esta rúbrica no tiene criterios definidos.</p>
                                </div>

                                <div v-else class="overflow-x-auto">
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
                                                    Puntaje Máximo
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
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ criterio.puntaje_maximo }} puntos
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Evaluaciones realizadas -->
                        <div v-if="rubrica.evaluaciones.length > 0" class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-6">
                                    {{ canEdit ? 'Evaluaciones Realizadas' : 'Tus Evaluaciones con esta Rúbrica' }}
                                </h3>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Proyecto
                                                </th>
                                                <th v-if="canEdit" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Jurado
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Nota Final
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Estado
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Fecha
                                                </th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Acciones
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="evaluacion in rubrica.evaluaciones" :key="evaluacion.id">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ evaluacion.proyecto?.titulo ?? evaluacion.evento_participante?.titulo_proyecto ?? 'N/A' }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ evaluacion.proyecto?.codigo ?? 'Propuesta de evento' }}
                                                    </div>
                                                </td>
                                                <td v-if="canEdit" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ evaluacion.jurado?.name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        :class="getNotaBadgeClass(evaluacion.nota_final)"
                                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                    >
                                                        {{ formatNota(evaluacion.nota_final) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        :class="evaluacion.estado === 'Evaluado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                    >
                                                        {{ evaluacion.estado }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ formatDate(evaluacion.created_at) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <Link
                                                        :href="route('evaluaciones.show', evaluacion.id)"
                                                        class="text-blue-600 hover:text-blue-900"
                                                    >
                                                        Ver
                                                    </Link>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel lateral -->
                    <div class="space-y-6">
                        <!-- Estadísticas -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Estadísticas</h3>

                                <div class="space-y-4">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Total criterios:</span>
                                        <span class="font-medium">{{ rubrica.criterios.length }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Total pesos:</span>
                                        <span class="font-medium">{{ rubrica.total_pesos }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">{{ canEdit ? 'Evaluaciones:' : 'Tus evaluaciones:' }}</span>
                                        <span class="font-medium">{{ rubrica.evaluaciones.length }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Estado:</span>
                                        <span
                                            :class="rubrica.completa ? 'text-green-600' : 'text-yellow-600'"
                                            class="font-medium"
                                        >
                                            {{ rubrica.completa ? 'Completa' : 'Incompleta' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Barra de progreso -->
                                <div class="mt-4">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Pesos</span>
                                        <span>{{ rubrica.total_pesos }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            :class="rubrica.completa ? 'bg-green-500' : 'bg-yellow-500'"
                                            class="h-2 rounded-full transition-all duration-300"
                                            :style="{ width: Math.min(rubrica.total_pesos, 100) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de la rúbrica -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información</h3>

                                <div class="space-y-2 text-sm text-gray-600">
                                    <div>
                                        <span class="font-medium">Creada:</span>
                                        {{ formatDate(rubrica.created_at) }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Última actualización:</span>
                                        {{ formatDate(rubrica.updated_at) }}
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
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    rubrica: Object,
});

const page = usePage();

// Permisos
const canEdit = computed(() => {
    return page.props.auth?.user?.roles?.some(role =>
        ['Administrador', 'Coordinador'].includes(role.name)
    );
});

// Métodos
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

const getNotaBadgeClass = (nota) => {
    if (nota >= 90) return "bg-green-100 text-green-800";
    if (nota >= 80) return "bg-blue-100 text-blue-800";
    if (nota >= 60) return "bg-yellow-100 text-yellow-800";
    return 'bg-red-100 text-red-800';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>
