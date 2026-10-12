<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import NumeroAnimado from '@/Components/NumeroAnimado.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    auth: Object,
    stats: { type: Object, default: () => ({}) },
    pendientesJurado: { type: Array, default: null },
    proximosEventos: { type: Array, default: () => [] },
    actividadReciente: { type: Array, default: () => [] },
    ultimasNotificaciones: { type: Array, default: () => [] },
});

const userRole = computed(() => props.auth?.user?.roles?.[0]?.name ?? props.auth?.user?.role ?? 'Estudiante');
const esGestor = computed(() => ['Administrador', 'Coordinador'].includes(userRole.value));
const nombreCorto = computed(() => (props.auth?.user?.name ?? 'Usuario').split(' ')[0]);

/* ------------------------------------------------------------------ Saludo */

const saludo = computed(() => {
    const hora = new Date().getHours();
    if (hora < 12) return 'Buenos días';
    if (hora < 19) return 'Buenas tardes';
    return 'Buenas noches';
});

const fechaHoy = computed(() =>
    new Date().toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
);

const descripcionRol = {
    'Administrador': 'Tienes el control completo del sistema: usuarios, permisos, reportes y auditoría.',
    'Coordinador': 'Coordinas semilleros y eventos, apruebas propuestas y asignas jurados.',
    'Docente/Tutor': 'Acompañas los semilleros a tu cargo y el avance de tus estudiantes.',
    'Jurado': 'Calificas los proyectos que te asignan usando las rúbricas institucionales.',
    'Estudiante': 'Aquí gestionas tus semilleros, proyectos, eventos y calificaciones.',
};

/* -------------------------------------------------------------- Indicadores */

const paletas = {
    azul: { fondo: 'bg-blue-50', texto: 'text-blue-600', barra: 'from-blue-500 to-indigo-500' },
    verde: { fondo: 'bg-emerald-50', texto: 'text-emerald-600', barra: 'from-emerald-500 to-teal-500' },
    morado: { fondo: 'bg-violet-50', texto: 'text-violet-600', barra: 'from-violet-500 to-purple-500' },
    naranja: { fondo: 'bg-amber-50', texto: 'text-amber-600', barra: 'from-amber-500 to-orange-500' },
};

// Trazados de los iconos, para no repetir el SVG completo en cada tarjeta.
const iconos = {
    usuarios: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    proyectos: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
    semilleros: 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
    certificados: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    reloj: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    verificado: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    estrella: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
    campana: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.158c0 .538-.214 1.055-.595 1.437L4 17h5m6 0v1a3 3 0 11-6 0v-1h6z',
};

const indicadores = computed(() => {
    const s = props.stats ?? {};

    const porRol = {
        'Administrador': [
            { titulo: 'Usuarios', valor: s.totalUsuarios, icono: 'usuarios', color: 'azul', enlace: '/users', pie: 'registrados en el sistema' },
            { titulo: 'Proyectos', valor: s.totalProyectos, icono: 'proyectos', color: 'verde', enlace: '/proyectos', pie: `${s.proyectosAprobados ?? 0} aprobados` },
            { titulo: 'Semilleros', valor: s.totalSemilleros, icono: 'semilleros', color: 'morado', enlace: '/semilleros', pie: `${s.semillerosActivos ?? 0} activos` },
            { titulo: 'Certificados', valor: s.totalCertificados, icono: 'certificados', color: 'naranja', enlace: '/certificados', pie: 'emitidos' },
        ],
        'Coordinador': [
            { titulo: 'Semilleros', valor: s.totalSemilleros, icono: 'semilleros', color: 'azul', enlace: '/semilleros', pie: `${s.semillerosActivos ?? 0} activos` },
            { titulo: 'Proyectos', valor: s.totalProyectos, icono: 'proyectos', color: 'verde', enlace: '/proyectos', pie: `${s.proyectosAprobados ?? 0} aprobados` },
            { titulo: 'Evaluaciones', valor: s.totalEvaluaciones, icono: 'estrella', color: 'morado', enlace: '/resultados-evaluacion', pie: 'registradas' },
            { titulo: 'Certificados', valor: s.totalCertificados, icono: 'certificados', color: 'naranja', enlace: '/certificados', pie: 'emitidos' },
        ],
        'Docente/Tutor': [
            { titulo: 'Mis semilleros', valor: s.misSemilleros, icono: 'semilleros', color: 'azul', enlace: '/mis-semilleros', pie: `${s.semillerosActivos ?? 0} activos` },
            { titulo: 'Estudiantes', valor: s.estudiantesAsignados, icono: 'usuarios', color: 'verde', enlace: '/mis-semilleros', pie: 'a tu cargo' },
            { titulo: 'Proyectos', valor: s.misProyectos, icono: 'proyectos', color: 'morado', enlace: '/proyectos', pie: 'en tus semilleros' },
            { titulo: 'Solicitudes', valor: s.solicitudesPendientes, icono: 'campana', color: 'naranja', enlace: '/solicitudes', pie: 'por revisar' },
        ],
        'Jurado': [
            { titulo: 'Por evaluar', valor: s.evaluacionesPendientes, icono: 'reloj', color: 'naranja', enlace: '/mis-proyectos-evaluar', pie: 'esperan tu nota' },
            { titulo: 'Evaluados', valor: s.proyectosEvaluados, icono: 'verificado', color: 'verde', enlace: '/evaluaciones', pie: 'ya calificados' },
            { titulo: 'Nota promedio', valor: s.calificacionPromedio, icono: 'estrella', color: 'morado', decimales: 1, pie: 'sobre 100' },
            { titulo: 'Asignados', valor: s.totalAsignados, icono: 'proyectos', color: 'azul', enlace: '/mis-proyectos-evaluar', pie: 'en total' },
        ],
        'Estudiante': [
            { titulo: 'Mis semilleros', valor: s.inscripcionesActivas, icono: 'semilleros', color: 'azul', enlace: '/semilleros', pie: 'con ingreso aprobado' },
            { titulo: 'Mis proyectos', valor: s.misProyectos, icono: 'proyectos', color: 'verde', enlace: '/proyectos', pie: 'postulados' },
            { titulo: 'Solicitudes', valor: s.solicitudesPendientes, icono: 'reloj', color: 'naranja', enlace: '/semilleros', pie: 'en espera' },
            { titulo: 'Certificados', valor: s.misCertificados, icono: 'certificados', color: 'morado', enlace: '/mis-certificados', pie: 'obtenidos' },
        ],
    };

    return porRol[userRole.value] ?? porRol['Estudiante'];
});

