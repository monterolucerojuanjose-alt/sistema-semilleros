<template>
    <AppLayout title="Reportes" :auth="auth">
        <template #header>
            <div>
                <h2 class="text-xl font-bold leading-tight text-gray-900">Generación de reportes</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Elige qué información necesitas, ajusta los filtros y descárgala en PDF para imprimir o en Excel para analizarla.
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <!-- Paso 1: tipo de reporte -->
            <section class="animar-entrada rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">1</span>
                    <h3 class="text-base font-semibold text-gray-900">Tipo de reporte</h3>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup" aria-label="Tipo de reporte">
                    <button
                        v-for="opcion in TIPOS_REPORTE"
                        :key="opcion.valor"
                        type="button"
                        role="radio"
                        :aria-checked="filtros.tipo_reporte === opcion.valor"
                        @click="filtros.tipo_reporte = opcion.valor"
                        :class="[
                            'group flex items-start gap-3 rounded-xl border p-4 text-left transition-all duration-200',
                            filtros.tipo_reporte === opcion.valor
                                ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/20'
                                : 'border-gray-200 bg-white hover:border-indigo-300 hover:bg-gray-50',
                        ]"
                    >
                        <span
                            :class="[
                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg transition-colors',
                                filtros.tipo_reporte === opcion.valor ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 group-hover:bg-indigo-100 group-hover:text-indigo-700',
                            ]"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="opcion.icono" />
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-900">{{ opcion.nombre }}</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-gray-500">{{ opcion.descripcion }}</span>
                        </span>
                    </button>
                </div>
            </section>

            <!-- Paso 2: filtros -->
            <section
                v-if="filtros.tipo_reporte"
                class="animar-entrada rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">2</span>
                        <h3 class="text-base font-semibold text-gray-900">Filtros <span class="font-normal text-gray-400">(opcionales)</span></h3>
                    </div>
                    <button
                        type="button"
                        @click="limpiarFiltros"
                        :disabled="!hayFiltros"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Limpiar filtros
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-if="aplica('evento_id')">
                        <label for="evento_id" class="mb-1.5 block text-sm font-medium text-gray-700">Evento</label>
                        <select id="evento_id" v-model="filtros.evento_id" class="campo">
                            <option value="">Todos los eventos</option>
                            <option v-for="evento in eventos" :key="evento.id" :value="evento.id">
                                {{ evento.titulo }} ({{ fechaCorta(evento.fecha_inicio) }})
                            </option>
                        </select>
                    </div>

                    <div v-if="aplica('semillero_id')">
                        <label for="semillero_id" class="mb-1.5 block text-sm font-medium text-gray-700">Semillero</label>
                        <select id="semillero_id" v-model="filtros.semillero_id" class="campo">
                            <option value="">Todos los semilleros</option>
                            <option v-for="semillero in semilleros" :key="semillero.id" :value="semillero.id">
                                {{ semillero.nombre }}
                            </option>
                        </select>
                    </div>

                    <div v-if="aplica('estado')">
                        <label for="estado" class="mb-1.5 block text-sm font-medium text-gray-700">Estado</label>
                        <select id="estado" v-model="filtros.estado" class="campo">
                            <option value="">Todos los estados</option>
                            <option v-for="estado in estadosDisponibles" :key="estado" :value="estado">
                                {{ estadoLegible(estado) }}
                            </option>
                        </select>
                    </div>

                    <div v-if="aplica('tipo')">
                        <label for="tipo" class="mb-1.5 block text-sm font-medium text-gray-700">Tipo de presentación</label>
                        <select id="tipo" v-model="filtros.tipo" class="campo">
                            <option value="">Ponencia y póster</option>
                            <option v-for="tipo in tipos" :key="tipo" :value="tipo">{{ tipoLegible(tipo) }}</option>
                        </select>
                    </div>

                    <div v-if="aplica('fecha_inicio')">
                        <label for="fecha_inicio" class="mb-1.5 block text-sm font-medium text-gray-700">Desde</label>
                        <input id="fecha_inicio" v-model="filtros.fecha_inicio" type="date" :max="filtros.fecha_fin || undefined" class="campo" />
                    </div>

                    <div v-if="aplica('fecha_fin')">
                        <label for="fecha_fin" class="mb-1.5 block text-sm font-medium text-gray-700">Hasta</label>
                        <input id="fecha_fin" v-model="filtros.fecha_fin" type="date" :min="filtros.fecha_inicio || undefined" class="campo" />
                    </div>
                </div>

                <p v-if="aplica('fecha_inicio')" class="mt-3 text-xs text-gray-500">
                    El rango de fechas se aplica sobre la {{ fechaPorTipo?.[filtros.tipo_reporte] }}.
                </p>
                <p v-if="filtros.evento_id && conAgrupacion" class="mt-1 text-xs text-gray-500">
                    Al elegir un evento, el PDF incluye además un resumen por línea temática.
                </p>
            </section>

            <!-- Paso 3: acciones -->
            <section
                v-if="filtros.tipo_reporte"
                class="animar-entrada rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">3</span>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Revisa y descarga</h3>
                            <p class="text-xs text-gray-500">La vista previa, el PDF y el Excel contienen exactamente los mismos registros.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            @click="vistaPrevia"
                            :disabled="cargando !== null"
                            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg v-if="cargando === 'vista'" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" /></svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Vista previa
                        </button>

                        <button
                            type="button"
                            @click="descargar('pdf')"
                            :disabled="cargando !== null"
                            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg v-if="cargando === 'pdf'" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" /></svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ cargando === 'pdf' ? 'Generando PDF…' : 'Descargar PDF' }}
                        </button>

                        <button
                            type="button"
                            @click="descargar('excel')"
                            :disabled="cargando !== null"
                            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg v-if="cargando === 'excel'" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" /></svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            {{ cargando === 'excel' ? 'Generando Excel…' : 'Descargar Excel' }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Vista previa -->
            <section
                v-if="vistaPreviaData"
                class="animar-entrada overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-4 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Vista previa: {{ tipoActual?.nombre }}</h3>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        {{ vistaPreviaData.total }} {{ vistaPreviaData.total === 1 ? 'registro' : 'registros' }}
                    </span>
                </div>

                <div v-if="vistaPreviaData.total > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">N.º</th>
                                <th
                                    v-for="columna in columnas"
                                    :key="columna"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    {{ columna }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr
                                v-for="(item, indice) in vistaPreviaData.datos.slice(0, LIMITE_VISTA)"
                                :key="indice"
                                class="transition-colors hover:bg-gray-50"
                            >
                                <td class="px-4 py-3 text-center text-xs text-gray-400">{{ indice + 1 }}</td>
                                <td
                                    v-for="(celda, clave) in fila(item)"
                                    :key="clave"
                                    :class="['px-4 py-3 align-top text-sm text-gray-700', anchoColumna(clave)]"
                                >
                                    <span
                                        v-if="clave === 'estado'"
                                        :class="['inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold', claseEstado(celda)]"
                                    >
                                        {{ celda }}
                                    </span>
                                    <span v-else-if="clave === 'nota'" :class="['font-semibold tabular-nums', claseNota(celda)]">{{ celda }}</span>
                                    <span v-else-if="clave === 'principal'" class="font-medium text-gray-900">{{ celda }}</span>
                                    <span v-else :class="{ 'text-gray-400': esVacio(celda) }">{{ celda }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="vistaPreviaData.total > LIMITE_VISTA" class="border-t border-gray-100 px-6 py-3 text-center text-sm text-gray-500">
                        Se muestran los primeros {{ LIMITE_VISTA }} de {{ vistaPreviaData.total }} registros. El archivo descargado los incluye todos.
                    </p>
                </div>

                <div v-else class="px-6 py-12 text-center">
                    <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-3 text-sm font-medium text-gray-700">No hay registros con estos filtros</p>
                    <p class="mt-1 text-sm text-gray-500">Prueba a quitar algún filtro o ampliar el rango de fechas.</p>
                </div>
            </section>
        </div>

        <!-- Aviso -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="aviso"
                role="status"
                :class="[
                    'fixed bottom-4 left-4 right-4 z-50 flex items-center gap-2 rounded-lg px-5 py-3 text-sm font-medium text-white shadow-lg sm:left-auto',
                    aviso.tipo === 'error' ? 'bg-red-600' : 'bg-emerald-600',
                ]"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path v-if="aviso.tipo === 'error'" stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ aviso.mensaje }}
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, reactive, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    auth: Object,
    semilleros: Array,
    eventos: Array,
    estadosPorTipo: Object,
    tipos: Array,
    // Qué filtros respeta cada tipo; viene del servidor para no repetir la
    // lista aquí y que ambos lados no se desincronicen.
    filtrosPorTipo: Object,
    fechaPorTipo: Object,
});

