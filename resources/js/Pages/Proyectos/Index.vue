<template>
    <Head title="Proyectos" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Gestion de Proyectos</h1>
                            <p class="mt-2 text-gray-600">Administra y revisa los proyectos de investigacion</p>
                        </div>
                        <div class="flex space-x-3">
                            <Link
                                v-if="!isAdmin && !isCoordinator"
                                :href="route('proyectos.create')"
                                class="flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-green-700"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Nuevo Proyecto
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="feedbackMessage" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ feedbackMessage }}
                </div>

                <div v-if="errorMessage" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ errorMessage }}
                </div>

                <!-- Panorama general: cuenta TODO lo visible, no solo esta
                     página, y cada tarjeta filtra por su estado al pulsarla. -->
                <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <button
                        v-for="tarjeta in tarjetasResumen"
                        :key="tarjeta.titulo"
                        type="button"
                        @click="filtrarPorEstado(tarjeta.clave)"
                        :class="[
                            'tarjeta-interactiva animar-entrada rounded-xl border bg-white p-4 text-left',
                            filters.estado === tarjeta.clave ? 'border-blue-500 ring-2 ring-blue-200' : 'border-gray-200',
                        ]"
                    >
                        <span :class="['inline-flex rounded-lg px-2 py-0.5 text-xs font-medium', tarjeta.fondo, tarjeta.color]">
                            {{ tarjeta.titulo }}
                        </span>
                        <p :class="['mt-2 text-2xl font-bold', tarjeta.color]">{{ tarjeta.valor }}</p>
                    </button>
                </div>

                <p v-if="resumen?.autores" class="mb-4 text-sm text-gray-500">
                    {{ resumen.total }} proyectos de {{ resumen.autores }}
                    {{ resumen.autores === 1 ? 'estudiante' : 'estudiantes' }}.
                </p>

                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <form @submit.prevent="applyFilters" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Buscar proyecto</label>
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Buscar por titulo, resumen o autor..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Estado</label>
                            <select
                                v-model="filters.estado"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Todos los estados</option>
                                <option value="Inscrito">Inscrito</option>
                                <option value="En revisión">En revisión</option>
                                <option value="Aprobado">Aprobado</option>
                                <option value="Rechazado">Rechazado</option>
                            </select>
                        </div>
                        <div v-if="isAdmin || isCoordinator">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Semillero</label>
                            <select
                                v-model="filters.semillero"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Todos los semilleros</option>
                                <option v-for="semillero in semilleros" :key="semillero.id" :value="semillero.id">
                                    {{ semillero.nombre }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end space-x-2">
                            <button
                                type="submit"
                                class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700"
                            >
                                Buscar
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="flex-1 rounded-md bg-gray-500 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-gray-600"
                            >
                                Limpiar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Selector de forma de ver el listado -->
                <div v-if="filteredProyectos.length > 0" class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-sm text-gray-600">
                        Mostrando {{ filteredProyectos.length }}
                        {{ filteredProyectos.length === 1 ? 'proyecto' : 'proyectos' }}
                        <template v-if="proyectos?.total"> de {{ proyectos.total }}</template>
                    </p>
                    <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1">
                        <button
                            type="button"
                            @click="cambiarVista('tarjetas')"
                            :class="[
                                'rounded-md px-3 py-1.5 text-xs font-medium transition',
                                vista === 'tarjetas' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                            ]"
                        >
                            Tarjetas
                        </button>
                        <button
                            type="button"
                            @click="cambiarVista('tabla')"
                            :class="[
                                'rounded-md px-3 py-1.5 text-xs font-medium transition',
                                vista === 'tabla' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                            ]"
                        >
                            Tabla
                        </button>
                    </div>
                </div>

                <!-- Vista de tabla: muchos más proyectos a la vista de una vez -->
                <div v-if="filteredProyectos.length > 0 && vista === 'tabla'" class="animar-entrada overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Proyecto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Estudiante</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Semillero</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Registrado</th>
                                    <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="proyecto in filteredProyectos" :key="proyecto.id" class="transition-colors hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="max-w-xs truncate text-sm font-medium text-gray-900" :title="proyecto.titulo">
                                            {{ proyecto.titulo }}
                                        </p>
                                        <p class="font-mono text-xs text-gray-400">{{ proyecto.codigo }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ proyecto.autor?.name || 'Sin autor' }}</td>
                                    <td class="max-w-[14rem] truncate px-4 py-3 text-sm text-gray-700" :title="proyecto.semillero?.nombre">
                                        {{ proyecto.semillero?.nombre || 'Sin semillero' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="getEstadoBadgeClass(proyecto.estado)" class="rounded-full px-2.5 py-1 text-xs font-medium">
                                            {{ proyecto.estado }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ formatDate(proyecto.created_at) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <Link
                                            :href="route('proyectos.show', proyecto.id)"
                                            class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                        >
                                            Ver
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="filteredProyectos.length > 0 && vista === 'tarjetas'" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="proyecto in filteredProyectos"
                        :key="proyecto.id"
                        class="tarjeta-interactiva animar-entrada group rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="p-6">
                            <div class="mb-4 flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="line-clamp-2 text-lg font-semibold text-gray-900 transition-colors duration-200 group-hover:text-blue-600">
                                        {{ proyecto.titulo }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ proyecto.codigo }}</p>
                                </div>
                                <span :class="getEstadoBadgeClass(proyecto.estado)" class="rounded-full px-3 py-1 text-xs font-medium">
                                    {{ proyecto.estado }}
                                </span>
                            </div>

                            <div class="mb-4 space-y-2">
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="mr-2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="font-medium">{{ proyecto.autor?.name || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="mr-2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>{{ proyecto.semillero?.nombre || 'N/A' }}</span>
                                </div>
                            </div>

                            <p class="line-clamp-3 mb-4 break-words text-sm text-gray-600">{{ proyecto.resumen }}</p>

                            <div class="mb-4 flex justify-between text-sm text-gray-500">
                                <div class="flex items-center">
                                    <svg class="mr-1 h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ proyecto.versiones?.length || 0 }} versiones
                                </div>
                                <div class="flex items-center">
                                    <svg class="mr-1 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ formatDate(proyecto.created_at) }}
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                <Link
                                    :href="route('proyectos.show', proyecto.id)"
                                    class="flex-1 rounded-md bg-blue-600 px-3 py-2 text-center text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700"
                                >
                                    Ver Detalles
                                </Link>

                                <Link
                                    v-if="canEdit(proyecto)"
                                    :href="route('proyectos.edit', proyecto.id)"
                                    class="rounded-md bg-yellow-600 px-3 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-yellow-700"
                                >
                                    Editar
                                </Link>

                                <button
                                    v-if="canDelete(proyecto)"
                                    @click="confirmDeleteProject(proyecto.id, proyecto.titulo)"
                                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-700"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="py-12 text-center">
                    <div class="text-gray-500">
                        <svg class="mx-auto mb-4 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mb-2 text-lg font-medium text-gray-900">No hay proyectos disponibles</h3>
                        <p class="mb-4 text-gray-500">
                            {{ hasActiveFilters ? 'No se encontraron proyectos que coincidan con los filtros.' : 'No hay proyectos registrados en este momento.' }}
                        </p>
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700"
                        >
                            Limpiar Filtros
                        </button>
                    </div>
                </div>

                <div v-if="proyectos && proyectos.links" class="mt-8">
                    <nav class="flex items-center justify-between">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <Link
                                v-if="proyectos.prev_page_url"
                                :href="proyectos.prev_page_url"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Anterior
                            </Link>
                            <Link
                                v-if="proyectos.next_page_url"
                                :href="proyectos.next_page_url"
                                class="ml-3 relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Siguiente
                            </Link>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Mostrando
                                    <span class="font-medium">{{ proyectos.from || 0 }}</span>
                                    a
                                    <span class="font-medium">{{ proyectos.to || 0 }}</span>
                                    de
                                    <span class="font-medium">{{ proyectos.total || 0 }}</span>
                                    proyectos
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                                    <template v-for="(link, index) in proyectos.links" :key="index">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="[
                                                link.active
                                                    ? 'z-10 border-blue-500 bg-blue-50 text-blue-600'
                                                    : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50',
                                                'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                                            ]"
                                        />
                                        <span
                                            v-else
                                            v-html="link.label"
                                            class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    proyectos: Object,
    semilleros: Array,
    filters: Object,
    auth: Object,
    // Totales por estado de todo lo visible, no solo de la página actual.
    resumen: {
        type: Object,
        default: () => ({}),
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const filters = ref({
    search: props.filters?.search || '',
    estado: props.filters?.estado || '',
    semillero: props.filters?.semillero || '',
});

const feedbackMessage = ref(props.flash?.success || '');
const errorMessage = ref('');

const isAdmin = computed(() => props.auth?.user?.roles?.some((role) => role.name === 'Administrador'));
const isCoordinator = computed(() => props.auth?.user?.roles?.some((role) => role.name === 'Coordinador'));
const hasActiveFilters = computed(() => Boolean(filters.value.search || filters.value.estado || filters.value.semillero));

/**
 * Forma de ver el listado: tarjetas (3 por fila) o tabla compacta.
 *
 * Con muchos proyectos las tarjetas obligan a desplazarse mucho y cuesta
 * comparar; la tabla muestra bastantes más de una sola vez. La preferencia se
 * recuerda en el navegador, envuelta en try/catch porque en ventanas privadas
 * el almacenamiento puede estar bloqueado y lanzar excepción.
 */
const vista = ref('tarjetas');

try {
    const guardada = localStorage.getItem('proyectos.vista');
    if (guardada === 'tabla' || guardada === 'tarjetas') vista.value = guardada;
} catch {
    // Sin preferencia guardada se usa la vista por defecto.
}

const cambiarVista = (nueva) => {
    vista.value = nueva;
    try {
        localStorage.setItem('proyectos.vista', nueva);
    } catch {
        // Que no se pueda recordar no impide usarla en esta sesión.
    }
};

// Tarjetas del resumen. Al pulsar una se filtra por ese estado, que es el
// gesto natural después de ver el número.
const tarjetasResumen = computed(() => [
    { clave: '', titulo: 'Total', valor: props.resumen?.total ?? 0, color: 'text-gray-900', fondo: 'bg-gray-100' },
    { clave: 'Inscrito', titulo: 'Inscritos', valor: props.resumen?.inscritos ?? 0, color: 'text-blue-700', fondo: 'bg-blue-100' },
    { clave: 'En revisión', titulo: 'En revisión', valor: props.resumen?.en_revision ?? 0, color: 'text-amber-700', fondo: 'bg-amber-100' },
    { clave: 'Aprobado', titulo: 'Aprobados', valor: props.resumen?.aprobados ?? 0, color: 'text-emerald-700', fondo: 'bg-emerald-100' },
    { clave: 'Rechazado', titulo: 'Rechazados', valor: props.resumen?.rechazados ?? 0, color: 'text-red-700', fondo: 'bg-red-100' },
]);

const filtrarPorEstado = (estado) => {
    filters.value.estado = estado;
    applyFilters();
};

const filteredProyectos = computed(() => {
    if (isAdmin.value || isCoordinator.value) {
        return props.proyectos?.data || [];
    }

    if (!props.proyectos?.data) {
        return [];
    }

    let proyectos = props.proyectos.data;

    if (filters.value.search) {
        const query = filters.value.search.toLowerCase();
        proyectos = proyectos.filter((proyecto) =>
            proyecto.titulo.toLowerCase().includes(query)
            || proyecto.codigo.toLowerCase().includes(query)
            || proyecto.autor?.name?.toLowerCase().includes(query)
        );
    }

    if (filters.value.estado) {
        proyectos = proyectos.filter((proyecto) => proyecto.estado === filters.value.estado);
    }

    return proyectos;
});

const applyFilters = () => {
    router.get(route('proyectos.index'), filters.value, {
        preserveState: true,
        replace: true,
    });
};

const getEstadoBadgeClass = (estado) => {
    const classes = {
        Inscrito: 'bg-blue-100 text-blue-800',
        'En revisión': 'bg-yellow-100 text-yellow-800',
        Aprobado: 'bg-green-100 text-green-800',
        Rechazado: 'bg-red-100 text-red-800',
    };

    return classes[estado] || 'bg-gray-100 text-gray-800';
};

const canEdit = (proyecto) => {
    const user = props.auth?.user;
    if (!user) return false;

    if (user.roles?.some((role) => ['Administrador', 'Coordinador'].includes(role.name))) {
        return true;
    }

    return proyecto.autor_id === user.id;
};

const canDelete = (proyecto) => {
    const user = props.auth?.user;
    if (!user) return false;

    if (user.roles?.some((role) => ['Administrador', 'Coordinador'].includes(role.name))) {
        return true;
    }

    return proyecto.autor_id === user.id;
};

const confirmDeleteProject = (proyectoId, titulo) => {
    if (confirm(`¿Estas seguro de que quieres eliminar el proyecto "${titulo}"?\n\nEsta accion no se puede deshacer y eliminara todas las versiones del proyecto.`)) {
        deleteProject(proyectoId);
    }
};

const deleteProject = (proyectoId) => {
    feedbackMessage.value = '';
    errorMessage.value = '';

    router.delete(route('proyectos.destroy', proyectoId), {
        preserveScroll: true,
        onSuccess: (page) => {
            feedbackMessage.value = page.props.flash?.success || 'Proyecto eliminado exitosamente.';
        },
        onError: () => {
            errorMessage.value = 'No se pudo eliminar el proyecto.';
        },
    });
};

const formatDate = (date) => new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
});

const clearFilters = () => {
    filters.value = {
        search: '',
        estado: '',
        semillero: '',
    };
    applyFilters();
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
