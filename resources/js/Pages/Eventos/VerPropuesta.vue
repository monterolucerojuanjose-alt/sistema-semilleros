<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import axios from 'axios';

const props = defineProps({
    propuesta: { type: Object, required: true },
});

const evaluadores = ref([]);

const ESTADOS = {
    pendiente: { texto: 'Pendiente de revisión', clase: 'bg-amber-100 text-amber-800' },
    aprobado: { texto: 'Aprobada', clase: 'bg-emerald-100 text-emerald-800' },
    rechazado: { texto: 'Rechazada', clase: 'bg-red-100 text-red-800' },
};
const estado = computed(() => ESTADOS[props.propuesta.estado] ?? { texto: props.propuesta.estado_texto, clase: 'bg-gray-100 text-gray-700' });

// Distingue un primer envío de una propuesta ya corregida, para que el
// revisor sepa de un vistazo si esto ya pasó por aquí antes.
const esCorreccion = computed(() => (props.propuesta.intentos_envio ?? 1) > 1);

const CARRERAS = {
    'Nutricion': 'Nutrición',
    'Enfermeria': 'Enfermería',
    'Ingenieria de Sistemas': 'Ingeniería de Sistemas',
    'Radiologia': 'Radiología',
    'Ingenieria Ambiental': 'Ingeniería Ambiental',
    'Mecatronica': 'Mecatrónica',
    'Derecho': 'Derecho',
    'Terapia Ocupacional': 'Terapia Ocupacional',
};
const nombreCarrera = (c) => CARRERAS[c] ?? c;

