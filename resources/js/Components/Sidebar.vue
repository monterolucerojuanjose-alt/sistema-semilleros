<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    isCollapsed: { type: Boolean, default: false },
});

const page = usePage();
const authData = computed(() => page.props.auth || props.auth);

const userRole = computed(() => {
    const user = authData.value?.user;
    if (!user) return 'Estudiante';
    return user.roles?.[0]?.name || user.role || 'Estudiante';
});

const currentUrl = computed(() => page.props.ziggy?.location || window.location.pathname);

// Avisos sin leer del usuario; se comparte desde HandleInertiaRequests.
const noLeidas = computed(() => page.props.notificacionesNoLeidas ?? 0);

/**
 * Menú por rol, agrupado en secciones para que no sea una lista larga y plana.
 *
 * Cada enlace debe corresponder a una ruta real y accesible para el rol: la
 * prueba tests/Usabilidad/NavegacionTest.php verifica que todas abran.
 */
const secciones = computed(() => {
    const menus = {
        'Administrador': [
            { titulo: null, opciones: [
                { name: 'Inicio', href: '/dashboard', icon: 'home' },
            ]},
            { titulo: 'Gestión', opciones: [
                { name: 'Usuarios', href: '/users', icon: 'user-group' },
                { name: 'Semilleros', href: '/semilleros', icon: 'academic-cap' },
                { name: 'Proyectos', href: '/proyectos', icon: 'folder' },
                { name: 'Eventos', href: '/eventos', icon: 'calendar' },
                { name: 'Rúbricas', href: '/rubricas', icon: 'document-text' },
            ]},
            { titulo: 'Seguimiento', opciones: [
                { name: 'Propuestas pendientes', href: '/propuestas/pendientes', icon: 'clipboard-check' },
                { name: 'Resultados de evaluación', href: '/resultados-evaluacion', icon: 'chart-bar' },
                { name: 'Historial de inscripciones', href: '/historial-inscripciones', icon: 'clipboard-list' },
                { name: 'Certificados', href: '/certificados', icon: 'document-text' },
            ]},
            { titulo: 'Sistema', opciones: [
                { name: 'Reportes', href: '/reportes', icon: 'chart-bar' },
                { name: 'Notificaciones', href: '/notificaciones', icon: 'bell' },
                { name: 'Auditoría', href: '/auditoria', icon: 'shield-check' },
            ]},
        ],
        // Se quitaron «Aprobaciones» y «Asignación de Jurados»: apuntaban a
        // /aprobaciones y /jurados, que no existen. Ambas tareas se hacen desde
        // «Propuestas pendientes».
        'Coordinador': [
            { titulo: null, opciones: [
                { name: 'Inicio', href: '/dashboard', icon: 'home' },
            ]},
            { titulo: 'Gestión', opciones: [
                { name: 'Semilleros', href: '/semilleros', icon: 'academic-cap' },
                { name: 'Proyectos', href: '/proyectos', icon: 'folder' },
                { name: 'Eventos', href: '/eventos', icon: 'calendar' },
                { name: 'Rúbricas', href: '/rubricas', icon: 'document-text' },
            ]},
            { titulo: 'Seguimiento', opciones: [
                { name: 'Propuestas pendientes', href: '/propuestas/pendientes', icon: 'clipboard-check' },
                { name: 'Resultados de evaluación', href: '/resultados-evaluacion', icon: 'chart-bar' },
                { name: 'Historial de inscripciones', href: '/historial-inscripciones', icon: 'clipboard-list' },
                { name: 'Certificados', href: '/certificados', icon: 'document-text' },
            ]},
            { titulo: 'Sistema', opciones: [
                { name: 'Reportes', href: '/reportes', icon: 'chart-bar' },
                { name: 'Notificaciones', href: '/notificaciones', icon: 'bell' },
                { name: 'Auditoría', href: '/auditoria', icon: 'shield-check' },
            ]},
        ],
        'Docente/Tutor': [
            { titulo: null, opciones: [
                { name: 'Inicio', href: '/dashboard', icon: 'home' },
            ]},
            { titulo: 'Mi semillero', opciones: [
                { name: 'Mis semilleros', href: '/mis-semilleros', icon: 'academic-cap' },
                { name: 'Solicitudes', href: '/solicitudes', icon: 'inbox' },
                { name: 'Estudiantes', href: '/estudiantes', icon: 'users' },
            ]},
            { titulo: 'Académico', opciones: [
                { name: 'Proyectos', href: '/proyectos', icon: 'folder' },
                { name: 'Proyectos de eventos', href: '/mis-proyectos-evaluar', icon: 'clipboard-check' },
                { name: 'Resultados de evaluación', href: '/resultados-evaluacion', icon: 'chart-bar' },
            ]},
            { titulo: 'General', opciones: [
                { name: 'Semilleros', href: '/semilleros', icon: 'academic-cap' },
                { name: 'Eventos', href: '/eventos', icon: 'calendar' },
                { name: 'Notificaciones', href: '/notificaciones', icon: 'bell' },
            ]},
        ],
        'Estudiante': [
            { titulo: null, opciones: [
                { name: 'Inicio', href: '/dashboard', icon: 'home' },
            ]},
            { titulo: 'Mi trabajo', opciones: [
                { name: 'Mis proyectos', href: '/proyectos', icon: 'folder' },
                { name: 'Mis inscripciones', href: '/mis-inscripciones', icon: 'clipboard-list' },
                { name: 'Mis notas', href: '/mis-notas', icon: 'document-text' },
                { name: 'Mis certificados', href: '/mis-certificados', icon: 'academic-cap' },
            ]},
            { titulo: 'Participar', opciones: [
                { name: 'Semilleros', href: '/semilleros', icon: 'academic-cap' },
                { name: 'Eventos', href: '/eventos', icon: 'calendar' },
                { name: 'Notificaciones', href: '/notificaciones', icon: 'bell' },
            ]},
        ],
        // El jurado sólo necesita lo suyo: lo que le falta por calificar, lo que
        // ya calificó y la rúbrica con la que califica.
        'Jurado': [
            { titulo: null, opciones: [
                { name: 'Inicio', href: '/dashboard', icon: 'home' },
            ]},
            { titulo: 'Evaluación', opciones: [
                { name: 'Evaluar proyectos', href: '/mis-proyectos-evaluar', icon: 'clipboard-check' },
                { name: 'Mis evaluaciones', href: '/evaluaciones', icon: 'folder' },
                { name: 'Rúbricas', href: '/rubricas', icon: 'document-text' },
            ]},
            { titulo: 'General', opciones: [
                { name: 'Notificaciones', href: '/notificaciones', icon: 'bell' },
            ]},
        ],
    };

    return menus[userRole.value] || menus['Estudiante'];
});

