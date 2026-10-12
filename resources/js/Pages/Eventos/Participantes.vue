<template>
    <Head :title="`Participantes - ${evento.titulo}`" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center mb-4">
                        <Link :href="route('eventos.index')" class="text-gray-600 hover:text-gray-900 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900">Participantes del Evento</h1>
                            <p class="text-gray-600 mt-2">{{ evento.titulo }}</p>
                        </div>
                    </div>
                </div>

                <!-- Información del Evento -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Total de Inscritos</p>
                            <p class="text-2xl font-bold text-gray-900">{{ participantes.length }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Fecha del Evento</p>
                            <p class="text-lg font-semibold text-gray-900">{{ formatDate(evento.fecha_inicio) }}</p>
                        </div>
                        <div v-if="evento.cupo_maximo">
                            <p class="text-sm text-gray-500">Cupos Disponibles</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ evento.cupo_maximo - participantes.length }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar estudiante</label>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por nombre o email..."
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
                                <option value="inscrito">Inscrito</option>
                                <option value="asistio">Asistió</option>
                                <option value="no_asistio">No Asistió</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Lista de Participantes -->
                <div v-if="filteredParticipantes.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
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
                                    Fecha Inscripción
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Observaciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="participante in filteredParticipantes" :key="participante.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <Avatar :usuario="participante.usuario" tamano="h-10 w-10" />
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ participante.usuario.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ participante.usuario.email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ formatDate(participante.created_at) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getEstadoBadgeClass(participante.estado)" class="px-3 py-1 text-xs font-medium rounded-full">
                                        {{ participante.estado_texto || participante.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500">
                                        {{ participante.observaciones || '-' }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mensaje cuando no hay participantes -->
                <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay participantes</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ search || selectedEstado ? 'No se encontraron participantes con los filtros aplicados.' : 'Aún no hay estudiantes inscritos en este evento.' }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    evento: Object,
    participantes: Array,
    auth: Object,
});

const search = ref('');
const selectedEstado = ref('');

const filteredParticipantes = computed(() => {
    let filtered = props.participantes || [];

    if (search.value) {
        const searchLower = search.value.toLowerCase();
        filtered = filtered.filter(participante =>
            participante.usuario.name.toLowerCase().includes(searchLower) ||
            participante.usuario.email.toLowerCase().includes(searchLower)
        );
    }

    if (selectedEstado.value) {
        filtered = filtered.filter(participante => participante.estado === selectedEstado.value);
    }

    return filtered;
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const getEstadoBadgeClass = (estado) => {
    const classes = {
        'inscrito': 'bg-blue-100 text-blue-800',
        'asistio': 'bg-green-100 text-green-800',
        'no_asistio': 'bg-red-100 text-red-800',
        'cancelado': 'bg-gray-100 text-gray-800',
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};
</script>