const LIMITE_VISTA = 10;

const TIPOS_REPORTE = [
    {
        valor: 'semilleros',
        nombre: 'Semilleros',
        descripcion: 'Tutor, coordinador, integrantes y proyectos de cada semillero.',
        icono: 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
    },
    {
        valor: 'proyectos',
        nombre: 'Proyectos',
        descripcion: 'Proyectos registrados con su autor, semillero y estado.',
        icono: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
    },
    {
        valor: 'eventos',
        nombre: 'Eventos',
        descripcion: 'Eventos académicos con fechas, lugar, cupo y participación.',
        icono: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        valor: 'inscripciones',
        nombre: 'Inscripciones',
        descripcion: 'Proyectos inscritos en eventos como ponencia o póster.',
        icono: 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
    },
    {
        valor: 'evaluaciones',
        nombre: 'Evaluaciones',
        descripcion: 'Calificaciones de los jurados (0 a 100) con promedios.',
        icono: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    },
    {
        valor: 'participantes',
        nombre: 'Participantes',
        descripcion: 'Estudiantes con sus semilleros y proyectos.',
        icono: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
];

// Los tipos que, al filtrar por evento, agregan el resumen por línea temática.
const TIPOS_CON_AGRUPACION = ['proyectos', 'inscripciones', 'evaluaciones', 'participantes'];

const filtros = reactive({
    tipo_reporte: '',
    fecha_inicio: '',
    fecha_fin: '',
    semillero_id: '',
    evento_id: '',
    estado: '',
    tipo: '',
});

const cargando = ref(null); // null | 'vista' | 'pdf' | 'excel'
const vistaPreviaData = ref(null);
const aviso = ref(null);

const tipoActual = computed(() => TIPOS_REPORTE.find((t) => t.valor === filtros.tipo_reporte));
const estadosDisponibles = computed(() => props.estadosPorTipo?.[filtros.tipo_reporte] ?? []);
const conAgrupacion = computed(() => TIPOS_CON_AGRUPACION.includes(filtros.tipo_reporte));
const aplica = (campo) => (props.filtrosPorTipo?.[filtros.tipo_reporte] ?? []).includes(campo);

const hayFiltros = computed(() =>
    ['fecha_inicio', 'fecha_fin', 'semillero_id', 'evento_id', 'estado', 'tipo'].some((c) => filtros[c] && aplica(c))
);

// Al cambiar de tipo, los filtros que el nuevo tipo no admite se limpian y el
// estado elegido deja de ser válido (cada entidad tiene sus propios estados).
watch(() => filtros.tipo_reporte, () => {
    filtros.estado = '';
    ['semillero_id', 'evento_id', 'tipo', 'fecha_inicio', 'fecha_fin'].forEach((campo) => {
        if (!aplica(campo)) filtros[campo] = '';
    });
    vistaPreviaData.value = null;
});

// Cualquier cambio de filtro deja desactualizada la vista previa mostrada.
watch(
    () => [filtros.fecha_inicio, filtros.fecha_fin, filtros.semillero_id, filtros.evento_id, filtros.estado, filtros.tipo],
    () => { vistaPreviaData.value = null; }
);

/** Sólo los filtros con valor que aplican al tipo elegido. */
const parametros = () => {
    const datos = { tipo_reporte: filtros.tipo_reporte };
    Object.keys(filtros).forEach((clave) => {
        if (clave !== 'tipo_reporte' && filtros[clave] && aplica(clave)) datos[clave] = filtros[clave];
    });
    return datos;
};

const nombreDelArchivo = (cabecera, formato) => {
    const extension = formato === 'excel' ? 'xlsx' : 'pdf';
    const respaldo = `reporte_${filtros.tipo_reporte}_${new Date().toISOString().slice(0, 10)}.${extension}`;
    if (!cabecera) return respaldo;
    const utf8 = cabecera.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8) return decodeURIComponent(utf8[1]);
    const simple = cabecera.match(/filename="?([^";]+)"?/i);
    return simple ? simple[1] : respaldo;
};