/* ----------------------------------------------------------- Accesos rápidos */

const accesosRapidos = computed(() => {
    const porRol = {
        'Administrador': [
            { texto: 'Gestionar usuarios', enlace: '/users', icono: 'usuarios' },
            { texto: 'Ver reportes', enlace: '/reportes', icono: 'certificados' },
            { texto: 'Auditoría', enlace: '/auditoria', icono: 'verificado' },
            { texto: 'Notificaciones', enlace: '/notificaciones', icono: 'campana' },
        ],
        'Coordinador': [
            { texto: 'Nuevo semillero', enlace: '/semilleros/create', icono: 'semilleros' },
            { texto: 'Nuevo evento', enlace: '/eventos/create', icono: 'certificados' },
            { texto: 'Propuestas pendientes', enlace: '/propuestas/pendientes', icono: 'reloj' },
            { texto: 'Ver reportes', enlace: '/reportes', icono: 'estrella' },
        ],
        'Docente/Tutor': [
            { texto: 'Mis semilleros', enlace: '/mis-semilleros', icono: 'semilleros' },
            { texto: 'Solicitudes', enlace: '/solicitudes', icono: 'campana' },
            { texto: 'Proyectos', enlace: '/proyectos', icono: 'proyectos' },
            { texto: 'Resultados', enlace: '/resultados-evaluacion', icono: 'estrella' },
        ],
        'Jurado': [
            { texto: 'Evaluar proyectos', enlace: '/mis-proyectos-evaluar', icono: 'reloj' },
            { texto: 'Mis evaluaciones', enlace: '/evaluaciones', icono: 'verificado' },
            { texto: 'Rúbricas', enlace: '/rubricas', icono: 'certificados' },
            { texto: 'Notificaciones', enlace: '/notificaciones', icono: 'campana' },
        ],
        'Estudiante': [
            { texto: 'Explorar semilleros', enlace: '/semilleros', icono: 'semilleros' },
            { texto: 'Nuevo proyecto', enlace: '/nuevo-proyecto', icono: 'proyectos' },
            { texto: 'Eventos', enlace: '/eventos', icono: 'certificados' },
            { texto: 'Mis notas', enlace: '/mis-notas', icono: 'estrella' },
        ],
    };

    return porRol[userRole.value] ?? porRol['Estudiante'];
});

/* --------------------------------------------------------------- Utilidades */

const estadoProyecto = {
    'Aprobado': 'bg-emerald-100 text-emerald-700',
    'Rechazado': 'bg-red-100 text-red-700',
    'En revisión': 'bg-amber-100 text-amber-700',
    'Inscrito': 'bg-blue-100 text-blue-700',
};

