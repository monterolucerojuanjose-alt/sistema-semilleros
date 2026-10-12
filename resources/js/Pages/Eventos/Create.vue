<template>
    <Head title="Crear Evento" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="mb-8">
                    <div class="mb-4 flex items-center">
                        <Link :href="route('eventos.index')" class="mr-4 text-gray-600 hover:text-gray-900">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Crear Nuevo Evento</h1>
                            <p class="mt-2 text-gray-600">Completa la informacion del evento academico</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
                            <div>
                                <label for="titulo" class="mb-2 block text-sm font-medium text-gray-700">
                                    Titulo del Evento <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="titulo"
                                    v-model="form.titulo"
                                    type="text"
                                    required
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: Congreso Nacional de Investigacion 2025"
                                />
                                <div v-if="form.errors.titulo" class="mt-1 text-sm text-red-600">{{ form.errors.titulo }}</div>
                            </div>

                            <div>
                                <label for="descripcion" class="mb-2 block text-sm font-medium text-gray-700">
                                    Descripcion <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="descripcion"
                                    v-model="form.descripcion"
                                    required
                                    rows="5"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Describe el evento, actividades, objetivos, etc."
                                />
                                <div v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">{{ form.errors.descripcion }}</div>
                            </div>

                            <div>
                                <label for="imagen" class="mb-2 block text-sm font-medium text-gray-700">
                                    Imagen del Evento
                                </label>
                                <input
                                    id="imagen"
                                    type="file"
                                    accept="image/*"
                                    @change="handleImageChange"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                                <p class="mt-1 text-sm text-gray-500">Formatos: JPG, PNG, WEBP. Maximo 2MB.</p>
                                <div v-if="form.errors.imagen" class="mt-1 text-sm text-red-600">{{ form.errors.imagen }}</div>

                                <div v-if="imagePreview" class="mt-4">
                                    <img :src="imagePreview" alt="Preview" class="max-w-xs rounded-lg shadow-sm">
                                </div>
                            </div>

                            <div>
                                <label for="precio" class="mb-2 block text-sm font-medium text-gray-700">
                                    Precio <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                                    <input
                                        id="precio"
                                        v-model.number="form.precio"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="w-full rounded-md border border-gray-300 py-2 pl-8 pr-3 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="0.00"
                                    />
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Ingresa 0 para eventos gratuitos</p>
                                <div v-if="form.errors.precio" class="mt-1 text-sm text-red-600">{{ form.errors.precio }}</div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label for="fecha_inicio" class="mb-2 block text-sm font-medium text-gray-700">
                                        Fecha y Hora de Inicio <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="fecha_inicio"
                                        v-model="form.fecha_inicio"
                                        type="datetime-local"
                                        required
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                    <div v-if="form.errors.fecha_inicio" class="mt-1 text-sm text-red-600">{{ form.errors.fecha_inicio }}</div>
                                </div>

                                <div>
                                    <label for="fecha_fin" class="mb-2 block text-sm font-medium text-gray-700">
                                        Fecha y Hora de Fin
                                    </label>
                                    <input
                                        id="fecha_fin"
                                        v-model="form.fecha_fin"
                                        type="datetime-local"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                    <div v-if="form.errors.fecha_fin" class="mt-1 text-sm text-red-600">{{ form.errors.fecha_fin }}</div>
                                </div>
                            </div>

                            <div>
                                <label for="ubicacion" class="mb-2 block text-sm font-medium text-gray-700">
                                    Ubicacion
                                </label>
                                <input
                                    id="ubicacion"
                                    v-model="form.ubicacion"
                                    type="text"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: Auditorio Principal - Campus Central"
                                />
                                <div v-if="form.errors.ubicacion" class="mt-1 text-sm text-red-600">{{ form.errors.ubicacion }}</div>
                            </div>

                            <div>
                                <label for="cupo_maximo" class="mb-2 block text-sm font-medium text-gray-700">
                                    Cupo Maximo
                                </label>
                                <input
                                    id="cupo_maximo"
                                    v-model.number="form.cupo_maximo"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Deja en blanco para cupos ilimitados"
                                />
                                <p class="mt-1 text-sm text-gray-500">Deja vacio si no hay limite de participantes</p>
                                <div v-if="form.errors.cupo_maximo" class="mt-1 text-sm text-red-600">{{ form.errors.cupo_maximo }}</div>
                            </div>

                            <div>
                                <label for="estado" class="mb-2 block text-sm font-medium text-gray-700">
                                    Estado <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="estado"
                                    v-model="form.estado"
                                    required
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                    <option value="finalizado">Finalizado</option>
                                </select>
                                <div v-if="form.errors.estado" class="mt-1 text-sm text-red-600">{{ form.errors.estado }}</div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-3">
                            <Link
                                :href="route('eventos.index')"
                                class="rounded-md bg-gray-100 px-6 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-md bg-green-600 px-6 py-2 font-medium text-white transition-colors duration-200 hover:bg-green-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Creando...' : 'Crear Evento' }}
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
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    auth: Object,
});

const form = useForm({
    titulo: '',
    descripcion: '',
    imagen: null,
    precio: 0,
    fecha_inicio: '',
    fecha_fin: '',
    ubicacion: '',
    cupo_maximo: '',
    estado: 'activo',
});

const imagePreview = ref(null);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (!file) {
        return;
    }

    form.imagen = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const submit = () => {
    form.post(route('eventos.store'), {
        forceFormData: true,
    });
};
</script>
