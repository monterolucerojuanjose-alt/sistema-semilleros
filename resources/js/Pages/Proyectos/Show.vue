<template>
    <Head :title="proyecto.titulo" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center">
                            <Link :href="route('proyectos.index')" class="mr-4 shrink-0 text-gray-500 hover:text-gray-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Link>
                            <div class="min-w-0">
                                <h1 class="break-words text-3xl font-bold text-gray-900">{{ proyecto.titulo }}</h1>
                                <p class="mt-2 text-gray-600">{{ proyecto.codigo }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center space-x-3">
                            <span :class="getEstadoBadgeClass(proyecto.estado)" class="rounded-full px-3 py-1 text-sm font-medium">
                                {{ proyecto.estado }}
                            </span>
                            <Link
                                v-if="canEdit"
                                :href="route('proyectos.edit', proyecto.id)"
                                class="rounded-md bg-yellow-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-yellow-700"
                            >
                                Editar
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

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <!-- min-w-0: sin esto una URL o una palabra muy larga en el
                         resumen ensancha la columna y descuadra las dos columnas. -->
                    <div class="min-w-0 lg:col-span-2">
                        <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <h2 class="text-lg font-semibold text-gray-900">Informacion del Proyecto</h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">Resumen</label>
                                        <!-- break-words parte las palabras/URLs demasiado largas
                                             para que el texto no se salga de la tarjeta. -->
                                        <p class="whitespace-pre-line break-words text-gray-600">{{ proyecto.resumen }}</p>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Autor</label>
                                            <p class="text-gray-600">{{ proyecto.autor?.name || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Semillero</label>
                                            <p class="text-gray-600">{{ proyecto.semillero?.nombre || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Fecha de creacion</label>
                                            <p class="text-gray-600">{{ formatDate(proyecto.created_at) }}</p>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Ultima actualizacion</label>
                                            <p class="text-gray-600">{{ formatDate(proyecto.updated_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-gray-900">Versiones del Proyecto</h2>
                                    <Link :href="route('proyectos.history', proyecto.id)" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                        Ver historial completo
                                    </Link>
                                </div>
                            </div>
                            <div class="p-6">
                                <div v-if="proyecto.versiones && proyecto.versiones.length > 0" class="space-y-4">
                                    <div
                                        v-for="version in proyecto.versiones.slice(0, 3)"
                                        :key="version.id"
                                        class="rounded-lg border border-gray-200 p-4"
                                    >
                                        <div class="mb-2 flex items-center justify-between">
                                            <div class="flex items-center">
                                                <svg class="mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="font-medium text-gray-900">{{ version.nombre_archivo }}</span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm text-gray-500">{{ formatDate(version.created_at) }}</span>
                                                <a
                                                    :href="route('versiones.download', version.id)"
                                                    target="_blank"
                                                    class="rounded bg-blue-600 px-3 py-1 text-xs font-medium text-white transition-colors duration-200 hover:bg-blue-700"
                                                >
                                                    Descargar
                                                </a>
                                                <button
                                                    @click="confirmDeleteVersion(version.id, version.nombre_archivo)"
                                                    class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white transition-colors duration-200 hover:bg-red-700"
                                                >
                                                    Eliminar
                                                </button>
                                            </div>
                                        </div>
                                        <p v-if="version.comentario" class="mt-2 text-sm text-gray-600">{{ version.comentario }}</p>
                                    </div>
                                </div>
                                <div v-else class="py-8 text-center">
                                    <svg class="mx-auto mb-4 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-gray-500">No hay versiones disponibles</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <h3 class="text-lg font-semibold text-gray-900">Acciones</h3>
                            </div>
                            <div class="space-y-3 p-6">
                                <button
                                    v-if="canUploadVersion"
                                    @click="showUploadModal = true"
                                    class="flex w-full items-center justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-green-700"
                                >
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    Subir Nueva Version
                                </button>

                                <Link
                                    :href="route('proyectos.history', proyecto.id)"
                                    class="flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700"
                                >
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Ver Historial
                                </Link>
                            </div>
                        </div>

                        <div v-if="canChangeStatus" class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <h3 class="text-lg font-semibold text-gray-900">Cambiar Estado</h3>
                            </div>
                            <div class="p-6">
                                <form @submit.prevent="changeStatus">
                                    <select
                                        v-model="statusForm.estado"
                                        class="mb-3 w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="Inscrito">Inscrito</option>
                                        <option value="En revisión">En revisión</option>
                                        <option value="Aprobado">Aprobado</option>
                                        <option value="Rechazado">Rechazado</option>
                                    </select>
                                    <button
                                        type="submit"
                                        :disabled="statusForm.processing"
                                        class="w-full rounded-md bg-yellow-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-yellow-700 disabled:bg-yellow-400"
                                    >
                                        {{ statusForm.processing ? 'Actualizando...' : 'Actualizar Estado' }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-6 py-4">
                                <h3 class="text-lg font-semibold text-gray-900">Estadisticas</h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Versiones</span>
                                        <span class="text-sm font-medium text-gray-900">{{ proyecto.versiones?.length || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Dias desde creacion</span>
                                        <span class="text-sm font-medium text-gray-900">{{ getDaysSinceCreation() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showUploadModal" class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
            <div class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg">
                <div class="mt-3">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Subir Nueva Version</h3>
                    <form @submit.prevent="uploadVersion">
                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Archivo</label>
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".pdf,.doc,.docx"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">Formatos permitidos: PDF, DOC, DOCX (max. 10MB)</p>
                        </div>
                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Comentario (opcional)</label>
                            <textarea
                                v-model="uploadForm.comentario"
                                rows="3"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Describe los cambios en esta version..."
                            />
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="showUploadModal = false"
                                class="rounded-md bg-gray-500 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-gray-600"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="uploadForm.processing"
                                class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-green-700 disabled:bg-green-400"
                            >
                                {{ uploadForm.processing ? 'Subiendo...' : 'Subir' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    proyecto: Object,
    auth: Object,
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const showUploadModal = ref(false);
const feedbackMessage = ref(props.flash?.success || '');
const errorMessage = ref('');

const uploadForm = useForm({
    archivo: null,
    comentario: '',
});

const statusForm = useForm({
    estado: props.proyecto.estado,
});

const canEdit = computed(() => {
    const user = props.auth?.user;
    if (!user) return false;

    if (user.roles?.some((role) => ['Administrador', 'Coordinador'].includes(role.name))) {
        return true;
    }

    return props.proyecto.autor_id === user.id;
});

const canUploadVersion = computed(() => {
    const user = props.auth?.user;
    if (!user) return false;

    if (user.roles?.some((role) => ['Administrador', 'Coordinador'].includes(role.name))) {
        return true;
    }

    return props.proyecto.autor_id === user.id;
});

const canChangeStatus = computed(() => {
    const user = props.auth?.user;
    if (!user) return false;

    return user.roles?.some((role) => ['Administrador', 'Coordinador'].includes(role.name));
});

const getEstadoBadgeClass = (estado) => {
    const classes = {
        Inscrito: 'bg-blue-100 text-blue-800',
        'En revisión': 'bg-yellow-100 text-yellow-800',
        Aprobado: 'bg-green-100 text-green-800',
        Rechazado: 'bg-red-100 text-red-800',
    };

    return classes[estado] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

const getDaysSinceCreation = () => {
    const created = new Date(props.proyecto.created_at);
    const now = new Date();
    const diffTime = Math.abs(now - created);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

const uploadVersion = () => {
    const fileInput = document.querySelector('input[type="file"]');
    if (!fileInput?.files?.length) {
        return;
    }

    errorMessage.value = '';
    feedbackMessage.value = '';
    uploadForm.archivo = fileInput.files[0];

    uploadForm.post(route('proyectos.upload-version', props.proyecto.id), {
        onSuccess: () => {
            showUploadModal.value = false;
            uploadForm.reset();
            feedbackMessage.value = 'Version subida exitosamente.';
        },
        onError: () => {
            errorMessage.value = 'No se pudo subir la version.';
        },
    });
};

const changeStatus = () => {
    errorMessage.value = '';
    feedbackMessage.value = '';

    statusForm.put(route('proyectos.change-status', props.proyecto.id), {
        onSuccess: () => {
            feedbackMessage.value = 'Estado actualizado exitosamente.';
        },
        onError: () => {
            errorMessage.value = 'No se pudo actualizar el estado.';
        },
    });
};

const confirmDeleteVersion = (versionId, fileName) => {
    if (confirm(`¿Estas seguro de que quieres eliminar la version "${fileName}"?\n\nEsta accion no se puede deshacer.`)) {
        deleteVersion(versionId);
    }
};

const deleteVersion = (versionId) => {
    errorMessage.value = '';
    feedbackMessage.value = '';

    router.delete(route('versiones.delete', versionId), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMessage.value = 'Version eliminada exitosamente.';
            router.reload({ only: ['proyecto', 'flash'] });
        },
        onError: () => {
            errorMessage.value = 'No se pudo eliminar la version.';
        },
    });
};
</script>