const formatFecha = (fecha) => {
    if (!fecha) return '';
    return new Date(fecha).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
};

const haceCuanto = (fecha) => {
    if (!fecha) return '';
    const minutos = Math.floor((Date.now() - new Date(fecha)) / 60000);
    if (minutos < 1) return 'hace un momento';
    if (minutos < 60) return `hace ${minutos} min`;
    const horas = Math.floor(minutos / 60);
    if (horas < 24) return `hace ${horas} h`;
    const dias = Math.floor(horas / 24);
    if (dias < 30) return `hace ${dias} d`;
    return formatFecha(fecha);
};

const cuentaRegresiva = (dias) => {
    if (dias === 0) return 'Hoy';
    if (dias === 1) return 'Mañana';
    return `En ${dias} días`;
};

/* -------------------------------------------------- Gráficas (gestores) */

const colores = {
    azul: '#4f46e5', verde: '#10b981', ambar: '#f59e0b',
    rojo: '#ef4444', cian: '#06b6d4', violeta: '#8b5cf6', naranja: '#f97316',
};

const graficas = reactive({
    proyectosPorEstado: { labels: [], datasets: [] },
    participacionPorRol: { labels: [], datasets: [] },
    evaluacionesPorRubrica: { labels: [], datasets: [] },
    estadisticasMensuales: { labels: [], datasets: [] },
    cargando: true,
});

const cargarGraficas = async () => {
    if (!esGestor.value) return;

    try {
        const [estado, participacion, rubrica, mensuales] = await Promise.all([
            axios.get(route('dashboard.proyectos-por-estado')),
            axios.get(route('dashboard.participacion-por-rol')),
            axios.get(route('dashboard.evaluaciones-por-rubrica')),
            axios.get(route('dashboard.estadisticas-mensuales')),
        ]);

        graficas.proyectosPorEstado = {
            labels: Object.keys(estado.data),
            datasets: [{
                label: 'Proyectos',
                data: Object.values(estado.data),
                backgroundColor: [colores.ambar, colores.verde, colores.rojo, colores.azul],
                borderRadius: 6,
                borderWidth: 0,
            }],
        };

        graficas.participacionPorRol = {
            labels: participacion.data.map((i) => i.rol),
            datasets: [{
                data: participacion.data.map((i) => i.cantidad),
                backgroundColor: [colores.azul, colores.verde, colores.ambar, colores.violeta, colores.naranja, colores.cian],
                borderWidth: 2,
                borderColor: '#fff',
            }],
        };

        graficas.evaluacionesPorRubrica = {
            labels: rubrica.data.map((i) => i.rubrica),
            datasets: [{
                label: 'Promedio',
                data: rubrica.data.map((i) => i.promedio),
                backgroundColor: colores.violeta,
                borderRadius: 6,
                borderWidth: 0,
            }],
        };

        graficas.estadisticasMensuales = {
            labels: mensuales.data.map((i) => i.nombre),
            datasets: [
                { label: 'Proyectos', data: mensuales.data.map((i) => i.proyectos), borderColor: colores.azul, backgroundColor: 'rgba(79,70,229,0.1)', tension: 0.4, fill: true },
                { label: 'Evaluaciones', data: mensuales.data.map((i) => i.evaluaciones), borderColor: colores.verde, backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.4, fill: true },
            ],
        };
    } catch (error) {
        // Si una estadística falla, el resto del panel debe seguir usable.
        console.error('No se pudieron cargar las gráficas del panel', error);
    } finally {
        graficas.cargando = false;
    }
};

onMounted(cargarGraficas);
</script>

