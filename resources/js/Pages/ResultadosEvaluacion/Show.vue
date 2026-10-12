<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    resultado: Object
});

// Función para formatear fecha
const formatearFecha = (fecha) => {
    if (!fecha) return 'N/A';
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Función para obtener el color de la nota
const getNotaColor = (nota) => {
    const notaNum = nota || 0;
    if (notaNum >= 90) return 'text-green-600';
    if (notaNum >= 70) return 'text-blue-600';
    if (notaNum >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

// Función para obtener el color del badge según el estado
const getEstadoBadgeClass = (estado) => {
    const classes = {
        'sin_evaluar': 'bg-yellow-100 text-yellow-800',
        'evaluado': 'bg-green-100 text-green-800',
        'rechazado': 'bg-red-100 text-red-800'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

// Función para obtener el texto del estado
const getEstadoTexto = (estado) => {
    const textos = {
        'sin_evaluar': 'Sin Evaluar',
        'evaluado': 'Evaluado',
        'rechazado': 'Rechazado'
    };
    return textos[estado] || estado;
};
</script>

<template>
    <Head title="Detalle de Evaluación" />

    <AppLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Detalle de Evaluación</h1>
                            <p class="mt-2 text-gray-600">Información completa de la evaluación del proyecto</p>
                        </div>
                        <Link
                            :href="route('resultados-evaluacion.index')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Volver a Resultados
                        </Link>
                    </div>
                </div>

                <!-- Información del Estudiante -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Estudiante</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-xl font-bold text-white">
                                    {{ resultado.usuario.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">{{ resultado.usuario.name }}</h4>
                                <p class="text-gray-600">{{ resultado.usuario.email }}</p>
                                <p v-if="resultado.modalidad || resultado.area_participacion" class="text-sm text-gray-500">
                                    {{ [resultado.modalidad, resultado.area_participacion].filter(Boolean).join(' · ') }}
                                </p>
                                <p v-else class="text-sm text-gray-500">Carrera: {{ resultado.carrera || 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Fecha de Inscripción:</span>
                                <span class="font-medium">{{ formatearFecha(resultado.created_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Estado de Participación:</span>
                                <span :class="getEstadoBadgeClass(resultado.estado)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                    {{ getEstadoTexto(resultado.estado) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Evento -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Evento</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-lg font-medium text-gray-900">{{ resultado.evento.titulo }}</h4>
                            <p class="text-gray-600 mt-1">{{ resultado.evento.descripcion }}</p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Fecha de Inicio:</span>
                                <span class="font-medium">{{ formatearFecha(resultado.evento.fecha_inicio) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Fecha de Fin:</span>
                                <span class="font-medium">{{ formatearFecha(resultado.evento.fecha_fin) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Modalidad:</span>
                                <span class="font-medium">{{ resultado.evento.modalidad }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Proyecto -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Proyecto</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Título del Proyecto</label>
                            <p class="text-gray-900 font-medium">{{ resultado.titulo_proyecto || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción del Proyecto</label>
                            <p class="text-gray-900">{{ resultado.descripcion_proyecto || 'N/A' }}</p>
                        </div>
                        <div v-if="resultado.archivo_proyecto">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Archivo del Proyecto</label>
                            <a :href="resultado.archivo_proyecto" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Descargar Archivo
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Información del Evaluador -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Evaluador</h3>
                    <div v-if="resultado.evaluador" class="flex items-center">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <span class="text-lg font-bold text-white">
                                {{ resultado.evaluador.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-medium text-gray-900">{{ resultado.evaluador.name }}</h4>
                            <p class="text-gray-600">{{ resultado.evaluador.email }}</p>
                        </div>
                    </div>
                    <div v-else class="text-gray-500">
                        <p>No se ha asignado un evaluador para este proyecto.</p>
                    </div>
                </div>

                <!-- Resultados de la Evaluación -->
                <div v-if="resultado.estado_evaluacion === 'evaluado'" class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Resultados de la Evaluación</h3>

                    <!-- Nota Final -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-lg font-medium text-gray-900">Nota Final</h4>
                            <span :class="getNotaColor(resultado.nota_final)" class="text-3xl font-bold">
                                {{ Number(resultado.nota_final || 0).toFixed(2) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div
                                class="h-3 rounded-full transition-all duration-300"
                                :class="{
                                    'bg-green-500': (resultado.nota_final || 0) >= 90,
                                    'bg-blue-500': (resultado.nota_final || 0) >= 70 && (resultado.nota_final || 0) < 90,
                                    'bg-yellow-500': (resultado.nota_final || 0) >= 60 && (resultado.nota_final || 0) < 70,
                                    'bg-red-500': (resultado.nota_final || 0) < 60
                                }"
                                :style="{ width: `${Math.min(Number(resultado.nota_final || 0), 100)}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Análisis del Proyecto -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Análisis del Proyecto</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-900">{{ resultado.analisis_proyecto || 'No disponible' }}</p>
                        </div>
                    </div>

                    <!-- Fortalezas -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fortalezas</label>
                        <div class="bg-green-50 rounded-lg p-4">
                            <p class="text-gray-900">{{ resultado.fortalezas || 'No disponible' }}</p>
                        </div>
                    </div>

                    <!-- Debilidades -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Debilidades</label>
                        <div class="bg-red-50 rounded-lg p-4">
                            <p class="text-gray-900">{{ resultado.debilidades || 'No disponible' }}</p>
                        </div>
                    </div>

                    <!-- Recomendaciones -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recomendaciones</label>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-gray-900">{{ resultado.recomendaciones || 'No disponible' }}</p>
                        </div>
                    </div>

                    <!-- Fecha de Evaluación -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <span class="text-sm text-gray-600">Fecha de Evaluación:</span>
                        <span class="text-sm font-medium text-gray-900">{{ formatearFecha(resultado.fecha_evaluacion) }}</span>
                    </div>
                </div>

                <!-- Estado Pendiente -->
                <div v-else-if="resultado.estado_evaluacion === 'sin_evaluar'" class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Evaluación Pendiente</h3>
                        <p class="text-gray-600">Este proyecto aún no ha sido evaluado por el jurado asignado.</p>
                    </div>
                </div>

                <!-- Estado Rechazado -->
                <div v-else-if="resultado.estado_evaluacion === 'rechazado'" class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Proyecto Rechazado</h3>
                        <p class="text-gray-600">Este proyecto ha sido rechazado por el evaluador asignado.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