const formatDate = (date) => {
    if (!date) return 'Sin registrar';
    return new Date(date).toLocaleDateString('es-ES', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const formAprobar = useForm({
    programacion: '',
    aula: '',
    horario: '',
    evaluador_id: '',
});

const formRechazar = useForm({
    motivo_rechazo: '',
});

const aprobar = () => {
    if (confirm('¿Confirmas aprobar esta propuesta? Se le notificará al estudiante y al evaluador asignado.')) {
        formAprobar.post(route('propuestas.aprobar', props.propuesta.id), {
            onSuccess: () => formAprobar.reset(),
        });
    }
};

const rechazar = () => {
    if (confirm('¿Confirmas rechazar esta propuesta? El estudiante podrá corregirla y enviarla de nuevo.')) {
        formRechazar.post(route('propuestas.rechazar', props.propuesta.id), {
            onSuccess: () => formRechazar.reset(),
        });
    }
};

onMounted(async () => {
    try {
        const { data } = await axios.get(route('eventos.evaluadores'));
        evaluadores.value = data;
    } catch {
        evaluadores.value = [];
    }
});
</script>

<template>
    <Head :title="`Propuesta: ${propuesta.titulo_proyecto}`" />

    <AppLayout title="Detalle de propuesta">
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Volver al listado -->
            <Link
                :href="route('propuestas.pendientes')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Volver a propuestas pendientes
            </Link>

            <!-- Encabezado -->
            <section class="animar-entrada overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-6 text-white">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-100">
                                {{ propuesta.evento?.titulo ?? 'Evento eliminado' }}
                            </p>
                            <h1 class="mt-1 text-2xl font-bold">{{ propuesta.titulo_proyecto }}</h1>
                            <p v-if="propuesta.modalidad || propuesta.area_participacion" class="mt-1 text-sm text-indigo-100">
                                {{ [propuesta.modalidad, propuesta.area_participacion].filter(Boolean).join(' · ') }}
                            </p>
                            <p v-else-if="propuesta.carrera" class="mt-1 text-sm text-indigo-100">
                                {{ nombreCarrera(propuesta.carrera) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span :class="['rounded-full px-3 py-1 text-xs font-semibold', estado.clase]">
                            {{ estado.texto }}
                        </span>
                        <span
                            v-if="esCorreccion"
                            class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Corregida · envío {{ propuesta.intentos_envio }}
                        </span>
                    </div>
                </div>

                <!-- Estudiante y fecha -->
                <div class="grid grid-cols-1 gap-6 px-6 py-5 md:grid-cols-2">
                    <div>
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Estudiante</h2>
                        <div class="flex items-center gap-3">
                            <Avatar :usuario="propuesta.usuario" tamano="h-11 w-11" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ propuesta.usuario?.name ?? 'Usuario eliminado' }}
                                </p>
                                <p class="truncate text-xs text-gray-500">{{ propuesta.usuario?.email }}</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Envío</h2>
                        <p class="text-sm text-gray-900">{{ formatDate(propuesta.created_at) }}</p>
                    </div>
                </div>
            </section>

            <!-- Motivo del rechazo anterior: visible mientras vuelve a estar pendiente -->
            <section
                v-if="esCorreccion && propuesta.estado === 'pendiente' && propuesta.motivo_rechazo"
                class="animar-entrada rounded-2xl border border-amber-200 bg-amber-50 p-5"
            >
                <h2 class="flex items-center gap-2 text-sm font-semibold text-amber-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    Motivo del rechazo anterior
                </h2>
                <p class="mt-1.5 whitespace-pre-line break-words text-sm text-amber-800">{{ propuesta.motivo_rechazo }}</p>
                <p class="mt-2 text-xs text-amber-700">
                    El estudiante corrigió y envió esta propuesta de nuevo; revisa si atendió la observación.
                </p>
            </section>

            <!-- Propuesta -->
            <section class="animar-entrada rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold text-gray-900">Propuesta presentada</h2>
                <p class="whitespace-pre-line break-words text-sm leading-relaxed text-gray-700">
                    {{ propuesta.descripcion_proyecto }}
                </p>

                <a
                    v-if="propuesta.archivo_proyecto"
                    :href="`/storage/${propuesta.archivo_proyecto}`"
                    target="_blank"
                    rel="noopener"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Descargar el archivo del proyecto
                </a>
            </section>

            <!-- Integrantes -->
            <section v-if="propuesta.participantes?.length" class="animar-entrada rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold text-gray-900">Integrantes ({{ propuesta.participantes.length }})</h2>
                <ul class="divide-y divide-gray-100">
                    <li
                        v-for="(persona, i) in propuesta.participantes" :key="i"
                        class="flex flex-wrap items-center justify-between gap-2 py-2.5"
                    >
                        <span class="text-sm text-gray-900">{{ persona.nombre }}</span>
                        <span class="text-xs text-gray-500">{{ persona.email }}</span>
                    </li>
                </ul>
            </section>

            <!-- Panel de decisión -->
            <section v-if="propuesta.estado === 'pendiente'" class="animar-entrada space-y-6">
                <!-- Aprobar -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <h2 class="font-semibold text-gray-900">Aprobar propuesta</h2>
                    </div>

                    <form @submit.prevent="aprobar" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">Aula asignada</label>
                                <input
                                    v-model="formAprobar.aula" type="text" required maxlength="100"
                                    placeholder="Ej: Auditorio Principal"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="formAprobar.errors.aula" class="mt-1 text-xs text-red-600">{{ formAprobar.errors.aula }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">Horario de ponencia</label>
                                <input
                                    v-model="formAprobar.horario" type="text" required maxlength="255"
                                    placeholder="Ej: Martes 15, 2:00 - 3:00 PM"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="formAprobar.errors.horario" class="mt-1 text-xs text-red-600">{{ formAprobar.errors.horario }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Programación / detalles</label>
                            <textarea
                                v-model="formAprobar.programacion" required maxlength="1000" rows="3"
                                placeholder="Requisitos técnicos, orden de presentación, tiempo asignado…"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <p v-if="formAprobar.errors.programacion" class="mt-1 text-xs text-red-600">{{ formAprobar.errors.programacion }}</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Evaluador (jurado o docente)</label>
                            <select
                                v-model="formAprobar.evaluador_id" required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Selecciona un evaluador</option>
                                <option v-for="ev in evaluadores" :key="ev.id" :value="ev.id">{{ ev.name }} ({{ ev.email }})</option>
                            </select>
                            <p v-if="formAprobar.errors.evaluador_id" class="mt-1 text-xs text-red-600">{{ formAprobar.errors.evaluador_id }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="formAprobar.processing"
                            class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {{ formAprobar.processing ? 'Procesando…' : 'Aprobar y asignar evaluador' }}
                        </button>
                    </form>
                </div>

                <!-- Rechazar -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50">
                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </span>
                        <h2 class="font-semibold text-gray-900">Rechazar propuesta</h2>
                    </div>

                    <form @submit.prevent="rechazar" class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Motivo del rechazo</label>
                            <textarea
                                v-model="formRechazar.motivo_rechazo" required maxlength="1000" rows="4"
                                placeholder="Sé específico: el estudiante podrá corregir esto y volver a enviar su propuesta."
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                El estudiante recibirá esta observación y podrá enviar una versión corregida.
                            </p>
                            <p v-if="formRechazar.errors.motivo_rechazo" class="mt-1 text-xs text-red-600">{{ formRechazar.errors.motivo_rechazo }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="formRechazar.processing"
                            class="w-full rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition-colors hover:bg-red-50 disabled:opacity-50"
                        >
                            {{ formRechazar.processing ? 'Procesando…' : 'Rechazar y enviar retroalimentación' }}
                        </button>
                    </form>
                </div>
            </section>

            <!-- Ya resuelta -->
            <section v-else class="animar-entrada rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 font-semibold text-gray-900">Información de la revisión</h2>

                <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div v-if="propuesta.revisor">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Revisado por</p>
                        <p class="mt-0.5 text-gray-900">{{ propuesta.revisor.name }}</p>
                    </div>
                    <div v-if="propuesta.fecha_revision">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Fecha de revisión</p>
                        <p class="mt-0.5 text-gray-900">{{ formatDate(propuesta.fecha_revision) }}</p>
                    </div>
                </div>

                <div v-if="propuesta.estado === 'rechazado' && propuesta.motivo_rechazo" class="mt-4 rounded-lg bg-red-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-red-700">Motivo del rechazo</p>
                    <p class="mt-1 whitespace-pre-line break-words text-sm text-red-800">{{ propuesta.motivo_rechazo }}</p>
                    <p class="mt-2 text-xs text-red-600">
                        El estudiante puede corregir su propuesta y enviarla de nuevo desde el evento.
                    </p>
                </div>

                <div v-if="propuesta.estado === 'aprobado'" class="mt-4 grid grid-cols-1 gap-4 rounded-lg bg-emerald-50 p-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Aula</p>
                        <p class="mt-0.5 text-sm font-semibold text-emerald-900">{{ propuesta.aula }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Horario</p>
                        <p class="mt-0.5 text-sm font-semibold text-emerald-900">{{ propuesta.horario }}</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