<template>
    <AppLayout :auth="auth" title="Inicio">
        <Head title="Inicio" />

        <div class="space-y-6">
            <!-- Saludo -->
            <section class="animar-entrada relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-blue-600 to-violet-600 px-6 py-8 text-white shadow-lg sm:px-8">
                <!-- Círculos decorativos del fondo -->
                <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10"></div>
                <div class="pointer-events-none absolute -bottom-24 right-24 h-48 w-48 rounded-full bg-white/5"></div>

                <div class="relative flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <!-- first-letter y no capitalize: «capitalize» pondría en
                             mayúscula cada palabra («9 De Septiembre De 2026»). -->
                        <p class="text-sm font-medium text-indigo-100 first-letter:uppercase">{{ fechaHoy }}</p>
                        <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">
                            {{ saludo }}, {{ nombreCorto }}
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm text-indigo-100">
                            {{ descripcionRol[userRole] ?? descripcionRol['Estudiante'] }}
                        </p>
                        <span class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300 animar-latido"></span>
                            {{ userRole }}
                        </span>
                    </div>

                    <Link
                        v-if="esGestor"
                        href="/reportes"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-5 py-2.5 text-sm font-semibold backdrop-blur transition hover:bg-white/25"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Ver reportes
                    </Link>
                </div>
            </section>

            <!-- Indicadores -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <component
                    :is="indicador.enlace ? Link : 'div'"
                    v-for="(indicador, i) in indicadores"
                    :key="indicador.titulo"
                    :href="indicador.enlace"
                    :class="[
                        'tarjeta-interactiva animar-entrada group block rounded-2xl border border-gray-200 bg-white p-5 shadow-sm',
                        `retraso-${i + 1}`,
                    ]"
                >
                    <div class="flex items-start justify-between">
                        <span :class="['inline-flex h-11 w-11 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-110', paletas[indicador.color].fondo]">
                            <svg :class="['h-5 w-5', paletas[indicador.color].texto]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconos[indicador.icono]" />
                            </svg>
                        </span>
                        <svg v-if="indicador.enlace" class="h-4 w-4 text-gray-300 transition-all duration-300 group-hover:translate-x-0.5 group-hover:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>

                    <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900">
                        <NumeroAnimado :valor="indicador.valor ?? 0" :decimales="indicador.decimales ?? 0" />
                    </p>
                    <p class="mt-0.5 text-sm font-medium text-gray-700">{{ indicador.titulo }}</p>
                    <p class="text-xs text-gray-400">{{ indicador.pie }}</p>

                    <div class="mt-3 h-1 w-full overflow-hidden rounded-full bg-gray-100">
                        <div :class="['h-full w-0 rounded-full bg-gradient-to-r transition-all duration-700 group-hover:w-full', paletas[indicador.color].barra]"></div>
                    </div>
                </component>
            </section>

            <!-- Accesos rápidos -->
            <section class="animar-entrada retraso-4">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Accesos rápidos</h2>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <Link
                        v-for="acceso in accesosRapidos"
                        :key="acceso.enlace"
                        :href="acceso.enlace"
                        class="tarjeta-interactiva group flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3.5 shadow-sm"
                    >
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 transition-colors duration-300 group-hover:bg-indigo-600">
                            <svg class="h-4.5 w-4.5 text-indigo-600 transition-colors duration-300 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="width:18px;height:18px">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconos[acceso.icono]" />
                            </svg>
                        </span>
                        <span class="truncate text-sm font-medium text-gray-700 group-hover:text-gray-900">{{ acceso.texto }}</span>
                    </Link>
                </div>
            </section>

            <!-- Proyectos que el jurado tiene por calificar -->
            <section v-if="pendientesJurado" class="animar-entrada retraso-5 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
                    <div>
                        <h2 class="font-semibold text-gray-900">Proyectos por evaluar</h2>
                        <p class="text-sm text-gray-500">
                            {{ pendientesJurado.length ? 'Estos proyectos esperan tu calificación' : 'No tienes proyectos pendientes por ahora' }}
                        </p>
                    </div>
                    <Link
                        v-if="pendientesJurado.length"
                        href="/mis-proyectos-evaluar"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700"
                    >
                        Ir a evaluar
                    </Link>
                </div>

                <div v-if="pendientesJurado.length" class="divide-y divide-gray-100">
                    <article v-for="p in pendientesJurado" :key="p.id" class="flex flex-wrap items-center gap-3 px-6 py-3.5 transition-colors hover:bg-gray-50">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50">
                            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconos.reloj" />
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900">{{ p.titulo }}</p>
                            <p class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-gray-500">
                                <span v-if="p.estudiante">{{ p.estudiante }}</span>
                                <span v-if="p.evento">· {{ p.evento }}</span>
                                <span v-if="p.aula">· Aula {{ p.aula }}</span>
                                <span v-if="p.horario">· {{ p.horario }}</span>
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pendiente</span>
                    </article>
                </div>

                <div v-else class="px-6 py-10 text-center">
                    <span class="mx-auto inline-flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="iconos.verificado" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm text-gray-600">Estás al día con tus evaluaciones.</p>
                </div>
            </section>

            <!-- Actividad, eventos y avisos -->
            <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Actividad reciente -->
                <div class="animar-entrada retraso-5 rounded-2xl border border-gray-200 bg-white shadow-sm lg:col-span-2">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="font-semibold text-gray-900">
                            {{ esGestor ? 'Actividad reciente del sistema' : 'Proyectos recientes' }}
                        </h2>
                    </div>

                    <ol v-if="actividadReciente.length" class="divide-y divide-gray-100">
                        <li
                            v-for="(item, i) in actividadReciente"
                            :key="item.id"
                            :class="['animar-lateral flex items-start gap-3 px-6 py-3.5 transition-colors hover:bg-gray-50', `retraso-${Math.min(i + 1, 6)}`]"
                        >
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-indigo-400"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">{{ item.texto }}</p>
                                <p class="mt-0.5 truncate text-xs text-gray-500">
                                    <span v-if="item.autor">{{ item.autor }}</span>
                                    <span v-if="item.contexto"> · {{ item.contexto }}</span>
                                    <span> · {{ haceCuanto(item.fecha) }}</span>
                                </p>
                            </div>
                            <span
                                v-if="item.estado"
                                :class="['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium', estadoProyecto[item.estado] ?? 'bg-gray-100 text-gray-600']"
                            >
                                {{ item.estado }}
                            </span>
                        </li>
                    </ol>

                    <div v-else class="px-6 py-12 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="iconos.proyectos" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">Todavía no hay actividad para mostrar.</p>
                    </div>
                </div>

                <!-- Columna lateral -->
                <div class="animar-entrada retraso-6 space-y-6">
                    <!-- Próximos eventos -->
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <h2 class="font-semibold text-gray-900">Próximos eventos</h2>
                            <Link href="/eventos" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Ver todos</Link>
                        </div>

                        <ul v-if="proximosEventos.length" class="divide-y divide-gray-100">
                            <li v-for="evento in proximosEventos" :key="evento.id">
                                <Link :href="`/eventos/${evento.id}`" class="block px-5 py-3.5 transition-colors hover:bg-gray-50">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 flex-col items-center justify-center rounded-lg bg-indigo-50 text-indigo-700">
                                            <span class="text-sm font-bold leading-none">{{ new Date(evento.fecha_inicio).getDate() }}</span>
                                            <span class="text-[10px] uppercase leading-none">{{ new Date(evento.fecha_inicio).toLocaleDateString('es-ES', { month: 'short' }) }}</span>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-gray-900">{{ evento.titulo }}</p>
                                            <p class="truncate text-xs text-gray-500">{{ evento.ubicacion || 'Sin ubicación definida' }}</p>
                                            <span class="mt-1 inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                                {{ cuentaRegresiva(evento.dias_restantes) }}
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            </li>
                        </ul>

                        <p v-else class="px-5 py-8 text-center text-sm text-gray-500">
                            No hay eventos programados por ahora.
                        </p>
                    </div>

                    <!-- Avisos sin leer -->
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <h2 class="font-semibold text-gray-900">Avisos sin leer</h2>
                            <Link href="/notificaciones" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Ver todos</Link>
                        </div>

                        <ul v-if="ultimasNotificaciones.length" class="divide-y divide-gray-100">
                            <li v-for="aviso in ultimasNotificaciones" :key="aviso.id" class="px-5 py-3.5">
                                <div class="flex items-start gap-3">
                                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-500 animar-latido"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900">{{ aviso.titulo }}</p>
                                        <p class="line-clamp-2 text-xs text-gray-500">{{ aviso.mensaje }}</p>
                                        <p class="mt-1 text-[11px] text-gray-400">{{ haceCuanto(aviso.fecha) }}</p>
                                    </div>
                                </div>
                            </li>
                        </ul>

                        <div v-else class="px-5 py-8 text-center">
                            <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconos.verificado" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Estás al día.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Gráficas: sólo para quien administra o coordina -->
            <section v-if="esGestor" class="space-y-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Estadísticas del sistema</h2>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="animar-escala rounded-2xl border border-gray-200 bg-white p-1 shadow-sm">
                        <BarChart title="Proyectos por estado" :data="graficas.proyectosPorEstado" :loading="graficas.cargando" height="300" />
                    </div>
                    <div class="animar-escala retraso-2 rounded-2xl border border-gray-200 bg-white p-1 shadow-sm">
                        <DoughnutChart title="Participación por rol" :data="graficas.participacionPorRol" :loading="graficas.cargando" height="300" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="animar-escala retraso-3 rounded-2xl border border-gray-200 bg-white p-1 shadow-sm">
                        <LineChart title="Actividad de los últimos meses" :data="graficas.estadisticasMensuales" :loading="graficas.cargando" height="300" />
                    </div>
                    <div class="animar-escala retraso-4 rounded-2xl border border-gray-200 bg-white p-1 shadow-sm">
                        <BarChart title="Promedio por rúbrica" :data="graficas.evaluacionesPorRubrica" :loading="graficas.cargando" height="300" />
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