const descargar = async (formato) => {
    cargando.value = formato;
    try {
        const respuesta = await axios.post(route('reportes.exportar'), { ...parametros(), formato }, { responseType: 'blob' });

        const url = window.URL.createObjectURL(new Blob([respuesta.data]));
        const enlace = document.createElement('a');
        enlace.href = url;
        enlace.download = nombreDelArchivo(respuesta.headers['content-disposition'], formato);
        document.body.appendChild(enlace);
        enlace.click();
        enlace.remove();
        window.URL.revokeObjectURL(url);

        mostrarAviso(`Reporte de ${tipoActual.value.nombre.toLowerCase()} descargado en ${formato === 'excel' ? 'Excel' : 'PDF'}.`);
    } catch (error) {
        console.error('Error al generar el reporte:', error);
        mostrarAviso('No se pudo generar el reporte. Intenta de nuevo.', 'error');
    } finally {
        cargando.value = null;
    }
};

const vistaPrevia = async () => {
    cargando.value = 'vista';
    try {
        const { data } = await axios.get(route('reportes.preview'), { params: parametros() });
        vistaPreviaData.value = data;
    } catch (error) {
        console.error('Error al obtener la vista previa:', error);
        mostrarAviso('No se pudo cargar la vista previa.', 'error');
    } finally {
        cargando.value = null;
    }
};