const estaActiva = (href) =>
    currentUrl.value === href || currentUrl.value.startsWith(href + '/');

const iconos = {
    'home': 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    'users': 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
    'shield-check': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'chart-bar': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'academic-cap': 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
    'folder': 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
    'user-group': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    'document-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'inbox': 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
    'clipboard-list': 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
    'clipboard-check': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    'bell': 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.158c0 .538-.214 1.055-.595 1.437L4 17h5m6 0v1a3 3 0 11-6 0v-1h6z',
    'calendar': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
};

const getIcon = (nombre) => iconos[nombre] || iconos['home'];
</script>

<template>
    <aside
        :class="[
            'flex h-full flex-col bg-slate-900 text-white transition-all duration-300 ease-in-out',
            isCollapsed ? 'w-16' : 'w-64',
        ]"
        role="navigation"
        aria-label="Navegación principal"
    >
        <!-- Identidad del sistema -->
        <div class="flex items-center gap-3 border-b border-white/10 px-4 py-4">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 shadow-lg shadow-indigo-500/25">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
            </span>
            <div v-if="!isCollapsed" class="min-w-0">
                <p class="truncate text-sm font-bold tracking-tight">Semilleros</p>
                <p class="truncate text-xs text-slate-400">{{ userRole }}</p>
            </div>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4">
            <div v-for="(seccion, s) in secciones" :key="s">
                <p
                    v-if="seccion.titulo && !isCollapsed"
                    class="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-500"
                >
                    {{ seccion.titulo }}
                </p>
                <!-- Colapsada, la sección se separa con una línea en lugar del título -->
                <div v-else-if="seccion.titulo" class="mx-2 mb-2 border-t border-white/10"></div>

                <div class="space-y-0.5">
                    <Link
                        v-for="opcion in seccion.opciones"
                        :key="opcion.href"
                        :href="opcion.href"
                        :title="isCollapsed ? opcion.name : null"
                        :class="[
                            'group relative flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200',
                            estaActiva(opcion.href)
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/40'
                                : 'text-slate-300 hover:bg-white/10 hover:text-white',
                            isCollapsed ? 'justify-center' : '',
                        ]"
                        :aria-current="estaActiva(opcion.href) ? 'page' : null"
                    >
                        <!-- Marca lateral de la opción activa -->
                        <span
                            v-if="estaActiva(opcion.href) && !isCollapsed"
                            class="absolute -left-3 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-indigo-400"
                        ></span>

                        <svg
                            class="h-5 w-5 shrink-0 transition-transform duration-200 group-hover:scale-110"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" :d="getIcon(opcion.icon)" />
                        </svg>

                        <span v-if="!isCollapsed" class="ml-3 truncate">{{ opcion.name }}</span>

                        <!-- Avisos pendientes sobre la opción de Notificaciones -->
                        <span
                            v-if="opcion.href === '/notificaciones' && noLeidas > 0"
                            :class="[
                                'inline-flex items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white',
                                isCollapsed ? 'absolute right-1 top-1 h-4 min-w-4 px-1' : 'ml-auto h-5 min-w-5 px-1.5',
                            ]"
                            :aria-label="`${noLeidas} notificaciones sin leer`"
                        >
                            {{ noLeidas > 99 ? '99+' : noLeidas }}
                        </span>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Usuario -->
        <div class="border-t border-white/10 p-3">
            <Link
                href="/profile"
                :title="isCollapsed ? authData?.user?.name : null"
                :class="[
                    'flex items-center rounded-lg p-2 transition-colors hover:bg-white/10',
                    isCollapsed ? 'justify-center' : '',
                ]"
            >
                <img
                    v-if="authData?.user?.avatar_url"
                    :src="authData.user.avatar_url"
                    alt=""
                    class="h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/20"
                />
                <span
                    v-else
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 text-sm font-bold text-white"
                >
                    {{ authData?.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                </span>
                <span v-if="!isCollapsed" class="ml-3 min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium text-white">
                        {{ authData?.user?.name || 'Usuario' }}
                    </span>
                    <span class="block truncate text-xs text-slate-400">
                        {{ authData?.user?.email }}
                    </span>
                </span>
            </Link>
        </div>
    </aside>
</template>

<style scoped>
/* Barra de desplazamiento discreta, a tono con el fondo oscuro del menú. */
nav::-webkit-scrollbar {
    width: 6px;
}

nav::-webkit-scrollbar-track {
    background: transparent;
}

nav::-webkit-scrollbar-thumb {
    background: rgb(71 85 105);
    border-radius: 9999px;
}

nav::-webkit-scrollbar-thumb:hover {
    background: rgb(100 116 139);
}

nav {
    scrollbar-width: thin;
    scrollbar-color: rgb(71 85 105) transparent;
}
</style>
