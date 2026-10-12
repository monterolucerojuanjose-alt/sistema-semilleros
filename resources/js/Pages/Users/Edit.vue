<template>
    <Head :title="`Editar Usuario: ${user.name}`" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center">
                    <Link :href="route('users.index')" class="text-gray-600 hover:text-gray-900 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Editar Usuario</h1>
                        <p class="text-sm text-gray-600">Modificar información de {{ user.name }}</p>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
                            <!-- Nombre -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre Completo <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    id="name"
                                    required
                                    maxlength="255"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: Juan Pérez"
                                />
                                <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Correo Electrónico <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    id="email"
                                    required
                                    maxlength="255"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: juan.perez@ejemplo.com"
                                />
                                <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                            </div>

                            <!-- Rol -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                    Rol <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.role"
                                    id="role"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Seleccione un rol</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">El rol determina los permisos y accesos del usuario</p>
                                <div v-if="form.errors.role" class="text-red-600 text-sm mt-1">{{ form.errors.role }}</div>
                            </div>

                            <!-- Estado -->
                            <div>
                                <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                                    Estado <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.estado"
                                    id="estado"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <div v-if="form.errors.estado" class="text-red-600 text-sm mt-1">{{ form.errors.estado }}</div>
                            </div>

                            <!-- Información adicional -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-blue-800">Nota:</p>
                                        <p class="text-sm text-blue-700">Para cambiar la contraseña del usuario, use el botón "Resetear Contraseña" en la lista de usuarios.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="mt-8 flex justify-end space-x-3">
                            <Link
                                :href="route('users.index')"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md font-medium transition-colors duration-200"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition-colors duration-200 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: Object,
    roles: Array,
    auth: Object,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.roles && props.user.roles.length > 0 ? props.user.roles[0].name : '',
    estado: props.user.estado,
});

const submit = () => {
    form.put(route('users.update', props.user.id));
};
</script>
