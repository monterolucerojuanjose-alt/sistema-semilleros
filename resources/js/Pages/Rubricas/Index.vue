<template>
    <AppLayout title="Gestión de Rúbricas" :auth="page.props.auth">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestión de Rúbricas
                </h2>
                <Link
                    v-if="canCreate"
                    :href="route('rubricas.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Nueva Rúbrica
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Mensajes: sin esto, "Se creó una copia…" o "No se puede
                     eliminar…" pasaban sin que nadie los viera. -->
                <div v-if="page.props.flash?.success" class="mb-6 rounded-lg border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                    {{ page.props.flash.success }}
                </div>
                <div v-if="page.props.flash?.error" class="mb-6 rounded-lg border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                    {{ page.props.flash.error }}
                </div>

                <!-- Lista de rúbricas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="rubricas.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay rúbricas</h3>
                            <p class="mt-1 text-sm text-gray-500">Comienza creando tu primera rúbrica de evaluación.</p>
                            <div v-if="canCreate" class="mt-6">
                                <Link
                                    :href="route('rubricas.create')"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Nueva Rúbrica
                                </Link>
                            </div>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="rubrica in rubricas"
                                :key="rubrica.id"
                                class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow duration-200"
                            >
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ rubrica.nombre }}</h3>
                                    <span
                                        :class="rubrica.completa ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                        class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{ rubrica.completa ? 'Completa' : 'Incompleta' }}
                                    </span>
                                </div>

                                <p v-if="rubrica.evento || rubrica.modalidad" class="mb-2 text-xs font-medium text-indigo-700">
                                    {{ [rubrica.evento?.titulo, rubrica.modalidad].filter(Boolean).join(' · ') }}
                                </p>
                                <p v-else class="mb-2 text-xs font-medium text-gray-400">Genérica</p>

                                <p v-if="rubrica.descripcion" class="text-sm text-gray-600 mb-4 line-clamp-2">
                                    {{ rubrica.descripcion }}
                                </p>

                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Criterios:</span>
                                        <span class="font-medium">{{ rubrica.criterios.length }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Total pesos:</span>
                                        <span class="font-medium">{{ rubrica.total_pesos }}%</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Evaluaciones:</span>
                                        <span class="font-medium">{{ rubrica.evaluaciones_count }}</span>
                                    </div>
                                </div>

                                <!-- Barra de progreso para pesos -->
                                <div class="mb-4">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Pesos</span>
                                        <span>{{ rubrica.total_pesos }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            :class="rubrica.completa ? 'bg-green-500' : 'bg-yellow-500'"
                                            class="h-2 rounded-full transition-all duration-300"
                                            :style="{ width: Math.min(rubrica.total_pesos, 100) + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Criterios -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Criterios:</h4>
                                    <div class="space-y-1">
                                        <div
                                            v-for="criterio in rubrica.criterios.slice(0, 3)"
                                            :key="criterio.id"
                                            class="flex justify-between text-xs"
                                        >
                                            <span class="text-gray-600 truncate">{{ criterio.criterio }}</span>
                                            <span class="font-medium">{{ criterio.peso }}%</span>
                                        </div>
                                        <div v-if="rubrica.criterios.length > 3" class="text-xs text-gray-500">
                                            +{{ rubrica.criterios.length - 3 }} más...
                                        </div>
                                    </div>
                                </div>

                                <!-- Aviso cuando los criterios ya no se pueden tocar -->
                                <p
                                    v-if="canEdit && rubrica.evaluaciones_count > 0"
                                    class="mb-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800"
                                >
                                    Ya calificó proyectos: sus criterios quedaron fijos. Puedes cambiar el nombre o duplicarla para ajustar los pesos.
                                </p>

                                <!-- Botones de acción -->
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        :href="route('rubricas.show', rubrica.id)"
                                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center py-2 px-3 rounded-md text-sm font-medium transition-colors duration-200"
                                    >
                                        Ver
                                    </Link>
                                    <Link
                                        v-if="canEdit"
                                        :href="route('rubricas.edit', rubrica.id)"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-3 rounded-md text-sm font-medium transition-colors duration-200"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        v-if="canCreate && rubrica.evaluaciones_count > 0"
                                        @click="duplicar(rubrica)"
                                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-center py-2 px-3 rounded-md text-sm font-medium transition-colors duration-200"
                                    >
                                        Duplicar
                                    </button>
                                    <button
                                        v-if="canDelete && rubrica.evaluaciones_count === 0"
                                        @click="confirmDelete(rubrica)"
                                        class="bg-red-600 hover:bg-red-700 text-white py-2 px-3 rounded-md text-sm font-medium transition-colors duration-200"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación de eliminación -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDeleteModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Eliminar rúbrica
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        ¿Estás seguro de que quieres eliminar la rúbrica "{{ rubricaToDelete?.nombre }}"? Esta acción no se puede deshacer.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="deleteRubrica"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Eliminar
                        </button>
                        <button
                            @click="showDeleteModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    rubricas: Array,
});

const page = usePage();

// Estados reactivos
const showDeleteModal = ref(false);
const rubricaToDelete = ref(null);

// Permisos
const canCreate = computed(() => {
    return page.props.auth?.user?.roles?.some(role =>
        ['Administrador', 'Coordinador'].includes(role.name)
    );
});

const canEdit = computed(() => {
    return page.props.auth?.user?.roles?.some(role =>
        ['Administrador', 'Coordinador'].includes(role.name)
    );
});

const canDelete = computed(() => {
    return page.props.auth?.user?.roles?.some(role =>
        ['Administrador', 'Coordinador'].includes(role.name)
    );
});

// Métodos
const duplicar = (rubrica) => {
    router.post(route('rubricas.duplicar', rubrica.id));
};

const confirmDelete = (rubrica) => {
    rubricaToDelete.value = rubrica;
    showDeleteModal.value = true;
};

const deleteRubrica = () => {
    if (rubricaToDelete.value) {
        router.delete(route('rubricas.destroy', rubricaToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                rubricaToDelete.value = null;
            },
        });
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
