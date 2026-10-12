<template>
    <Head title="Emitir Certificado" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Emitir Certificado</h1>
                            <p class="text-gray-600 mt-2">Crea un nuevo certificado para un estudiante</p>
                        </div>
                <Link
                    :href="route('certificados.index')"
                            class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200"
                >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                            Volver
                </Link>
                    </div>
            </div>

                <!-- Formulario -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <form @submit.prevent="submit">
                        <div class="p-6 space-y-6">
                            <!-- Información básica -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Certificado</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Título -->
                                    <div class="md:col-span-2">
                                        <label for="titulo" class="block text-sm font-medium text-gray-700 mb-2">
                                            Título del Certificado *
                                        </label>
                                        <input
                                            id="titulo"
                                            v-model="form.titulo"
                                            type="text"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            :class="{ 'border-red-500': form.errors.titulo }"
                                            placeholder="Ej: Certificado de Participación en Evento"
                                        />
                                        <div v-if="form.errors.titulo" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.titulo }}
                                        </div>
                                    </div>

                                    <!-- Descripción -->
                                    <div class="md:col-span-2">
                                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                                            Descripción
                                        </label>
                                        <textarea
                                            id="descripcion"
                                            v-model="form.descripcion"
                                            rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            :class="{ 'border-red-500': form.errors.descripcion }"
                                            placeholder="Descripción del logro o participación..."
                                        ></textarea>
                                        <div v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.descripcion }}
                                        </div>
                                    </div>

                                    <!-- Tipo -->
                                    <div>
                                        <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2">
                                            Tipo de Certificado *
                                        </label>
                                        <select
                                            id="tipo"
                                            v-model="form.tipo"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            :class="{ 'border-red-500': form.errors.tipo }"
                                        >
                                            <option value="">Seleccionar tipo...</option>
                                            <option value="participacion">Participación</option>
                                            <option value="ponencia">Ponencia</option>
                                            <option value="poster">Póster</option>
                                            <option value="organizacion">Organización</option>
                                            <option value="voluntariado">Voluntariado</option>
                                        </select>
                                        <div v-if="form.errors.tipo" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.tipo }}
                                        </div>
                            </div>

                                    <!-- Fecha de emisión -->
                                    <div>
                                        <label for="fecha_emision" class="block text-sm font-medium text-gray-700 mb-2">
                                            Fecha de Emisión *
                                        </label>
                                        <input
                                            id="fecha_emision"
                                            v-model="form.fecha_emision"
                                            type="date"
                                            required
                                            :max="today"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            :class="{ 'border-red-500': form.errors.fecha_emision }"
                                        />
                                        <div v-if="form.errors.fecha_emision" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.fecha_emision }}
                                                </div>
                                            </div>
                                            </div>
                                        </div>

                            <!-- Destinatario -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Destinatario</h3>

                                            <div>
                                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Estudiante *
                                    </label>
                                                <select
                                        id="user_id"
                                        v-model="form.user_id"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="{ 'border-red-500': form.errors.user_id }"
                                    >
                                        <option value="">Seleccionar estudiante...</option>
                                        <option v-for="estudiante in estudiantes" :key="estudiante.id" :value="estudiante.id">
                                            {{ estudiante.name }} - {{ estudiante.email }}
                                        </option>
                                                </select>
                                    <div v-if="form.errors.user_id" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.user_id }}
                                    </div>
                                </div>
                                            </div>

                            <!-- Evento relacionado -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Evento Relacionado</h3>

                                <div>
                                    <label for="evento_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Evento (Opcional)
                                    </label>
                                                <select
                                        id="evento_id"
                                        v-model="form.evento_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="{ 'border-red-500': form.errors.evento_id }"
                                    >
                                        <option value="">Sin evento específico</option>
                                        <option v-for="evento in eventos" :key="evento.id" :value="evento.id">
                                            {{ evento.titulo }}
                                                    </option>
                                                </select>
                                    <div v-if="form.errors.evento_id" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.evento_id }}
                                    </div>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="pb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Información Adicional</h3>

                                <div class="space-y-6">
                                    <!-- Observaciones -->
                                    <div>
                                        <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-2">
                                            Observaciones
                                        </label>
                                        <textarea
                                            id="observaciones"
                                            v-model="form.observaciones"
                                            rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            :class="{ 'border-red-500': form.errors.observaciones }"
                                            placeholder="Observaciones adicionales sobre el certificado..."
                                        ></textarea>
                                        <div v-if="form.errors.observaciones" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.observaciones }}
                                        </div>
                                            </div>

                                    <!-- Archivo PDF del Certificado -->
                                    <div>
                                        <label for="archivo_certificado" class="block text-sm font-medium text-gray-700 mb-2">
                                            Archivo PDF del Certificado
                                        </label>
                                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors duration-200">
                                            <div class="space-y-1 text-center">
                                                <svg v-if="!form.archivo_certificado" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <div v-if="!form.archivo_certificado" class="flex text-sm text-gray-600">
                                                    <label for="archivo_certificado" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                        <span>Subir archivo PDF</span>
                                                        <input
                                                            id="archivo_certificado"
                                                            ref="archivoInput"
                                                            @change="handleFileChange"
                                                            type="file"
                                                            accept=".pdf"
                                                            class="sr-only"
                                                        />
                                                    </label>
                                                    <p class="pl-1">o arrastra y suelta aquí</p>
                                                </div>
                                                <div v-else class="text-sm text-gray-600">
                                                    <p class="font-medium text-green-600">{{ form.archivo_certificado.name }}</p>
                                                    <p class="text-xs text-gray-500">Archivo PDF seleccionado</p>
                                            <button
                                                        @click="removeFile"
                                                type="button"
                                                        class="mt-2 text-red-600 hover:text-red-500 text-xs"
                                            >
                                                        Remover archivo
                                            </button>
                                        </div>
                                                <p class="text-xs text-gray-500">
                                                    PDF hasta 10MB
                                                </p>
                                    </div>
                                </div>
                                        <div v-if="form.errors.archivo_certificado" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.archivo_certificado }}
                                </div>
                            </div>

                                    <!-- Metadata -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Información Adicional (JSON)
                                        </label>
                                        <textarea
                                            v-model="metadataText"
                                            rows="4"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                                            placeholder='{"horas": 40, "creditos": 2, "nota": "Excelente"}'
                                        ></textarea>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Formato JSON para información adicional como horas, créditos, etc.
                                        </p>
                                        <div v-if="form.errors.metadata" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.metadata }}
                                        </div>
                                    </div>
                            </div>
                        </div>
                    </div>

                        <!-- Botones -->
                        <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end space-x-3">
                        <Link
                            :href="route('certificados.index')"
                                class="px-6 py-2 border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium rounded-lg transition-colors duration-200"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Creando...' : 'Emitir Certificado' }}
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    estudiantes: Array,
    eventos: Array,
    auth: Object,
});

