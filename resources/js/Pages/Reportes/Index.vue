<template>
    <AppLayout title="Reportes" :auth="auth">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Generación de Reportes
                </h2>
                <Link
                    :href="route('dashboard')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Volver al Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Panel de filtros -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Configuración del Reporte</h3>
                        <form @submit.prevent="generarReporte" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Tipo de Reporte -->
                                <div>
                                    <label for="tipo_reporte" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipo de Reporte
                                    </label>
                                    <select
                                        id="tipo_reporte"
                                        v-model="filtros.tipo_reporte"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Seleccionar tipo</option>
                                        <option value="semilleros">Semilleros</option>
                                        <option value="proyectos">Proyectos</option>
                                        <option value="eventos">Eventos</option>
                                        <option value="inscripciones">Inscripciones</option>
                                        <option value="evaluaciones">Evaluaciones</option>
                                        <option value="participantes">Participantes</option>
                                    </select>
                                </div>

                                <!-- Formato -->
                                <div>
                                    <label for="formato" class="block text-sm font-medium text-gray-700 mb-2">
                                        Formato
                                    </label>
                                    <select
                                        id="formato"
                                        v-model="filtros.formato"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Seleccionar formato</option>
                                        <option value="pdf">PDF</option>
                                        <option value="excel">Excel</option>
                                    </select>
                                </div>

                                <!-- Evento -->
                                <div>
                                    <label for="evento_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Evento
                                    </label>
                                    <select
                                        id="evento_id"
                                        v-model="filtros.evento_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">Todos los eventos</option>
                                        <option v-for="evento in eventos" :key="evento.id" :value="evento.id">
                                            {{ evento.titulo }} - {{ new Date(evento.fecha_inicio).toLocaleDateString('es-ES') }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Semillero -->
                                <div>
                                    <label for="semillero_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Semillero
                                    </label>
                                    <select
                                        id="semillero_id"
                                        v-model="filtros.semillero_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">Todos los semilleros</option>
                                        <option v-for="semillero in semilleros" :key="semillero.id" :value="semillero.id">
                                            {{ semillero.nombre }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Estado -->
                                <div>
                                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                                        Estado
                                    </label>
                                    <select
                                        id="estado"
                                        v-model="filtros.estado"
                                        :disabled="!filtros.tipo_reporte"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    >
                                        <option value="">
                                            {{ filtros.tipo_reporte ? 'Todos los estados' : 'Seleccione primero el tipo de reporte' }}
                                        </option>
                                        <option v-for="estado in estadosDisponibles" :key="estado" :value="estado">
                                            {{ estado }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Fechas -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-2">
                                        Fecha Inicio
                                    </label>
                                    <input
                                        type="date"
                                        id="fecha_inicio"
                                        v-model="filtros.fecha_inicio"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-2">
                                        Fecha Fin
                                    </label>
                                    <input
                                        type="date"
                                        id="fecha_fin"
                                        v-model="filtros.fecha_fin"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-between items-center">
                                <button
                                    type="button"
                                    @click="limpiarFiltros"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Limpiar Filtros
                                </button>
                                <div class="flex space-x-3">
                                    <button
                                        type="button"
                                        @click="vistaPrevia"
                                        :disabled="!filtros.tipo_reporte || loading"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Vista Previa
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="!filtros.tipo_reporte || !filtros.formato || loading"
                                        class="bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                                    >
                                        <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        {{ loading ? 'Generando...' : 'Generar Reporte' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Vista previa -->
                <div v-if="vistaPreviaData" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Vista Previa del Reporte</h3>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                {{ vistaPreviaData.total }} registros encontrados
                            </span>
                        </div>

                        <div v-if="vistaPreviaData.total > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th v-for="(header, index) in getTableHeaders()" :key="index"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ header }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in vistaPreviaData.datos.slice(0, 10)" :key="index">
                                        <td v-for="(value, key) in getTableRow(item)" :key="key"
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ value }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="vistaPreviaData.total > 10" class="mt-4 text-center text-sm text-gray-500">
                                Mostrando los primeros 10 registros de {{ vistaPreviaData.total }} totales
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            No se encontraron registros con los filtros aplicados
                        </div>
                    </div>
                </div>

                <!-- Información de ayuda -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Información sobre los Reportes
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li><strong>Semilleros:</strong> Semilleros de investigación con su tutor, coordinador, número de integrantes y de proyectos.</li>
                                    <li><strong>Eventos:</strong> Eventos académicos con fechas, ubicación, cupo, participantes y proyectos inscritos.</li>
                                    <li><strong>Proyectos:</strong> Lista proyectos registrados en eventos seleccionados, agrupados por programa (línea temática).</li>
                                    <li><strong>Inscripciones:</strong> Registro de inscripciones a eventos con detalles del proyecto y tipo.</li>
                                    <li><strong>Evaluaciones:</strong> Reporte de calificaciones de proyectos por evento, agrupadas por programa con promedios.</li>
                                    <li><strong>Participantes:</strong> Lista de participantes inscritos en eventos, agrupados por programa académico.</li>
                                </ul>
                                <p class="mt-2">
                                    <strong>Filtro por Evento:</strong> Selecciona un evento específico para generar reportes detallados con agrupación por programa.
                                    Los reportes se pueden exportar en formato PDF para impresión o Excel para análisis de datos.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notificaciones Toast -->
        <div v-if="showToast" class="fixed bottom-4 right-4 z-50">
            <div
                :class="toastTipo === 'error' ? 'bg-red-600' : 'bg-green-500'"
                class="text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out"
            >
                <div class="flex items-center">
                    <svg v-if="toastTipo === 'error'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ toastMessage }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    auth: Object,
    semilleros: Array,
    eventos: Array,
    estadosPorTipo: Object,
    tipos: Array,
});

const loading = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const toastTipo = ref('success');
const vistaPreviaData = ref(null);

const filtros = reactive({
    tipo_reporte: '',
    formato: '',
    fecha_inicio: '',
    fecha_fin: '',
    semillero_id: '',
    evento_id: '',
    estado: '',
    tipo: ''
});

// Cada entidad tiene sus propios estados, así que la lista depende del tipo de reporte.
const estadosDisponibles = computed(() => props.estadosPorTipo?.[filtros.tipo_reporte] ?? []);

// Al cambiar de tipo de reporte, el estado elegido deja de ser válido.
watch(() => filtros.tipo_reporte, () => {
    filtros.estado = '';
    vistaPreviaData.value = null;
});

const generarReporte = async () => {
    loading.value = true;

    try {
        const formData = new FormData();
        Object.keys(filtros).forEach(key => {
            if (filtros[key]) {
                formData.append(key, filtros[key]);
            }
        });

        const response = await axios.post(route('reportes.exportar'), formData, {
            responseType: 'blob',
            headers: {
                'Content-Type': 'multipart/form-data',
            }
        });

        // Crear enlace de descarga
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;

        const filename = response.headers['content-disposition']
            ? response.headers['content-disposition'].split('filename=')[1].replace(/"/g, '')
            : `reporte_${filtros.tipo_reporte}_${new Date().toISOString().slice(0, 10)}.${filtros.formato === 'excel' ? 'xlsx' : 'pdf'}`;

        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        mostrarToast('Reporte generado y descargado exitosamente');
    } catch (error) {
        console.error('Error generando reporte:', error);
        mostrarToast('Error al generar el reporte', 'error');
    } finally {
        loading.value = false;
    }
};

const vistaPrevia = async () => {
    try {
        const params = {
            tipo_reporte: filtros.tipo_reporte,
            ...filtros
        };

        // Remover valores vacíos
        Object.keys(params).forEach(key => {
            if (!params[key]) {
                delete params[key];
            }
        });

        const response = await axios.get(route('reportes.preview'), { params });
        vistaPreviaData.value = response.data;
    } catch (error) {
        console.error('Error obteniendo vista previa:', error);
        mostrarToast('Error al obtener vista previa', 'error');
    }
};

const limpiarFiltros = () => {
    Object.keys(filtros).forEach(key => {
        filtros[key] = '';
    });
    vistaPreviaData.value = null;
};

const mostrarToast = (mensaje, tipo = 'success') => {
    toastMessage.value = mensaje;
    toastTipo.value = tipo;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 3000);
};

const getTableHeaders = () => {
    switch (filtros.tipo_reporte) {
        case 'semilleros':
            return ['Nombre', 'Línea Temática', 'Tutor', 'Coordinador', 'Estado', 'Integrantes', 'Proyectos'];
        case 'eventos':
            return ['Título', 'Fecha Inicio', 'Fecha Fin', 'Ubicación', 'Estado', 'Participantes', 'Proyectos Inscritos'];
        case 'proyectos':
            return ['Código', 'Título', 'Autor', 'Semillero', 'Estado', 'Fecha Creación'];
        case 'inscripciones':
            return ['Código', 'Proyecto', 'Autor', 'Tipo', 'Estado', 'Fecha Inscripción'];
        case 'evaluaciones':
            return ['Proyecto', 'Autor', 'Modalidad', 'Jurado', 'Rúbrica / Evento', 'Nota Final (0-100)', 'Fecha Evaluación'];
        case 'participantes':
            return ['Nombre', 'Email', 'Estado', 'Semillero', 'Proyectos', 'Fecha Registro'];
        default:
            return [];
    }
};

const getTableRow = (item) => {
    switch (filtros.tipo_reporte) {
        case 'semilleros':
            return {
                nombre: item.nombre,
                linea: item.linea_tematica || 'Sin programa',
                tutor: item.tutor?.name || 'Sin asignar',
                coordinador: item.coordinador?.name || 'Sin asignar',
                estado: item.estado,
                integrantes: item.miembros_aprobados_count,
                proyectos: item.proyectos_count
            };
        case 'eventos':
            return {
                titulo: item.titulo,
                inicio: item.fecha_inicio ? new Date(item.fecha_inicio).toLocaleDateString('es-ES') : 'N/A',
                fin: item.fecha_fin ? new Date(item.fecha_fin).toLocaleDateString('es-ES') : 'N/A',
                ubicacion: item.ubicacion || 'No especificada',
                estado: item.estado,
                participantes: item.participantes_count,
                inscritos: item.inscripciones_count
            };
        case 'proyectos':
            return {
                codigo: item.codigo,
                titulo: item.titulo,
                autor: item.autor?.name || 'N/A',
                semillero: item.semillero?.nombre || 'N/A',
                estado: item.estado,
                fecha: new Date(item.created_at).toLocaleDateString('es-ES')
            };
        case 'inscripciones':
            return {
                codigo: item.codigo_inscripcion,
                proyecto: item.proyecto?.titulo || 'N/A',
                autor: item.proyecto?.autor?.name || 'N/A',
                tipo: item.tipo,
                estado: item.estado,
                fecha: new Date(item.created_at).toLocaleDateString('es-ES')
            };
        case 'evaluaciones':
            // Este reporte llega ya normalizado desde el backend (combina
            // evaluaciones con rúbrica y propuestas de evento en un mismo
            // formato de texto plano), a diferencia de los demás tipos que
            // son colecciones de Eloquent con relaciones anidadas.
            return {
                proyecto: item.proyecto || 'N/A',
                autor: item.autor || 'N/A',
                modalidad: item.modalidad || '—',
                jurado: item.jurado || 'N/A',
                rubrica: item.origen || 'N/A',
                nota: item.nota_final !== null && item.nota_final !== undefined ? Number(item.nota_final).toFixed(2) : 'Pendiente',
                fecha: item.fecha ? new Date(item.fecha).toLocaleDateString('es-ES') : 'N/A'
            };
        case 'participantes':
            return {
                nombre: item.name,
                email: item.email,
                estado: item.estado,
                semillero: [...new Set(item.proyectos?.map(p => p.semillero?.nombre).filter(Boolean))].join(', ') || 'N/A',
                proyectos: item.proyectos?.map(p => p.titulo).join(', ') || 'N/A',
                fecha: new Date(item.created_at).toLocaleDateString('es-ES')
            };
        default:
            return {};
    }
};
</script>

















