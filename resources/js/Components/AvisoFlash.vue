<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Aviso de confirmación o error que deja el servidor tras una acción
 * (->with('success'|'error'|'info', …)). Una acción que funciona sin decirlo
 * se percibe como rota, así que toda pantalla de destino debe pintarlo.
 */
const page = usePage();

const aviso = computed(() => {
    const flash = page.props.flash ?? {};
    if (flash.error) return { tipo: 'error', texto: flash.error };
    if (flash.success || flash.exito) return { tipo: 'exito', texto: flash.success || flash.exito };
    if (flash.info) return { tipo: 'info', texto: flash.info };
    return null;
});

const estilos = {
    exito: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    error: 'border-red-200 bg-red-50 text-red-800',
    info: 'border-blue-200 bg-blue-50 text-blue-800',
};

const iconos = {
    exito: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    error: 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};
</script>

<template>
    <div
        v-if="aviso"
        role="status"
        :class="['animar-escala flex items-start gap-3 rounded-xl border px-4 py-3 text-sm', estilos[aviso.tipo]]"
    >
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" :d="iconos[aviso.tipo]" />
        </svg>
        <p class="min-w-0 break-words">{{ aviso.texto }}</p>
    </div>
</template>
