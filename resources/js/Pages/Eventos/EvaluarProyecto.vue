<template>
    <Head :title="`Evaluar Proyecto - ${participante.titulo_proyecto}`" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center mb-4">
                        <Link :href="route('proyectos.asignados')" class="text-gray-600 hover:text-gray-900 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                {{ participante.estado_evaluacion === 'evaluado' ? 'Ver Evaluación' : 'Evaluar Proyecto' }}
                            </h1>
                            <p class="text-gray-600 mt-2">{{ participante.titulo_proyecto }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Información del Proyecto -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Datos del Proyecto -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Información del Proyecto</h2>

                            <div class="space-y-3">
                                <div class="flex flex-wrap gap-2">
                                    <span v-if="participante.modalidad" class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                                        {{ participante.modalidad }}
                                    </span>
                                    <span v-if="participante.area_participacion" class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                        {{ participante.area_participacion }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Título</p>
                                    <p class="text-gray-900">{{ participante.titulo_proyecto }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Descripción</p>
                                    <p class="text-gray-900 whitespace-pre-line">{{ participante.descripcion_proyecto }}</p>
                                </div>
                                <div v-if="participante.participantes && participante.participantes.length > 0">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Participantes del Proyecto</p>
                                    <div class="space-y-2">
                                        <div v-for="(part, index) in participante.participantes" :key="index"
                                             class="flex items-center p-2 bg-gray-50 rounded">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ part.nombre }}</p>
                                                <p class="text-xs text-gray-500">{{ part.email }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sin rúbrica disponible -->
                        <div v-if="participante.estado_evaluacion !== 'evaluado' && rubricas.length === 0" class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                            <h2 class="text-lg font-bold text-amber-900 mb-2">No hay ninguna rúbrica para calificar esta propuesta</h2>
                            <p class="text-sm text-amber-800">
                                No existe una rúbrica para la modalidad «{{ participante.modalidad || 'sin modalidad' }}» de este evento, ni una
                                rúbrica genérica de respaldo. Pide al coordinador o administrador que cree una en
                                <span class="font-medium">Rúbricas</span>, ligada a este evento y modalidad (o genérica), para poder calificar.
                            </p>
                        </div>

                        <!-- Formulario de Evaluación -->
                        <div v-else-if="participante.estado_evaluacion !== 'evaluado'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Evaluación del Proyecto</h2>

                            <form @submit.prevent="submit">
                                <div class="space-y-6">
                                    <!-- Selector de rúbrica, si hay más de una candidata -->
                                    <div v-if="rubricas.length > 1">
                                        <label for="rubrica_id" class="block text-sm font-medium text-gray-700 mb-2">
                                            Rúbrica a usar
                                        </label>
                                        <select
                                            id="rubrica_id"
                                            v-model.number="form.rubrica_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option v-for="r in rubricas" :key="r.id" :value="r.id">{{ r.nombre }}</option>
                                        </select>
                                    </div>

                                    <!-- Cómo calificar con esta rúbrica -->
                                    <div v-if="rubricaSeleccionada?.descripcion" class="rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Cómo calificar con esta rúbrica</p>
                                        <p class="mt-1 whitespace-pre-line text-sm text-indigo-900">{{ rubricaSeleccionada.descripcion }}</p>
                                    </div>

                                    <!-- Tabla de criterios -->
                                    <div v-if="rubricaSeleccionada" class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criterio</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peso</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puntaje</th>
                                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puntuación</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="criterio in rubricaSeleccionada.criterios" :key="criterio.id">
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {{ criterio.criterio }}
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ criterio.peso }}%</td>
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        <select
                                                            v-model="form.puntajes[criterio.id]"
                                                            required
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                            :class="{ 'border-red-500': form.errors[`puntajes.${criterio.id}`] }"
                                                        >
                                                            <option value="">Selecciona puntaje</option>
                                                            <option v-for="puntaje in range(1, criterio.puntaje_maximo)" :key="puntaje" :value="puntaje">
                                                                {{ puntaje }} punto{{ puntaje !== 1 ? 's' : '' }}
                                                            </option>
                                                        </select>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                        {{ calcularPuntuacionCriterio(criterio) }}%
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <!-- Resumen de la evaluación -->
                                        <div class="bg-gray-50 rounded-lg p-4 mt-4">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-500">Nota Final (0 a 100):</span>
                                                <span class="text-2xl font-bold text-blue-600">{{ calcularNotaFinal().toFixed(2) }}</span>
                                            </div>
                                            <div class="mt-3">
                                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                                    <span>Progreso de evaluación</span>
                                                    <span>{{ criteriosEvaluados }}/{{ rubricaSeleccionada.criterios.length }}</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div
                                                        class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                                        :style="{ width: (criteriosEvaluados / rubricaSeleccionada.criterios.length) * 100 + '%' }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Análisis del Proyecto -->
                                    <div>
                                        <label for="analisis_proyecto" class="block text-sm font-medium text-gray-700 mb-2">
                                            Análisis General del Proyecto <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.analisis_proyecto"
                                            id="analisis_proyecto"
                                            required
                                            rows="5"
                                            maxlength="2000"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Describe tu análisis general del proyecto..."
                                        ></textarea>
                                        <p class="text-xs text-gray-500 mt-1">{{ form.analisis_proyecto.length }} / 2000 caracteres</p>
                                        <div v-if="form.errors.analisis_proyecto" class="text-red-600 text-sm mt-1">{{ form.errors.analisis_proyecto }}</div>
                                    </div>

                                    <!-- Fortalezas -->
                                    <div>
                                        <label for="fortalezas" class="block text-sm font-medium text-gray-700 mb-2">
                                            Fortalezas del Proyecto <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.fortalezas"
                                            id="fortalezas"
                                            required
                                            rows="4"
                                            maxlength="1000"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Indica los aspectos positivos y puntos fuertes del proyecto..."
                                        ></textarea>
                                        <p class="text-xs text-gray-500 mt-1">{{ form.fortalezas.length }} / 1000 caracteres</p>
                                        <div v-if="form.errors.fortalezas" class="text-red-600 text-sm mt-1">{{ form.errors.fortalezas }}</div>
                                    </div>

                                    <!-- Debilidades -->
                                    <div>
                                        <label for="debilidades" class="block text-sm font-medium text-gray-700 mb-2">
                                            Debilidades / Aspectos a Mejorar <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.debilidades"
                                            id="debilidades"
                                            required
                                            rows="4"
                                            maxlength="1000"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Indica los aspectos que necesitan mejorar..."
                                        ></textarea>
                                        <p class="text-xs text-gray-500 mt-1">{{ form.debilidades.length }} / 1000 caracteres</p>
                                        <div v-if="form.errors.debilidades" class="text-red-600 text-sm mt-1">{{ form.errors.debilidades }}</div>
                                    </div>

                                    <!-- Recomendaciones -->
                                    <div>
                                        <label for="recomendaciones" class="block text-sm font-medium text-gray-700 mb-2">
                                            Recomendaciones <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.recomendaciones"
                                            id="recomendaciones"
                                            required
                                            rows="4"
                                            maxlength="1000"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Proporciona recomendaciones para que el estudiante mejore..."
                                        ></textarea>
                                        <p class="text-xs text-gray-500 mt-1">{{ form.recomendaciones.length }} / 1000 caracteres</p>
                                        <div v-if="form.errors.recomendaciones" class="text-red-600 text-sm mt-1">{{ form.errors.recomendaciones }}</div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="mt-8 flex justify-end space-x-3">
                                    <Link
                                        :href="route('proyectos.asignados')"
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-md font-medium"
                                    >
                                        Cancelar
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="form.processing || !todosLosCriteriosEvaluados"
                                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md font-medium disabled:opacity-50"
                                    >
                                        {{ form.processing ? 'Guardando...' : 'Guardar Evaluación' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Mostrar evaluación (solo lectura) -->
                        <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Evaluación Realizada</h2>

                            <div class="space-y-6">
                                <!-- Nota Final -->
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-600 mb-1">Nota Final</p>
                                    <p class="text-4xl font-bold text-green-600">{{ participante.nota_final }} / 100</p>
                                    <p class="text-xs text-gray-500 mt-2">Evaluado el {{ formatDate(participante.fecha_evaluacion) }}</p>
                                </div>

                                <!-- Desglose por criterio, si quedó registrado con rúbrica -->
                                <div v-if="evaluacionExistente?.detalle_evaluaciones?.length">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Desglose por criterio</p>
                                    <div class="space-y-1">
                                        <div
                                            v-for="detalle in evaluacionExistente.detalle_evaluaciones"
                                            :key="detalle.id"
                                            class="flex justify-between text-sm bg-gray-50 px-3 py-2 rounded"
                                        >
                                            <span class="text-gray-700">{{ detalle.criterio?.criterio }}</span>
                                            <span class="font-medium text-gray-900">{{ detalle.puntaje }} / {{ detalle.criterio?.puntaje_maximo }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Análisis -->
                                <div>
                                    <p class="text-sm font-medium text-gray-700 mb-2">Análisis General</p>
                                    <div class="bg-gray-50 p-4 rounded-md">
                                        <p class="text-gray-900 whitespace-pre-line">{{ participante.analisis_proyecto }}</p>
                                    </div>
                                </div>

                                <!-- Fortalezas -->
                                <div>
                                    <p class="text-sm font-medium text-gray-700 mb-2">Fortalezas</p>
                                    <div class="bg-green-50 p-4 rounded-md">
                                        <p class="text-gray-900 whitespace-pre-line">{{ participante.fortalezas }}</p>
                                    </div>
                                </div>

                                <!-- Debilidades -->
                                <div>
                                    <p class="text-sm font-medium text-gray-700 mb-2">Aspectos a Mejorar</p>
                                    <div class="bg-yellow-50 p-4 rounded-md">
                                        <p class="text-gray-900 whitespace-pre-line">{{ participante.debilidades }}</p>
                                    </div>
                                </div>

                                <!-- Recomendaciones -->
                                <div>
                                    <p class="text-sm font-medium text-gray-700 mb-2">Recomendaciones</p>
                                    <div class="bg-blue-50 p-4 rounded-md">
                                        <p class="text-gray-900 whitespace-pre-line">{{ participante.recomendaciones }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar con Información -->
                    <div class="space-y-6">
                        <!-- Información del Evento -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Evento</h3>
                            <div class="space-y-2">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Nombre:</span><br />
                                    {{ participante.evento?.titulo }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Fecha:</span><br />
                                    {{ formatDate(participante.evento?.fecha_inicio) }}
                                </p>
                            </div>
                        </div>

                        <!-- Información del Estudiante -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Estudiante</h3>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <Avatar :usuario="participante.usuario" tamano="h-12 w-12" texto-tamano="text-lg" />
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900">{{ participante.usuario?.name }}</p>
                                        <p class="text-sm text-gray-500">{{ participante.usuario?.email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Programación -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Programación</h3>
                            <div class="space-y-2">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Aula:</span> {{ participante.aula }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Horario:</span> {{ participante.horario }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Programación:</span><br />
                                    {{ participante.programacion }}
                                </p>
                            </div>
                        </div>

                        <!-- Descargar Proyecto -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Archivo del Proyecto</h3>
                            <a
                                v-if="participante.archivo_proyecto"
                                :href="`/storage/${participante.archivo_proyecto}`"
                                target="_blank"
                                download
                                class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-md font-medium text-center transition-colors"
                            >
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Descargar Proyecto
                            </a>
                            <p v-else class="text-sm text-gray-500 text-center">No hay archivo disponible</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    participante: Object,
    rubricas: { type: Array, default: () => [] },
    evaluacionExistente: { type: Object, default: null },
    auth: Object,
});

// Puntajes ya registrados, si se vuelve a abrir una evaluación para
// corregirla antes de que el jurado la marque como definitiva.
const puntajesPrevios = {};
if (props.evaluacionExistente?.detalle_evaluaciones) {
    for (const detalle of props.evaluacionExistente.detalle_evaluaciones) {
        puntajesPrevios[detalle.criterio_id] = detalle.puntaje;
    }
}

const form = useForm({
    rubrica_id: props.evaluacionExistente?.rubrica_id ?? props.rubricas[0]?.id ?? null,
    puntajes: puntajesPrevios,
    analisis_proyecto: props.participante.analisis_proyecto || '',
    fortalezas: props.participante.fortalezas || '',
    debilidades: props.participante.debilidades || '',
    recomendaciones: props.participante.recomendaciones || '',
});

const rubricaSeleccionada = computed(() => props.rubricas.find((r) => r.id === form.rubrica_id) ?? null);

// Al cambiar de rúbrica se limpian los puntajes: los de la rúbrica anterior
// no pertenecen a los criterios de la nueva, y dejarlos desincronizaba el
// contador de progreso (podía marcar más criterios evaluados que los que
// tiene la rúbrica elegida, o enviar puntajes de criterios que no son de
// esta rúbrica). watch() sin "immediate" no se dispara con el valor inicial,
// así que no borra los puntajes ya guardados al abrir la pantalla.
watch(() => form.rubrica_id, () => {
    form.puntajes = {};
});

// Sólo cuenta los criterios de la rúbrica seleccionada: si quedara alguna
// clave de otra rúbrica en form.puntajes, no debe inflar el progreso.
const criteriosEvaluados = computed(() => {
    if (!rubricaSeleccionada.value?.criterios) return 0;

    return rubricaSeleccionada.value.criterios.filter((criterio) => {
        const puntaje = form.puntajes[criterio.id];
        return puntaje !== null && puntaje !== undefined && puntaje !== '';
    }).length;
});

const todosLosCriteriosEvaluados = computed(() => {
    if (!rubricaSeleccionada.value?.criterios?.length) return false;
    return criteriosEvaluados.value === rubricaSeleccionada.value.criterios.length;
});

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

const calcularNotaFinal = () => {
    let notaFinal = 0;

    if (!rubricaSeleccionada.value?.criterios) return 0;

    rubricaSeleccionada.value.criterios.forEach((criterio) => {
        const puntaje = form.puntajes[criterio.id];
        if (puntaje) {
            const puntuacionNormalizada = (puntaje / criterio.puntaje_maximo) * 100;
            notaFinal += puntuacionNormalizada * (criterio.peso / 100);
        }
    });

    return notaFinal;
};

const submit = () => {
    if (confirm('¿Estás seguro de guardar esta evaluación?')) {
        form.post(route('proyectos.evaluar', props.participante.id));
    }
};

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
</script>
