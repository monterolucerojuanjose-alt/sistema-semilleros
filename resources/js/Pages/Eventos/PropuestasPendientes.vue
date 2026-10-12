<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    propuestas: { type: Array, default: () => [] },
});

const formatDate = (date) => {
    if (!date) return 'Sin registrar';
    return new Date(date).toLocaleDateString('es-ES', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

// Las propuestas corregidas suben primero: es lo que más urge revisar, porque
// el estudiante ya está esperando una segunda respuesta.
const ordenadas = computed(() =>
    [...props.propuestas].sort((a, b) => {
        const correccionA = (a.intentos_envio ?? 1) > 1 ? 1 : 0;
        const correccionB = (b.intentos_envio ?? 1) > 1 ? 1 : 0;
        if (correccionA !== correccionB) return correccionB - correccionA;
        return new Date(a.created_at) - new Date(b.created_at);
    })
);
</script>

<template>
    <Head title="Propuestas pendientes" />

    <AppLayout title="Propuestas pendientes">
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Encabezado -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Propuestas pendientes de revisión</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Aprueba o rechaza las propuestas de proyecto que los estudiantes enviaron a los eventos.
                </p>
            </div>

            <!-- Listado -->
            <div v-if="ordenadas.length" class="space-y-3">
                <Link
                    v-for="(propuesta, i) in ordenadas" :key="propuesta.id"
                    :href="route('propuestas.ver', propuesta.id)"
                    :class="['tarjeta-interactiva animar-entrada block rounded-2xl border bg-white p-5 shadow-sm', `retraso-${Math.min(i + 1, 6)}`,
                             (propuesta.intentos_envio ?? 1) > 1 ? 'border-violet-200' : 'border-gray-200']"
                >
                    <div class="flex flex-wrap items-start gap-4">
                        <Avatar :usuario="propuesta.usuario" tamano="h-11 w-11" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate text-base font-semibold text-gray-900">{{ propuesta.titulo_proyecto }}</h3>
                                <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                    Pendiente
                                </span>
                                <span
                                    v-if="(propuesta.intentos_envio ?? 1) > 1"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-semibold text-violet-700"
                                >
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Corregida · envío {{ propuesta.intentos_envio }}
                                </span>
                            </div>

                            <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ propuesta.usuario?.name }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ propuesta.evento?.titulo ?? 'Evento eliminado' }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ formatDate(propuesta.created_at) }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ propuesta.participantes?.length || 0 }} integrante(s)
                                </span>
                            </div>

                            <p class="mt-2.5 line-clamp-2 text-sm text-gray-600">
                                {{ propuesta.descripcion_proyecto }}
                            </p>
                        </div>

                        <svg class="mt-2 h-5 w-5 shrink-0 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </Link>
            </div>

            <!-- Sin propuestas -->
            <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
                    <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-gray-900">No hay propuestas pendientes</h3>
                <p class="mt-1 text-sm text-gray-500">Todas las propuestas enviadas ya fueron revisadas.</p>
            </div>
        </div>
    </AppLayout>
</template>
