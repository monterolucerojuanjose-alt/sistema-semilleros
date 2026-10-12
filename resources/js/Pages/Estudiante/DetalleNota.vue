<template>
    <Head :title="`Evaluación: ${propuesta.titulo_proyecto}`" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Header con botón volver -->
                <div class="mb-6 flex items-center">
                    <Link :href="route('mis-notas')" class="text-gray-600 hover:text-gray-900 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900">Detalle de Evaluación</h1>
                        <p class="text-sm text-gray-600">{{ propuesta.evento.titulo }}</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Información del Proyecto -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-8 text-white">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h2 class="text-2xl font-bold mb-2">{{ propuesta.titulo_proyecto }}</h2>
                                    <p class="text-blue-100">{{ propuesta.descripcion_proyecto }}</p>
                                </div>
                                <div class="text-right ml-6">
                                    <div class="text-5xl font-bold mb-1">{{ propuesta.nota_final }}</div>
                                    <div class="text-sm text-blue-100">Nota Final / 100</div>
                                    <div class="mt-3">
                                        <span :class="getNotaBadgeClass(propuesta.nota_final)" class="px-3 py-1 text-xs font-semibold rounded-full">
                                            {{ getNotaTexto(propuesta.nota_final) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <!-- Información del evaluador y fecha -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-500">Evaluador</p>
                                        <p class="text-base font-semibold text-gray-900">{{ propuesta.evaluador.name }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-500">Fecha de Evaluación</p>
                                        <p class="text-base font-semibold text-gray-900">{{ formatDate(propuesta.fecha_evaluacion) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Análisis del Proyecto -->
                            <div v-if="propuesta.analisis_proyecto" class="mb-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Análisis General del Proyecto
                                </h3>
                                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ propuesta.analisis_proyecto }}</p>
                                </div>
                            </div>

                            <!-- Fortalezas -->
                            <div v-if="propuesta.fortalezas" class="mb-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Fortalezas del Proyecto
                                </h3>
                                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                    <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ propuesta.fortalezas }}</p>
                                </div>
                            </div>

                            <!-- Debilidades -->
                            <div v-if="propuesta.debilidades" class="mb-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Áreas de Mejora
                                </h3>
                                <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                                    <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ propuesta.debilidades }}</p>
                                </div>
                            </div>

                            <!-- Recomendaciones -->
                            <div v-if="propuesta.recomendaciones" class="mb-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    Recomendaciones
                                </h3>
                                <div class="p-4 bg-purple-50 border border-purple-200 rounded-lg">
                                    <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ propuesta.recomendaciones }}</p>
                                </div>
                            </div>

                            <!-- Información del proyecto -->
                            <div class="pt-6 border-t border-gray-200">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Información del Proyecto</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Aula Asignada</p>
                                        <p class="text-base text-gray-900">{{ propuesta.aula || 'No asignada' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Horario</p>
                                        <p class="text-base text-gray-900">{{ propuesta.horario || 'No asignado' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex justify-between">
                        <Link
                            :href="route('mis-notas')"
                            class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Volver a Mis Notas
                        </Link>

                        <a
                            v-if="propuesta.archivo_proyecto"
                            :href="`/storage/${propuesta.archivo_proyecto}`"
                            target="_blank"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Descargar Proyecto
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    propuesta: Object,
    auth: Object,
});

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getNotaTexto = (nota) => {
    const notaNum = parseFloat(nota);
    if (notaNum >= 90) return 'Excelente';
    if (notaNum >= 80) return 'Sobresaliente';
    if (notaNum >= 70) return 'Bueno';
    if (notaNum >= 60) return 'Aceptable';
    return 'Insuficiente';
};

const getNotaBadgeClass = (nota) => {
    const notaNum = parseFloat(nota);
    if (notaNum >= 90) return 'bg-green-100 text-green-800';
    if (notaNum >= 80) return 'bg-blue-100 text-blue-800';
    if (notaNum >= 70) return 'bg-yellow-100 text-yellow-800';
    if (notaNum >= 60) return 'bg-orange-100 text-orange-800';
    return 'bg-red-100 text-red-800';
};
</script>