const limpiarFiltros = () => {
    ['fecha_inicio', 'fecha_fin', 'semillero_id', 'evento_id', 'estado', 'tipo'].forEach((c) => { filtros[c] = ''; });
};

let temporizadorAviso = null;
const mostrarAviso = (mensaje, tipo = 'exito') => {
    aviso.value = { mensaje, tipo };
    clearTimeout(temporizadorAviso);
    temporizadorAviso = setTimeout(() => { aviso.value = null; }, 3500);
};

/* ---------- Textos de presentación (mismos que en el PDF y el Excel) ---------- */

const ESTADOS = {
    activo: 'Activo', inactivo: 'Inactivo', finalizado: 'Finalizado', evaluado: 'Evaluado',
    pendiente: 'Pendiente', aprobado: 'Aprobado', rechazado: 'Rechazado', inscrito: 'Inscrito',
    aceptado: 'Aceptado', 'en revisión': 'En revisión',
};
const estadoLegible = (valor) => {
    if (!valor) return 'Sin estado';
    return ESTADOS[String(valor).toLowerCase()] ?? String(valor).charAt(0).toUpperCase() + String(valor).slice(1);
};
const tipoLegible = (valor) => ({ ponencia: 'Ponencia', poster: 'Póster' }[valor] ?? valor ?? 'Sin tipo');

const COLORES_ESTADO = {
    Activo: 'bg-emerald-100 text-emerald-800', Aprobado: 'bg-emerald-100 text-emerald-800',
    Aceptado: 'bg-emerald-100 text-emerald-800', Evaluado: 'bg-emerald-100 text-emerald-800',
    Rechazado: 'bg-red-100 text-red-800',
    'En revisión': 'bg-amber-100 text-amber-800', Pendiente: 'bg-amber-100 text-amber-800',
    Inscrito: 'bg-blue-100 text-blue-800', Finalizado: 'bg-indigo-100 text-indigo-800',
};
const claseEstado = (etiqueta) => COLORES_ESTADO[etiqueta] ?? 'bg-gray-100 text-gray-700';

const claseNota = (texto) => {
    const nota = parseFloat(String(texto).replace(',', '.'));
    if (Number.isNaN(nota)) return 'text-gray-400';
    if (nota >= 80) return 'text-emerald-700';
    if (nota >= 60) return 'text-amber-700';
    return 'text-red-700';
};

// Las columnas cortas (fechas, cifras, estados) van en una sola línea y las de
// texto tienen un ancho mínimo; si no caben, la tabla se desplaza de lado en
// lugar de partir cada palabra en varios renglones.
const COLUMNAS_CORTAS = ['codigo', 'fecha', 'inicio', 'fin', 'nota', 'estado', 'tipo', 'integrantes', 'proyectos_count', 'participantes', 'inscritos'];
const anchoColumna = (clave) => {
    if (clave === 'principal') return 'min-w-[14rem] max-w-xs';
    if (COLUMNAS_CORTAS.includes(clave) || (clave === 'proyectos' && filtros.tipo_reporte === 'semilleros')) return 'whitespace-nowrap';
    return 'min-w-[9rem]';
};

