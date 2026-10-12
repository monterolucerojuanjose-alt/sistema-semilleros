<template>
    <Head title="Editar Evento" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center mb-4">
                        <Link :href="route('eventos.index')" class="text-gray-600 hover:text-gray-900 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Editar Evento</h1>
                            <p class="text-gray-600 mt-2">Modifica la información del evento</p>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <form @submit.prevent="submit">
                        <div class="space-y-6">
                            <!-- Título -->
                            <div>
                                <label for="titulo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Título del Evento <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.titulo"
                                    type="text"
                                    id="titulo"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <div v-if="form.errors.titulo" class="text-red-600 text-sm mt-1">{{ form.errors.titulo }}</div>
                            </div>

                            <!-- Descripción -->
                            <div>
                                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                                    Descripción <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.descripcion"
                                    id="descripcion"
                                    required
                                    rows="5"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                ></textarea>
                                <div v-if="form.errors.descripcion" class="text-red-600 text-sm mt-1">{{ form.errors.descripcion }}</div>
                            </div>

                            <!-- Imagen Actual -->
                            <div v-if="evento.imagen && !imagePreview">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Imagen Actual
                                </label>
                                <img :src="`/storage/${evento.imagen}`" alt="Imagen actual" class="max-w-xs rounded-lg shadow-sm">
                            </div>

                            <!-- Nueva Imagen -->
                            <div>
                                <label for="imagen" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ evento.imagen ? 'Cambiar Imagen' : 'Imagen del Evento' }}
                                </label>
                                <input
                                    type="file"
                                    id="imagen"
                                    accept="image/*"
                                    @change="handleImageChange"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p class="text-sm text-gray-500 mt-1">Formatos: JPG, PNG, WEBP. Máximo 2MB.</p>
                                <div v-if="form.errors.imagen" class="text-red-600 text-sm mt-1">{{ form.errors.imagen }}</div>

                                <!-- Preview de la nueva imagen -->
                                <div v-if="imagePreview" class="mt-4">
                                    <img :src="imagePreview" alt="Preview" class="max-w-xs rounded-lg shadow-sm">
                                </div>
                            </div>

                            <!-- Precio -->
                            <div>
                                <label for="precio" class="block text-sm font-medium text-gray-700 mb-2">
                                    Precio <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                                    <input
                                        v-model.number="form.precio"
                                        type="number"
                                        id="precio"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                                <div v-if="form.errors.precio" class="text-red-600 text-sm mt-1">{{ form.errors.precio }}</div>
                            </div>

                            <!-- Fechas -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-2">
                                        Fecha y Hora de Inicio <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.fecha_inicio"
                                        type="datetime-local"
                                        id="fecha_inicio"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                    <div v-if="form.errors.fecha_inicio" class="text-red-600 text-sm mt-1">{{ form.errors.fecha_inicio }}</div>
                                </div>

                                <div>
                                    <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-2">
                                        Fecha y Hora de Fin
                                    </label>
                                    <input
                                        v-model="form.fecha_fin"
                                        type="datetime-local"
                                        id="fecha_fin"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                    <div v-if="form.errors.fecha_fin" class="text-red-600 text-sm mt-1">{{ form.errors.fecha_fin }}</div>
                                </div>
                            </div>

                            <!-- Ubicación -->
                            <div>
                                <label for="ubicacion" class="block text-sm font-medium text-gray-700 mb-2">
                                    Ubicación
                                </label>
                                <input
                                    v-model="form.ubicacion"
                                    type="text"
                                    id="ubicacion"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <div v-if="form.errors.ubicacion" class="text-red-600 text-sm mt-1">{{ form.errors.ubicacion }}</div>
                            </div>

                            <!-- Cupo Máximo -->
                            <div>
                                <label for="cupo_maximo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Cupo Máximo
                                </label>
                                <input
                                    v-model.number="form.cupo_maximo"
                                    type="number"
                                    id="cupo_maximo"
                                    min="1"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p class="text-sm text-gray-500 mt-1">Deja vacío si no hay límite de participantes</p>
                                <div v-if="form.errors.cupo_maximo" class="text-red-600 text-sm mt-1">{{ form.errors.cupo_maximo }}</div>
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
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                    <option value="finalizado">Finalizado</option>
                                </select>
                                <div v-if="form.errors.estado" class="text-red-600 text-sm mt-1">{{ form.errors.estado }}</div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="mt-8 flex justify-end space-x-3">
                            <Link
                                :href="route('eventos.index')"
                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-md font-medium transition-colors duration-200"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200 disabled:opacity-50"
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
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    evento: Object,
    auth: Object,
});

// Formatear las fechas para datetime-local
const formatDateForInput = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toISOString().slice(0, 16);
};

const form = useForm({
    titulo: props.evento.titulo || '',
    descripcion: props.evento.descripcion || '',
    imagen: null,
    precio: props.evento.precio || 0,
    fecha_inicio: formatDateForInput(props.evento.fecha_inicio) || '',
    fecha_fin: formatDateForInput(props.evento.fecha_fin) || '',
    ubicacion: props.evento.ubicacion || '',
    cupo_maximo: props.evento.cupo_maximo || '',
    estado: props.evento.estado || 'activo',
});

const imagePreview = ref(null);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.imagen = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    form.transform((data) => {
        const datos = { ...data, _method: 'PUT' };

        // Si no se eligió una imagen nueva, no se envía el campo: al mandarlo
        // vacío el servidor sobrescribiría la imagen actual del evento.
        if (!(datos.imagen instanceof File)) {
            delete datos.imagen;
        }

        return datos;
    }).post(route('eventos.update', props.evento.id), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>



