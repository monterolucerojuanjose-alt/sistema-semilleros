<template>
    <AppLayout title="Emitir certificado">
        <div class="mx-auto max-w-3xl">
            <div class="animar-entrada mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <Link :href="route('certificados.index')" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        Certificados
                    </Link>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900">Emitir certificado</h1>
                    <p class="mt-1 text-sm text-gray-500">Completa los datos y el sistema generará el documento oficial.</p>
                </div>
            </div>

            <form @submit.prevent="emitir" class="animar-entrada retraso-1">
                <FormularioCertificado :form="form" :estudiantes="estudiantes" :eventos="eventos" :tipos="tipos" />

                <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/95 px-4 py-4 backdrop-blur sm:mx-0 sm:flex-row sm:justify-end sm:rounded-xl sm:border sm:px-5">
                    <Link :href="route('certificados.index')" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" /></svg>
                        {{ form.processing ? 'Emitiendo…' : 'Emitir certificado' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormularioCertificado from '@/Components/Certificados/FormularioCertificado.vue';

defineProps({
    estudiantes: Array,
    eventos: Array,
    tipos: Object,
});

// Fecha local (no toISOString, que usa UTC y de noche ya marca el día siguiente).
const hoy = new Date();
const fechaHoy = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;

const form = useForm({
    titulo: '',
    descripcion: '',
    tipo: '',
    fecha_emision: fechaHoy,
    user_id: '',
    evento_id: '',
    horas: '',
    observaciones: '',
    archivo_certificado: null,
});

const emitir = () => {
    form.post(route('certificados.store'), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>