const VACIOS = ['Sin asignar', 'Sin semillero', 'Sin proyectos', 'No aplica', 'Sin registrar', 'No especificada', 'Sin línea temática', '—'];
const esVacio = (valor) => VACIOS.includes(valor);

const fechaCorta = (fecha) => (fecha ? new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '—');
const nota = (valor) => (valor === null || valor === undefined ? 'Sin nota' : Number(valor).toFixed(2).replace('.', ','));

const COLUMNAS = {
    semilleros: ['Semillero', 'Línea temática', 'Tutor', 'Coordinador', 'Estado', 'Integrantes', 'Proyectos'],
    eventos: ['Evento', 'Inicio', 'Fin', 'Ubicación', 'Estado', 'Participantes', 'Proyectos inscritos'],
    proyectos: ['Código', 'Título', 'Autor', 'Semillero', 'Estado', 'Creación'],
    inscripciones: ['Código', 'Proyecto', 'Autor', 'Evento', 'Tipo', 'Estado', 'Inscripción'],
    evaluaciones: ['Proyecto', 'Autor', 'Modalidad', 'Jurado', 'Rúbrica / evento', 'Nota (0-100)', 'Fecha'],
    participantes: ['Nombre', 'Correo electrónico', 'Estado', 'Semilleros', 'Proyectos', 'Registro'],
};
const columnas = computed(() => COLUMNAS[filtros.tipo_reporte] ?? []);

/**
 * Celdas de una fila de la vista previa. Las claves especiales «principal»,
 * «estado» y «nota» reciben formato propio en la tabla.
 *
 * Ojo: «evaluaciones» llega ya normalizado desde el servidor (campos de
 * texto planos), a diferencia de los demás tipos, que son modelos con sus
 * relaciones anidadas.
 */
const fila = (item) => {
    switch (filtros.tipo_reporte) {
        case 'semilleros':
            return {
                principal: item.nombre,
                linea: item.linea_tematica || 'Sin línea temática',
                tutor: item.tutor?.name || 'Sin asignar',
                coordinador: item.coordinador?.name || 'Sin asignar',
                estado: estadoLegible(item.estado),
                integrantes: item.miembros_aprobados_count ?? 0,
                proyectos: item.proyectos_count ?? 0,
            };
        case 'eventos':
            return {
                principal: item.titulo,
                inicio: fechaCorta(item.fecha_inicio),
                fin: fechaCorta(item.fecha_fin),
                ubicacion: item.ubicacion || 'No especificada',
                estado: estadoLegible(item.estado),
                participantes: item.participantes_count ?? 0,
                inscritos: item.inscripciones_count ?? 0,
            };
        case 'proyectos':
            return {
                codigo: item.codigo || '—',
                principal: item.titulo,
                autor: item.autor?.name || 'Sin autor',
                semillero: item.semillero?.nombre || 'Sin semillero',
                estado: estadoLegible(item.estado),
                fecha: fechaCorta(item.created_at),
            };
        case 'inscripciones':
            return {
                codigo: item.codigo_inscripcion,
                principal: item.proyecto?.titulo || 'Proyecto eliminado',
                autor: item.proyecto?.autor?.name || 'Sin autor',
                evento: item.evento?.titulo || 'Sin evento',
                tipo: tipoLegible(item.tipo),
                estado: estadoLegible(item.estado),
                fecha: fechaCorta(item.created_at),
            };
        case 'evaluaciones':
            return {
                principal: item.proyecto,
                autor: item.autor,
                modalidad: item.modalidad || '—',
                jurado: item.jurado,
                rubrica: item.origen,
                nota: nota(item.nota_final),
                fecha: fechaCorta(item.fecha),
            };
        case 'participantes': {
            const proyectos = item.proyectos ?? [];
            return {
                principal: item.name,
                email: item.email,
                estado: estadoLegible(item.estado ?? 'activo'),
                semilleros: [...new Set(proyectos.map((p) => p.semillero?.nombre).filter(Boolean))].join(', ') || 'Sin semillero',
                proyectos: proyectos.map((p) => p.titulo).filter(Boolean).join(', ') || 'Sin proyectos',
                fecha: fechaCorta(item.created_at),
            };
        }
        default:
            return {};
    }
};
</script>

<style scoped>
.campo {
    @apply block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
}
</style>
