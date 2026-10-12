<template>
    <Head title="Evaluar proyectos" />

    <AppLayout title="Evaluar proyectos" :auth="auth">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Evaluar proyectos</h2>
                    <p class="text-sm text-gray-500">Proyectos de semillero aprobados que todavía no has calificado.</p>
                </div>
                <Link
                    :href="route('evaluaciones.index')"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    Mis evaluaciones
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Mensaje de error (p. ej. rol sin permiso) -->
            <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ error }}
            </div>

            <!-- Sin proyectos -->
            <div v-if="proyectos.length === 0 && !error" class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay proyectos asignados</h3>
                <p class="mt-1 text-sm text-gray-500">No tienes proyectos pendientes de evaluación en este momento.</p>
            </div>

            <!-- Listado -->
            <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="(proyecto, i) in proyectos" :key="proyecto.id"
                    :class="['animar-entrada flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm', `retraso-${Math.min(i + 1, 6)}`]"
                >
                    <div class="flex-1 p-5">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="line-clamp-2 text-base font-semibold text-gray-900">{{ proyecto.titulo }}</h3>
                            <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                                Aprobado
                            </span>
                        </div>

                        <dl class="mt-3 space-y-1 text-sm">
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-500">Código</dt>
                                <dd class="font-mono text-xs text-gray-700">{{ proyecto.codigo }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-500">Autor</dt>
                                <dd class="truncate font-medium text-gray-900">{{ proyecto.autor.name }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-gray-500">Semillero</dt>
                                <dd class="truncate font-medium text-gray-900">{{ proyecto.semillero.nombre }}</dd>
                            </div>
                        </dl>

                        <p class="mt-3 line-clamp-3 text-sm text-gray-600">{{ proyecto.resumen }}</p>
                    </div>

                    <div class="flex gap-2 border-t border-gray-100 bg-gray-50 px-5 py-3">
                        <Link
                            :href="route('proyectos.show', proyecto.id)"
                            class="flex-1 rounded-lg border border-gray-300 bg-white py-1.5 text-center text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Ver proyecto
                        </Link>
                        <button
                            @click="showRubricaModal(proyecto)"
                            class="flex-1 rounded-lg bg-indigo-600 py-1.5 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                        >
                            Evaluar
                        </button>
                    </div>
                </article>
            </div>
        </div>

        <!-- Modal de selección de rúbrica -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50" @click="closeModal"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-start gap-4 p-6">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900">Seleccionar rúbrica</h3>
                        <p class="mt-0.5 break-words text-sm text-gray-500">
                            ¿Con qué rúbrica vas a calificar «{{ selectedProyecto?.titulo }}»?
                        </p>
                    </div>
                </div>

                <div class="px-6 pb-2">
                    <label for="rubrica" class="mb-2 block text-sm font-medium text-gray-700">Rúbrica de evaluación *</label>
                    <select
                        id="rubrica"
                        v-model="selectedRubricaId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Selecciona una rúbrica</option>
                        <option v-for="rubrica in rubricas" :key="rubrica.id" :value="rubrica.id">
                            {{ rubrica.nombre }} ({{ rubrica.criterios.length }} criterios)
                        </option>
                    </select>

                    <div v-if="rubricaSeleccionadaDescripcion" class="mt-3 rounded-lg border border-indigo-100 bg-indigo-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Cómo calificar con esta rúbrica</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-indigo-900">{{ rubricaSeleccionadaDescripcion }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 bg-gray-50 px-6 py-4">
                    <button
                        @click="closeModal"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="startEvaluacion"
                        :disabled="!selectedRubricaId"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Continuar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    proyectos: Array,
    rubricas: Array,
    error: String,
    auth: Object,
});

const showModal = ref(false);
const selectedProyecto = ref(null);
const selectedRubricaId = ref('');

const rubricaSeleccionadaDescripcion = computed(() => {
    return props.rubricas.find((r) => r.id === selectedRubricaId.value)?.descripcion ?? '';
});

const showRubricaModal = (proyecto) => {
    selectedProyecto.value = proyecto;
    selectedRubricaId.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedProyecto.value = null;
    selectedRubricaId.value = '';
};

const startEvaluacion = () => {
    if (selectedProyecto.value && selectedRubricaId.value) {
        router.get(route('evaluaciones.create'), {
            proyecto_id: selectedProyecto.value.id,
            rubrica_id: selectedRubricaId.value
        });
    }
};
</script>
