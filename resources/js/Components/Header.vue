<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    auth: Object,
    isCollapsed: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['toggle-sidebar']);

const page = usePage();
const authData = computed(() => page.props.auth || props.auth);

const showingUserMenu = ref(false);
const showingNotificaciones = ref(false);
const cargandoNotificaciones = ref(false);
const notificaciones = ref([]);

// El contador llega en las props compartidas, así que el distintivo aparece
// de inmediato sin pedir nada al servidor.
const noLeidas = computed(() => page.props.notificacionesNoLeidas ?? 0);

// Nombre legible de la pantalla actual, para orientar al usuario en la cabecera.
const NOMBRES_PANTALLA = {
    '/dashboard': 'Inicio',
    '/users': 'Gestión de usuarios',
    '/semilleros': 'Semilleros',
    '/mis-semilleros': 'Mis semilleros',
    '/proyectos': 'Proyectos',
    '/nuevo-proyecto': 'Nuevo proyecto',
    '/eventos': 'Eventos académicos',
    '/propuestas': 'Propuestas',
    '/resultados-evaluacion': 'Resultados de evaluación',
    '/evaluaciones': 'Evaluaciones',
    '/mis-proyectos-evaluar': 'Evaluar proyectos',
    '/rubricas': 'Rúbricas',
    '/certificados': 'Certificados',
    '/mis-certificados': 'Mis certificados',
    '/mis-inscripciones': 'Mis inscripciones',
    '/historial-inscripciones': 'Historial de inscripciones',
    '/mis-notas': 'Mis notas',
    '/estudiantes': 'Estudiantes',
    '/solicitudes': 'Solicitudes de ingreso',
    '/reportes': 'Reportes',
    '/notificaciones': 'Notificaciones',
    '/auditoria': 'Auditoría',
    '/profile': 'Mi perfil',
};

const tituloPantalla = computed(() => {
    const ruta = page.props.ziggy?.location
        ? new URL(page.props.ziggy.location, 'http://localhost').pathname
        : window.location.pathname;

    // Se busca el prefijo más largo para que /semilleros/3/edit siga diciendo
    // «Semilleros» en lugar de caer en el nombre por defecto.
    const encontrado = Object.keys(NOMBRES_PANTALLA)
        .filter((base) => ruta === base || ruta.startsWith(base + '/'))
        .sort((a, b) => b.length - a.length)[0];

    return encontrado ? NOMBRES_PANTALLA[encontrado] : 'Sistema de Semilleros';
});

const toggleUserMenu = () => {
    showingUserMenu.value = !showingUserMenu.value;
    showingNotificaciones.value = false;
};

/** Las notificaciones se piden al abrir el panel, no en cada carga de página. */
const toggleNotificaciones = async () => {
    showingUserMenu.value = false;
    showingNotificaciones.value = !showingNotificaciones.value;

    if (!showingNotificaciones.value) return;

    cargandoNotificaciones.value = true;
    try {
        const { data } = await axios.get('/api/notificaciones/no-leidas');
        notificaciones.value = data.notificaciones ?? [];
    } catch {
        notificaciones.value = [];
    } finally {
        cargandoNotificaciones.value = false;
    }
};

const marcarLeida = async (id) => {
    try {
        await axios.put(`/notificaciones/${id}/leida`);
        notificaciones.value = notificaciones.value.filter((n) => n.id !== id);
        router.reload({ only: ['notificacionesNoLeidas', 'notificaciones', 'estadisticas'] });
    } catch {
        // Si falla, el panel se recompone al volver a abrirlo.
    }
};

const marcarTodas = async () => {
    try {
        await axios.put('/notificaciones/marcar-todas-leidas');
        notificaciones.value = [];
        router.reload({ only: ['notificacionesNoLeidas', 'notificaciones', 'estadisticas'] });
    } catch {
        // Sin acción: el contador se recalcula en la siguiente navegación.
    }
};

const cerrarTodo = () => {
    showingUserMenu.value = false;
    showingNotificaciones.value = false;
};

const COLORES = {
    info: 'bg-blue-100 text-blue-600',
    exito: 'bg-emerald-100 text-emerald-600',
    alerta: 'bg-amber-100 text-amber-600',
    error: 'bg-red-100 text-red-600',
};
const colorTipo = (tipo) => COLORES[tipo] ?? 'bg-gray-100 text-gray-500';

const ICONOS = {
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    exito: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    alerta: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    error: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
};
const iconoTipo = (tipo) => ICONOS[tipo] ?? ICONOS.info;

const tiempoRelativo = (fecha) => {
    const minutos = Math.floor((Date.now() - new Date(fecha).getTime()) / 60000);
    if (minutos < 1) return 'Ahora';
    if (minutos < 60) return `Hace ${minutos} min`;
    if (minutos < 1440) return `Hace ${Math.floor(minutos / 60)} h`;
    return `Hace ${Math.floor(minutos / 1440)} d`;
};

// Cerrar los desplegables al pulsar fuera o con Escape.
const alPulsarFuera = (evento) => {
    if (!evento.target.closest('[data-desplegable]')) cerrarTodo();
};
const alPulsarTecla = (evento) => {
    if (evento.key === 'Escape') cerrarTodo();
};

onMounted(() => {
    document.addEventListener('click', alPulsarFuera);
    document.addEventListener('keydown', alPulsarTecla);
});
onUnmounted(() => {
    document.removeEventListener('click', alPulsarFuera);
    document.removeEventListener('keydown', alPulsarTecla);
});
</script>