const form = useForm({
    titulo: '',
    descripcion: '',
    tipo: '',
    fecha_emision: new Date().toISOString().split('T')[0],
    user_id: '',
    evento_id: '',
    metadata: {},
    observaciones: '',
    archivo_certificado: null,
});

const metadataText = ref('');
const archivoInput = ref(null);

const today = computed(() => {
    return new Date().toISOString().split('T')[0];
});

// Convertir metadata de texto a objeto
watch(metadataText, (newValue) => {
    try {
        if (newValue.trim()) {
            form.metadata = JSON.parse(newValue);
        } else {
            form.metadata = {};
        }
    } catch (error) {
        // Si hay error en el JSON, mantener el valor anterior
        console.warn('Error parsing metadata JSON:', error);
    }
});

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validar tipo de archivo
        if (file.type !== 'application/pdf') {
            alert('Por favor, selecciona un archivo PDF válido.');
            return;
        }

        // Validar tamaño (10MB máximo)
        if (file.size > 10 * 1024 * 1024) {
            alert('El archivo es demasiado grande. El tamaño máximo permitido es 10MB.');
            return;
        }

        form.archivo_certificado = file;
    }
};

const removeFile = () => {
    form.archivo_certificado = null;
    if (archivoInput.value) {
        archivoInput.value.value = '';
    }
};

const submit = () => {
    // Asegurar que metadata esté definido antes de enviar
    if (!form.metadata) {
        form.metadata = {};
    }

    // Si metadataText está vacío, asegurar que metadata sea un objeto vacío
    if (!metadataText.value.trim()) {
        form.metadata = {};
    }

    form.post(route('certificados.store'), {
        preserveScroll: true,
        forceFormData: true, // Importante para archivos
    });
};
</script>
