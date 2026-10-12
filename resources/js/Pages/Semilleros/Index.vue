<template>
    <Head title="Gestión de Semilleros" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Confirmación de la acción: sin esto el estudiante enviaba la
                     solicitud y la pantalla no le decía nada, como si fallara. -->
                <div
                    v-if="$page.props.flash?.success"
                    class="animar-entrada mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                >
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-emerald-900">{{ $page.props.flash.success }}</p>
                </div>

                <div
                    v-if="$page.props.flash?.error"
                    class="animar-entrada mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3"
                >
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p class="text-sm font-medium text-red-900">{{ $page.props.flash.error }}</p>
                </div>
                <!-- Header con título y botón crear -->
                <div class="mb-8">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Gestión de Semilleros</h1>
                            <p class="text-gray-600 mt-2">Administra los semilleros de investigación</p>
                        </div>
                        <div v-if="canManage">
                            <Link
                                :href="route('semilleros.create')"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200 flex items-center"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Crear Semillero
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Filtros de búsqueda -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por nombre, línea temática o tutor..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select
                                v-model="selectedEstado"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Todos los estados</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="w-full bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200"
                            >
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de semilleros -->
                <div v-if="filteredSemilleros.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="semillero in filteredSemilleros" :key="semillero.id"
                         class="tarjeta-interactiva animar-entrada group rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="p-6">
                            <!-- Header de la tarjeta -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors duration-200">
                                        {{ semillero.nombre }}
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">{{ semillero.linea_tematica }}</p>
                                </div>
                                <span :class="getEstadoBadgeClass(semillero.estado)"
                                      class="px-3 py-1 text-xs font-medium rounded-full flex-shrink-0">
                                    {{ semillero.estado }}
                                </span>
                            </div>

                            <!-- Descripción -->
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ semillero.descripcion }}</p>

                            <!-- Información del tutor -->
                            <div class="flex items-center text-sm text-gray-500 mb-4">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="font-medium">{{ semillero.tutor?.name || 'No asignado' }}</span>
                            </div>

                            <!-- Estadísticas -->
                            <div class="flex justify-between text-sm text-gray-500 mb-4">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ semillero.miembros_aprobados?.length || 0 }}
                                    <template v-if="semillero.cupo_maximo"> de {{ semillero.cupo_maximo }}</template>
                                    miembros
                                </div>
                                <!-- Semillero con clave de acceso -->
                                <div v-if="semillero.requiere_clave" class="flex items-center text-gray-500" title="Requiere clave para unirse">
                                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Con clave
                                </div>
                                <div v-if="semillero.solicitudes_pendientes?.length > 0" class="flex items-center text-orange-500">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ semillero.solicitudes_pendientes.length }} pendientes
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="flex space-x-2">
                                <Link
                                    :href="route('semilleros.show', semillero.id)"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-3 rounded-md text-sm font-medium transition-colors duration-200"
                                >
                                    Ver Detalles
                                </Link>

                                <!-- Botones para Administradores y Coordinadores -->
                                <template v-if="isAdmin || isCoordinator">
                                    <Link
                                        :href="route('semilleros.edit', semillero.id)"
                                        class="bg-gray-600 hover:bg-gray-700 text-white py-2 px-3 rounded-md text-sm font-medium transition-colors duration-200"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        @click="confirmDelete(semillero)"
                                        class="bg-red-600 hover:bg-red-700 text-white py-2 px-3 rounded-md text-sm font-medium transition-colors duration-200"
                                    >
                                        Eliminar
                                    </button>
                                </template>

                                <!-- Botón para estudiantes -->
                                <button
                                    v-if="puedeUnirse(semillero)"
                                    @click="abrirSolicitud(semillero)"
                                    class="inline-flex items-center gap-1.5 rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-green-700"
                                >
                                    <svg v-if="semillero.requiere_clave" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Unirme
                                </button>

                                <!-- Motivo por el que no se puede solicitar el ingreso -->
                                <span
                                    v-else-if="isStudent && !isMember(semillero) && !hasPendingRequest(semillero) && motivoNoDisponible(semillero)"
                                    class="inline-flex cursor-not-allowed items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-400"
                                    :title="motivoNoDisponible(semillero) === 'Semillero inactivo'
                                        ? 'Este semillero está inactivo y no admite nuevos integrantes'
                                        : 'Este semillero ya alcanzó su cupo máximo'"
                                >
                                    {{ motivoNoDisponible(semillero) }}
                                </span>

                                <!-- Estado de solicitud -->
                                <span
                                    v-if="isStudent && hasPendingRequest(semillero)"
                                    class="bg-yellow-100 text-yellow-800 py-2 px-3 rounded-md text-sm font-medium"
                                >
                                    Solicitud Pendiente
                                </span>

                                <span
                                    v-if="isStudent && isMember(semillero)"
                                    class="bg-green-100 text-green-800 py-2 px-3 rounded-md text-sm font-medium"
                                >
                                    Miembro
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje si no hay semilleros -->
                <div v-else class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron semilleros</h3>
                        <p class="text-gray-500 mb-4">
                            {{ search || selectedEstado ? 'No hay semilleros que coincidan con los filtros aplicados.' : 'No hay semilleros de investigación registrados.' }}
                        </p>
                        <button
                            v-if="search || selectedEstado"
                            @click="clearFilters"
                            class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-200"
                        >
                            Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- Paginación -->
                <div v-if="semilleros && semilleros.links" class="mt-8">
                    <nav class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="semilleros.prev_page_url"
                                :href="semilleros.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Anterior
                            </Link>
                            <Link
                                v-if="semilleros.next_page_url"
                                :href="semilleros.next_page_url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Siguiente
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Mostrando
                                    <span class="font-medium">{{ semilleros.from || 0 }}</span>
                                    a
                                    <span class="font-medium">{{ semilleros.to || 0 }}</span>
                                    de
                                    <span class="font-medium">{{ semilleros.total || 0 }}</span>
                                    resultados
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <template v-for="(link, index) in semilleros.links" :key="index">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="[
                                                link.active
                                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                            ]"
                                        />
                                        <span
                                            v-else
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Confirmar el ingreso; pide la clave si el semillero la exige -->
        <div v-if="semilleroAUnirse" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4" @click.self="cerrarSolicitud">
            <div class="mx-auto mt-24 w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="px-6 py-5">
                    <h3 class="text-base font-semibold text-gray-900">Solicitar ingreso</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Vas a pedir unirte a <strong>«{{ semilleroAUnirse.nombre }}»</strong>.
                        El tutor o el coordinador debe aprobar tu solicitud.
                    </p>

                    <div v-if="semilleroAUnirse.requiere_clave" class="mt-4">
                        <label for="clave-semillero" class="mb-1.5 block text-xs font-medium text-gray-700">
                            Clave del semillero
                        </label>
                        <input
                            id="clave-semillero"
                            v-model="claveIngresada"
                            type="password"
                            autocomplete="off"
                            placeholder="Escribe la clave que te compartieron"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @keyup.enter="confirmarSolicitud"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Este semillero es de acceso restringido. Pide la clave a su tutor.
                        </p>
                    </div>

                    <p v-if="errorClave" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">
                        {{ errorClave }}
                    </p>
                </div>
                <div class="flex justify-end gap-2 bg-gray-50 px-6 py-4">
                    <button
                        @click="cerrarSolicitud"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="confirmarSolicitud"
                        :disabled="enviandoSolicitud"
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        {{ enviandoSolicitud ? 'Enviando…' : 'Enviar solicitud' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    semilleros: Object,
    auth: Object,
});

