<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    semillero: Object,
    auth: Object,
});

const canManage = computed(() => {
    return props.auth?.user?.roles?.some(role =>
        ['Administrador', 'Coordinador'].includes(role.name)
    );
});

const isStudent = computed(() => {
    return props.auth?.user?.roles?.some(role => role.name === 'Estudiante');
});

const requestJoin = () => {
    if (confirm(`¿Estás seguro de que quieres solicitar unirte al semillero "${props.semillero.nombre}"?`)) {
        router.post(route('semilleros.request-join', props.semillero.id));
    }
};

const approveRequest = (user, status) => {
    const action = status === 'aprobado' ? 'aprobar' : 'rechazar';
    if (confirm(`¿Estás seguro de que quieres ${action} la solicitud de ${user.name}?`)) {
        router.put(route('semilleros.approve-join', [props.semillero.id, user.id]), {
            estado: status
        });
    }
};

const hasPendingRequest = computed(() => {
    return props.semillero.solicitudes_pendientes?.some(solicitud =>
        solicitud.id === props.auth?.user?.id
    );
});

const isMember = computed(() => {
    return props.semillero.miembros_aprobados?.some(miembro =>
        miembro.id === props.auth?.user?.id
    );
});

const getEstadoBadgeClass = (estado) => {
    return estado === 'activo'
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800';
};

const getEstadoDisplayName = (estado) => {
    return estado === 'activo' ? 'Activo' : 'Inactivo';
};
</script>

<template>
    <Head :title="`Semillero: ${semillero.nombre}`" />

    <AppLayout :auth="auth">
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">{{ semillero.nombre }}</h1>
                                <p class="text-gray-600 mt-2">{{ semillero.linea_tematica }}</p>
                                <span
                                    :class="getEstadoBadgeClass(semillero.estado)"
                                    class="inline-flex px-3 py-1 text-sm font-semibold rounded-full mt-2"
                                >
                                    {{ getEstadoDisplayName(semillero.estado) }}
                                </span>
                            </div>
                            <div class="flex space-x-3">
                                <Link
                                    :href="route('semilleros.index')"
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200"
                                >
                                    Volver
                                </Link>
                                <Link
                                    v-if="canManage"
                                    :href="route('semilleros.edit', semillero.id)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200"
                                >
                                    Editar
                                </Link>
                            </div>
                        </div>

                        <!-- Información del semillero -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                            <!-- Información principal -->
                            <div class="lg:col-span-2">
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Descripción</h2>
                                    <p class="text-gray-700 leading-relaxed">{{ semillero.descripcion }}</p>
                                </div>
                            </div>

                            <!-- Información del tutor -->
                            <div>
                                <div class="bg-blue-50 rounded-lg p-6">
                                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Tutor Responsable</h2>
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                                            <span class="text-white font-semibold text-lg">
                                                {{ semillero.tutor?.name?.charAt(0) || 'T' }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ semillero.tutor?.name || 'Sin asignar' }}</p>
                                            <p class="text-sm text-gray-600">{{ semillero.tutor?.email || '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Miembros del semillero -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Miembros del Semillero</h2>
                            <div v-if="semillero.miembros_aprobados?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="miembro in semillero.miembros_aprobados"
                                    :key="miembro.id"
                                    class="bg-white border border-gray-200 rounded-lg p-4"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center">
                                            <span class="text-white font-semibold">
                                                {{ miembro.name.charAt(0) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ miembro.name }}</p>
                                            <p class="text-sm text-gray-600">{{ miembro.email }}</p>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ miembro.pivot?.rol_en_semillero || 'estudiante' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                <p>No hay miembros en este semillero aún.</p>
                            </div>
                        </div>

                        <!-- Solicitudes pendientes (solo para coordinadores) -->
                        <div v-if="canManage && semillero.solicitudes_pendientes?.length > 0" class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Solicitudes Pendientes</h2>
                            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Estudiante
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Email
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Fecha de Solicitud
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="solicitud in semillero.solicitudes_pendientes" :key="solicitud.id">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 bg-yellow-600 rounded-full flex items-center justify-center mr-3">
                                                        <span class="text-white font-semibold text-sm">
                                                            {{ solicitud.name.charAt(0) }}
                                                        </span>
                                                    </div>
                                                    <div class="text-sm font-medium text-gray-900">{{ solicitud.name }}</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ solicitud.email }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ new Date(solicitud.pivot?.fecha).toLocaleDateString() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                <button
                                                    @click="approveRequest(solicitud, 'aprobado')"
                                                    class="text-green-600 hover:text-green-900 bg-green-100 hover:bg-green-200 px-3 py-1 rounded-md transition-colors duration-200"
                                                >
                                                    Aprobar
                                                </button>
                                                <button
                                                    @click="approveRequest(solicitud, 'rechazado')"
                                                    class="text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 px-3 py-1 rounded-md transition-colors duration-200"
                                                >
                                                    Rechazar
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Botón para estudiantes -->
                        <div v-if="isStudent && semillero.estado === 'activo'" class="text-center">
                            <div v-if="!hasPendingRequest && !isMember">
                                <button
                                    @click="requestJoin"
                                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200"
                                >
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Solicitar Unirse al Semillero
                                </button>
                            </div>
                            <div v-else-if="hasPendingRequest" class="bg-yellow-100 text-yellow-800 font-medium py-3 px-6 rounded-lg inline-block">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Solicitud Pendiente de Aprobación
                            </div>
                            <div v-else-if="isMember" class="bg-green-100 text-green-800 font-medium py-3 px-6 rounded-lg inline-block">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Eres miembro de este semillero
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>





