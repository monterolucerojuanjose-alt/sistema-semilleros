<template>
    <AppLayout :title="certificado.titulo">
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Encabezado -->
            <div class="animar-entrada flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <Link :href="volverA" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        {{ puedeGestionar ? 'Certificados' : 'Mis certificados' }}
                    </Link>
                    <h1 class="mt-1 break-words text-2xl font-bold text-gray-900">{{ certificado.titulo }}</h1>
                    <p class="mt-1 font-mono text-sm text-gray-500">{{ certificado.codigo }}</p>
                </div>

                <div v-if="disponible" class="flex shrink-0 flex-col gap-2 sm:flex-row">
                    <a
                        :href="route('certificados.descargar', { certificado: certificado.id, ver: 1 })"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Ver certificado
                    </a>
                    <a
                        :href="route('certificados.descargar', certificado.id)"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar PDF
                    </a>
                </div>
            </div>

            <AvisoFlash />

            <div v-if="revocado" class="animar-entrada flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                <p>
                    <strong>Certificado revocado.</strong>
                    {{ puedeGestionar ? 'Su PDF se genera con la marca «REVOCADO» y el estudiante ya no puede descargarlo.' : 'Este certificado fue anulado y ya no está disponible para descarga.' }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <!-- Vista del documento -->
                <section class="animar-entrada retraso-1 lg:col-span-3">
                    <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-2 shadow-sm">
                        <div class="relative rounded-lg border-2 border-blue-900 p-1">
                            <div class="rounded border border-amber-600/70 px-5 pb-6 pt-8 text-center sm:px-10">
                                <div class="absolute inset-x-3 top-3 h-1.5 rounded-t bg-blue-900"></div>
                                <p class="text-xs font-bold tracking-[0.25em] text-blue-900">UNIVERSIDAD MARIANA</p>
                                <p class="mt-0.5 text-[10px] uppercase tracking-wider text-gray-400">Sistema de Gestión de Semilleros de Investigación</p>

                                <p class="mt-6 text-2xl font-bold tracking-[0.2em] text-gray-900 sm:text-3xl">CERTIFICADO</p>
                                <p class="text-xs uppercase tracking-[0.25em] text-amber-700">de {{ tipoTexto.toLowerCase() }}</p>

                                <p class="mt-6 text-xs text-gray-500">Se otorga el presente certificado a</p>
                                <p class="mt-1 break-words text-xl font-bold text-blue-900 sm:text-2xl">{{ certificado.usuario?.name }}</p>
                                <div class="mx-auto mt-2 h-px w-3/4 bg-amber-600/60"></div>

                                <p class="mx-auto mt-4 max-w-md text-sm leading-relaxed text-gray-700">
                                    {{ motivo }}
                                    <template v-if="certificado.evento">el evento <strong>«{{ certificado.evento.titulo }}»</strong></template>
                                    <strong v-else>{{ certificado.titulo }}</strong><template v-if="horas">, con una intensidad de <strong>{{ horas }} {{ horas === 1 ? 'hora' : 'horas' }}</strong></template>.
                                </p>
                                <p v-if="certificado.descripcion" class="mx-auto mt-2 max-w-md break-words text-xs leading-relaxed text-gray-500">{{ certificado.descripcion }}</p>
                                <p class="mt-4 text-xs text-gray-500">Dado en San Juan de Pasto, el {{ fechaLarga(certificado.fecha_emision) }}.</p>
                            </div>
                        </div>
                        <div v-if="revocado" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <span class="-rotate-12 text-5xl font-black tracking-widest text-red-600/20 sm:text-6xl">REVOCADO</span>
                        </div>
                    </div>
                    <p class="mt-2 text-center text-xs text-gray-500">
                        {{ tieneArchivoPropio ? 'Este certificado se entrega con un PDF adjuntado por quien lo emitió.' : 'Vista resumida. El PDF descargable incluye la firma y el código de verificación.' }}
                    </p>
                </section>

                <!-- Datos -->
                <aside class="animar-entrada retraso-2 space-y-4 lg:col-span-2">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Detalles</h2>
                        <dl class="mt-4 space-y-4 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Estado</dt>
                                <dd><span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', revocado ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800']">{{ estados[certificado.estado] ?? certificado.estado }}</span></dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Tipo</dt>
                                <dd class="font-medium text-gray-900">{{ tipoTexto }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Fecha de emisión</dt>
                                <dd class="text-right font-medium text-gray-900">{{ fechaLarga(certificado.fecha_emision) }}</dd>
                            </div>
                            <div v-if="horas" class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Intensidad horaria</dt>
                                <dd class="font-medium text-gray-900">{{ horas }} {{ horas === 1 ? 'hora' : 'horas' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Evento</dt>
                                <dd class="mt-0.5 break-words font-medium text-gray-900">{{ certificado.evento?.titulo ?? 'Sin evento (reconocimiento general)' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Personas</h2>
                        <div class="mt-4 space-y-4">
                            <div class="flex items-center gap-3">
                                <Avatar :usuario="certificado.usuario" tamano="h-10 w-10" />
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-500">Destinatario</p>
                                    <p class="truncate text-sm font-medium text-gray-900">{{ certificado.usuario?.name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ certificado.usuario?.email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Avatar :usuario="certificado.emisor" tamano="h-10 w-10" />
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-500">Emitido por</p>
                                    <p class="truncate text-sm font-medium text-gray-900">{{ certificado.emisor?.name ?? 'Sin registro' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="puedeGestionar && certificado.observaciones" class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                        <h2 class="text-sm font-semibold text-amber-900">Observaciones internas</h2>
                        <p class="mt-1 whitespace-pre-line break-words text-sm text-amber-800">{{ certificado.observaciones }}</p>
                    </div>

                    <div v-if="puedeGestionar" class="flex gap-3">
                        <Link
                            :href="route('certificados.edit', certificado.id)"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Editar
                        </Link>
                        <button
                            type="button"
                            @click="eliminar"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Eliminar
                        </button>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';
import AvisoFlash from '@/Components/AvisoFlash.vue';

const props = defineProps({
    certificado: Object,
    tipos: Object,
    estados: Object,
    puedeGestionar: Boolean,
    tieneArchivoPropio: Boolean,
});

const MOTIVOS = {
    participacion: 'Por su participación en',
    ponencia: 'Por la presentación de su ponencia en',
    poster: 'Por la presentación de su trabajo en modalidad póster en',
    organizacion: 'Por su apoyo en la organización de',
    voluntariado: 'Por su labor de voluntariado en',
};

const revocado = computed(() => props.certificado.estado === 'revocado');
// El titular de un certificado revocado ya no puede descargarlo.
const disponible = computed(() => !revocado.value || props.puedeGestionar);
const tipoTexto = computed(() => props.tipos?.[props.certificado.tipo] ?? 'Reconocimiento');
const motivo = computed(() => MOTIVOS[props.certificado.tipo] ?? 'Por su participación en');
const horas = computed(() => Number(props.certificado.metadata?.horas) || 0);
const volverA = computed(() => (props.puedeGestionar ? route('certificados.index') : route('mis-certificados')));

// La fecha llega como «2026-09-25T00:00:00Z»; se lee sólo la parte de la fecha
// para que la zona horaria no la corra al día anterior.
const fechaLarga = (valor) => {
    if (!valor) return '';
    const [anio, mes, dia] = String(valor).slice(0, 10).split('-').map(Number);
    return new Date(anio, mes - 1, dia).toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' });
};

const eliminar = () => {
    if (confirm(`¿Eliminar el certificado «${props.certificado.titulo}» de ${props.certificado.usuario?.name}? Esta acción no se puede deshacer.`)) {
        router.delete(route('certificados.destroy', props.certificado.id));
    }
};
</script>