const search = ref('');
const selectedEstado = ref('');

const filteredSemilleros = computed(() => {
    // Verificar que semilleros existe y tiene data
    if (!props.semilleros || !props.semilleros.data) {
        return [];
    }

    let semilleros = props.semilleros.data;

    if (search.value) {
        semilleros = semilleros.filter(semillero =>
            semillero.nombre.toLowerCase().includes(search.value.toLowerCase()) ||
            semillero.linea_tematica.toLowerCase().includes(search.value.toLowerCase()) ||
            semillero.tutor?.name.toLowerCase().includes(search.value.toLowerCase())
        );
    }

    if (selectedEstado.value) {
        semilleros = semilleros.filter(semillero => semillero.estado === selectedEstado.value);
    }

    return semilleros;
});

const getEstadoBadgeClass = (estado) => {
    return estado === 'activo'
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800';
};

const isStudent = computed(() => {
    return props.auth?.user?.roles?.some(role => role.name === 'Estudiante');
});

const isAdmin = computed(() => {
    return props.auth?.user?.roles?.some(role => role.name === 'Administrador');
});

const isCoordinator = computed(() => {
    return props.auth?.user?.roles?.some(role => role.name === 'Coordinador');
});

const canManage = computed(() => {
    return props.auth?.user?.roles?.some(role =>
        ['Administrador', 'Coordinador'].includes(role.name)
    );
});

/**
 * Un estudiante sólo puede pedir el ingreso si el semillero está activo, le
 * quedan cupos y todavía no es miembro ni tiene solicitud pendiente.
 */
const puedeUnirse = (semillero) =>
    isStudent.value
    && semillero.estado === 'activo'
    && semillero.cupos_disponibles !== 0
    && !isMember(semillero)
    && !hasPendingRequest(semillero);

const motivoNoDisponible = (semillero) => {
    if (semillero.estado !== 'activo') return 'Semillero inactivo';
    if (semillero.cupos_disponibles === 0) return 'Cupo lleno';
    return null;
};

// Semilleros con clave: se pide antes de enviar la solicitud.
const semilleroAUnirse = ref(null);
const claveIngresada = ref('');
const errorClave = ref('');
const enviandoSolicitud = ref(false);

const abrirSolicitud = (semillero) => {
    semilleroAUnirse.value = semillero;
    claveIngresada.value = '';
    errorClave.value = '';
};

const cerrarSolicitud = () => {
    semilleroAUnirse.value = null;
    claveIngresada.value = '';
    errorClave.value = '';
};

const confirmarSolicitud = () => {
    const semillero = semilleroAUnirse.value;
    if (!semillero) return;

    if (semillero.requiere_clave && !claveIngresada.value.trim()) {
        errorClave.value = 'Escribe la clave del semillero.';
        return;
    }

    enviandoSolicitud.value = true;
    router.post(
        route('semilleros.request-join', semillero.id),
        { clave_acceso: claveIngresada.value },
        {
            preserveScroll: true,
            onSuccess: () => cerrarSolicitud(),
            onError: (errores) => {
                errorClave.value = errores.clave_acceso || 'No se pudo enviar la solicitud.';
            },
            onFinish: () => (enviandoSolicitud.value = false),
        }
    );
};

const hasPendingRequest = (semillero) => {
    return semillero.solicitudes_pendientes?.some(solicitud =>
        solicitud.id === props.auth?.user?.id
    );
};

const isMember = (semillero) => {
    return semillero.miembros_aprobados?.some(miembro =>
        miembro.id === props.auth?.user?.id
    );
};

const clearFilters = () => {
    search.value = '';
    selectedEstado.value = '';
};

const confirmDelete = (semillero) => {
    if (confirm(`¿Estás seguro de que quieres eliminar el semillero "${semillero.nombre}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('semilleros.destroy', semillero.id));
    }
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