<template>
    <!-- Fija al desplazar: el acceso al menú y a los avisos queda siempre a mano. -->
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/85 backdrop-blur-md">
        <div class="flex items-center justify-between px-4 py-3">
            <!-- Botón para colapsar el menú y nombre de la pantalla -->
            <div class="flex min-w-0 items-center">
                <button
                    @click="$emit('toggle-sidebar')"
                    class="rounded-lg p-2 text-gray-600 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900"
                    :aria-label="isCollapsed ? 'Abrir el menú' : 'Contraer el menú'"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="ml-3 min-w-0">
                    <h1 class="truncate text-lg font-bold text-gray-900 sm:text-xl">{{ tituloPantalla }}</h1>
                    <p class="hidden truncate text-xs text-gray-500 sm:block">Sistema de gestión de semilleros de investigación</p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <!-- Notificaciones: disponible para todos los roles desde cualquier pantalla -->
                <div class="relative" data-desplegable>
                    <button
                        @click="toggleNotificaciones"
                        class="relative rounded-lg p-2 text-gray-600 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900"
                        :aria-label="noLeidas > 0 ? `Notificaciones, ${noLeidas} sin leer` : 'Notificaciones'"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span
                            v-if="noLeidas > 0"
                            class="absolute right-0.5 top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-white"
                        >
                            {{ noLeidas > 99 ? '99+' : noLeidas }}
                        </span>
                    </button>

                    <!-- Panel de notificaciones -->
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0 -translate-y-1 scale-95"
                        leave-active-class="transition duration-100 ease-in"
                        leave-to-class="opacity-0 scale-95"
                    >
                    <div
                        v-show="showingNotificaciones"
                        class="absolute right-0 z-50 mt-2 w-80 origin-top-right overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl sm:w-96"
                    >
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                            <h3 class="text-sm font-semibold text-gray-900">Notificaciones</h3>
                            <button
                                v-if="notificaciones.length"
                                @click="marcarTodas"
                                class="text-xs font-medium text-indigo-600 hover:underline"
                            >
                                Marcar todas
                            </button>
                        </div>

                        <div class="max-h-80 overflow-y-auto">
                            <p v-if="cargandoNotificaciones" class="px-4 py-8 text-center text-sm text-gray-500">
                                Cargando…
                            </p>

                            <p v-else-if="notificaciones.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
                                No tienes avisos sin leer
                            </p>

                            <article
                                v-for="notificacion in notificaciones"
                                :key="notificacion.id"
                                class="flex items-start gap-3 border-b border-gray-50 px-4 py-3 transition-colors last:border-0 hover:bg-gray-50"
                            >
                                <span :class="['mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full', colorTipo(notificacion.tipo)]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="iconoTipo(notificacion.tipo)" />
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ notificacion.titulo }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-gray-600">{{ notificacion.mensaje }}</p>
                                    <p class="mt-1 text-[11px] text-gray-400">
                                        {{ tiempoRelativo(notificacion.created_at) }}
                                        <template v-if="notificacion.remitente"> · {{ notificacion.remitente.name }}</template>
                                    </p>
                                </div>
                                <button
                                    @click="marcarLeida(notificacion.id)"
                                    title="Marcar como leída"
                                    class="shrink-0 rounded-md p-1 text-gray-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </button>
                            </article>
                        </div>

                        <Link
                            href="/notificaciones"
                            @click="cerrarTodo"
                            class="block border-t border-gray-100 bg-gray-50 px-4 py-2.5 text-center text-sm font-medium text-indigo-600 transition-colors hover:bg-gray-100"
                        >
                            Ver todas las notificaciones
                        </Link>
                    </div>
                    </Transition>
                </div>

                <!-- Menú de usuario -->
                <div class="relative" data-desplegable>
                    <button
                        @click="toggleUserMenu"
                        class="flex items-center gap-2 rounded-lg p-1.5 text-gray-600 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900"
                        aria-label="Menú de usuario"
                    >
                        <img
                            v-if="authData?.user?.avatar_url"
                            :src="authData.user.avatar_url"
                            alt=""
                            class="h-9 w-9 rounded-full object-cover ring-2 ring-indigo-100"
                        />
                        <span
                            v-else
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 text-sm font-bold text-white"
                        >
                            {{ authData?.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                        </span>
                        <!-- El nombre se oculta en pantallas estrechas para no desbordar -->
                        <span class="hidden max-w-[11rem] text-left lg:block">
                            <span class="block truncate text-sm font-medium text-gray-900">{{ authData?.user?.name || 'Usuario' }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ authData?.user?.email }}</span>
                        </span>
                        <svg
                            :class="['h-4 w-4 transition-transform duration-200', showingUserMenu ? 'rotate-180' : '']"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Desplegable del usuario -->
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0 -translate-y-1 scale-95"
                        leave-active-class="transition duration-100 ease-in"
                        leave-to-class="opacity-0 scale-95"
                    >
                    <div
                        v-show="showingUserMenu"
                        @click="cerrarTodo"
                        class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-xl border border-gray-200 bg-white py-1 shadow-xl"
                    >
                        <div class="border-b border-gray-100 px-4 py-2.5 lg:hidden">
                            <p class="truncate text-sm font-medium text-gray-900">{{ authData?.user?.name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ authData?.user?.email }}</p>
                        </div>
                        <Link
                            :href="route('profile.edit')"
                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Mi Perfil
                        </Link>

                        <Link
                            href="/notificaciones"
                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            Notificaciones
                            <span v-if="noLeidas > 0" class="ml-auto rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white">
                                {{ noLeidas }}
                            </span>
                        </Link>

                        <div class="border-t border-gray-200 my-1"></div>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Cerrar Sesión
                        </Link>
                    </div>
                    </Transition>
                </div>
            </div>
        </div>
    </header>
</template>
