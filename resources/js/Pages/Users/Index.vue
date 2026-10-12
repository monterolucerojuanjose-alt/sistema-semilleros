<template>
    <Head title="Gestión de Usuarios" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestión de Usuarios
                </h2>
                <Link
                    :href="route('users.create')"
                    class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Crear Usuario
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Mensajes de éxito/error -->
                <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Estadísticas por Rol -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div v-for="(count, roleName) in roleStats" :key="roleName" class="tarjeta-interactiva animar-entrada rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">{{ roleName }}</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ count }}</p>
                            </div>
                            <div :class="getRoleIconClass(roleName)" class="w-10 h-10 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                                <input
                                    v-model="search"
                                    type="text"
                                placeholder="Nombre o email..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                                <select
                                    v-model="selectedRole"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Todos los roles</option>
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select
                                v-model="selectedEstado"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Todos los estados</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md font-medium transition-colors duration-200"
                            >
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                        </div>

                <!-- Tabla de Usuarios -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usuario
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rol
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha Registro
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <Avatar :usuario="user" tamano="h-10 w-10" />
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ user.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                            v-if="user.roles && user.roles.length > 0"
                                            :class="getRoleBadgeClass(user.roles[0].name)"
                                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                            {{ user.roles[0].name }}
                                        </span>
                                        <span v-else class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Sin rol
                                            </span>
                                        </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button
                                            @click="toggleEstado(user)"
                                            :class="getEstadoBadgeClass(user.estado)"
                                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer hover:opacity-80"
                                        >
                                            {{ user.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <Link
                                                :href="route('users.edit', user.id)"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </Link>
                                            <button
                                                @click="showResetPasswordModal(user)"
                                                class="text-yellow-600 hover:text-yellow-900"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteUser(user)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                    <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Mostrando <span class="font-medium">{{ users.from }}</span> a <span class="font-medium">{{ users.to }}</span> de <span class="font-medium">{{ users.total }}</span> usuarios
                            </div>
                            <div class="flex space-x-2">
                                <template v-for="(link, index) in users.links" :key="index">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        v-html="link.label"
                                        :class="[
                                            'px-3 py-2 text-sm rounded-md',
                                            link.active
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                                        ]"
                                    />
                                    <span
                                        v-else
                                        v-html="link.label"
                                        class="px-3 py-2 text-sm rounded-md bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Resetear Contraseña -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closePasswordModal"></div>

                <!-- Modal Content -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="resetPassword">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4" id="modal-title">
                                        Resetear Contraseña
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-4">
                                        Cambiar contraseña de: <strong>{{ selectedUser?.name }}</strong>
                                    </p>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña</label>
                                            <input
                                                v-model="passwordForm.password"
                                                type="password"
                                                required
                                                minlength="8"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Mínimo 8 caracteres"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña</label>
                                            <input
                                                v-model="passwordForm.password_confirmation"
                                                type="password"
                                                required
                                                minlength="8"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Confirme la contraseña"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            >
                                {{ passwordForm.processing ? 'Guardando...' : 'Cambiar Contraseña' }}
                            </button>
                            <button
                                type="button"
                                @click="closePasswordModal"
                                :disabled="passwordForm.processing"
                                class="mt-3 w-full sm:mt-0 sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    users: Object,
    roles: Array,
    roleStats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedRole = ref(props.filters?.role || '');
const selectedEstado = ref(props.filters?.estado || '');

const showPasswordModal = ref(false);
const selectedUser = ref(null);

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

// Watch para aplicar filtros
let searchTimeout;
watch([search, selectedRole, selectedEstado], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('users.index'), {
            search: search.value,
            role: selectedRole.value,
            estado: selectedEstado.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
});

const clearFilters = () => {
    search.value = '';
    selectedRole.value = '';
    selectedEstado.value = '';
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const getRoleBadgeClass = (roleName) => {
    const classes = {
        'Administrador': 'bg-purple-100 text-purple-800',
        'Coordinador': 'bg-blue-100 text-blue-800',
        'Docente/Tutor': 'bg-green-100 text-green-800',
        'Estudiante': 'bg-yellow-100 text-yellow-800',
        'Jurado': 'bg-red-100 text-red-800',
    };
    return classes[roleName] || 'bg-gray-100 text-gray-800';
};

const getRoleIconClass = (roleName) => {
    const classes = {
        'Administrador': 'bg-purple-100 text-purple-600',
        'Coordinador': 'bg-blue-100 text-blue-600',
        'Docente/Tutor': 'bg-green-100 text-green-600',
        'Estudiante': 'bg-yellow-100 text-yellow-600',
        'Jurado': 'bg-red-100 text-red-600',
    };
    return classes[roleName] || 'bg-gray-100 text-gray-600';
};

const getEstadoBadgeClass = (estado) => {
    return estado === 'activo'
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const toggleEstado = (user) => {
    const accion = user.estado === 'activo' ? 'desactivar' : 'activar';
    if (confirm(`¿Estás seguro de ${accion} a ${user.name}?`)) {
        router.post(route('users.toggle-estado', user.id), {}, {
            preserveScroll: true,
        });
    }
};

const showResetPasswordModal = (user) => {
    selectedUser.value = user;
    showPasswordModal.value = true;
    passwordForm.reset();
};

const closePasswordModal = () => {
    showPasswordModal.value = false;
    selectedUser.value = null;
    passwordForm.reset();
};

const resetPassword = () => {
    if (passwordForm.password !== passwordForm.password_confirmation) {
        alert('Las contraseñas no coinciden.');
        return;
    }

    passwordForm.post(route('users.reset-password', selectedUser.value.id), {
        onSuccess: () => {
            closePasswordModal();
        },
        preserveScroll: true,
    });
};

const deleteUser = (user) => {
    if (confirm(`¿Estás seguro de eliminar a ${user.name}?\n\nEsta acción no se puede deshacer.`)) {
        router.delete(route('users.destroy', user.id), {
            preserveScroll: true,
        });
    }
};
</script>
