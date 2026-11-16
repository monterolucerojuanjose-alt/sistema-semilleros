<template>
    <AppLayout title="Mis certificados">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="animar-entrada">
                <h1 class="text-2xl font-bold text-gray-900">Mis certificados</h1>
                <p class="mt-1 text-sm text-gray-500">Constancias de tu participación en eventos y actividades de investigación.</p>
            </div>

            <AvisoFlash />

            <!-- Resumen y filtros (sólo si hay algo que filtrar) -->
            <div v-if="resumen?.total > 0" class="animar-entrada flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:flex-row md:items-center">
                <p class="shrink-0 text-sm text-gray-600">
                    <span class="text-lg font-bold text-gray-900">{{ resumen.vigentes }}</span>
                    {{ resumen.vigentes === 1 ? 'certificado vigente' : 'certificados vigentes' }}
                </p>
                <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3 md:ml-auto md:max-w-xl">
                    <div class="relative sm:col-span-2">
                        <label for="buscar" class="sr-only">Buscar</label>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input id="buscar" v-model="buscar" type="search" placeholder="Buscar por título, código o evento" class="campo pl-9" />
                    </div>
                    <div>
                        <label for="tipo" class="sr-only">Tipo</label>
                        <select id="tipo" v-model="tipo" class="campo">
                            <option value="">Todos los tipos</option>
                            <option v-for="(etiqueta, valor) in tipos" :key="valor" :value="valor">{{ etiqueta }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tarjetas -->
            <div v-if="certificados.data.length" class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="(c, i) in certificados.data"
                    :key="c.id"
                    :class="['animar-entrada tarjeta-interactiva flex flex-col overflow-hidden rounded-xl border bg-white shadow-sm', c.estado === 'revocado' ? 'border-red-200' : 'border-gray-200', `retraso-${(i % 6) + 1}`]"
                >
                    <!-- Franja tipo diploma -->
                    <div :class="['relative px-5 pb-5 pt-6 text-center', c.estado === 'revocado' ? 'bg-gray-50' : 'bg-gradient-to-br from-blue-900 to-indigo-800']">
                        <p :class="['text-[10px] font-bold tracking-[0.25em]', c.estado === 'revocado' ? 'text-gray-400' : 'text-blue-200']">CERTIFICADO</p>
                        <p :class="['mt-1 text-sm font-semibold uppercase tracking-wider', c.estado === 'revocado' ? 'text-gray-500' : 'text-amber-300']">de {{ (tipos[c.tipo] ?? c.tipo).toLowerCase() }}</p>
                        <span v-if="c.estado === 'revocado'" class="absolute right-3 top-3 rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">Revocado</span>
                    </div>

                    <div class="flex flex-1 flex-col p-5">
                        <h2 class="line-clamp-2 break-words text-base font-semibold text-gray-900">{{ c.titulo }}</h2>
                        <dl class="mt-3 space-y-1.5 text-sm text-gray-600">
                            <div v-if="c.evento" class="flex items-start gap-2">
                                <dt class="sr-only">Evento</dt>
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <dd class="min-w-0 break-words">{{ c.evento.titulo }}</dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <dt class="sr-only">Fecha de emisión</dt>
                                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <dd>Emitido el {{ fechaLarga(c.fecha_emision) }}</dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <dt class="sr-only">Código</dt>
                                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
                                <dd class="font-mono text-xs">{{ c.codigo }}</dd>
                            </div>
                        </dl>

                        <div class="mt-auto flex gap-2 pt-5">
                            <Link :href="route('certificados.show', c.id)" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Ver detalle
                            </Link>
                            <a
                                v-if="c.estado !== 'revocado'"
                                :href="route('certificados.descargar', c.id)"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Descargar
                            </a>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Sin certificados -->
            <div v-else class="animar-entrada rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50">
                    <svg class="h-8 w-8 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /></svg>
                </span>
                <h3 class="mt-4 text-base font-semibold text-gray-900">{{ hayFiltros ? 'Ningún certificado coincide' : 'Aún no tienes certificados' }}</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">
                    {{ hayFiltros ? 'Prueba con otra búsqueda.' : 'Aparecerán aquí cuando la coordinación los emita por tu participación en eventos y actividades.' }}
                </p>
                <Link v-if="!hayFiltros" :href="route('eventos.index')" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Ver eventos disponibles
                </Link>
            </div>

            <Pagination
                v-if="certificados.last_page > 1"
                :links="certificados.links"
                :from="certificados.from"
                :to="certificados.to"
                :total="certificados.total"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AvisoFlash from '@/Components/AvisoFlash.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    certificados: Object,
    tipos: Object,
    estados: Object,
    filtros: Object,
    resumen: Object,
});

const buscar = ref(props.filtros?.buscar ?? '');
const tipo = ref(props.filtros?.tipo ?? '');
const hayFiltros = computed(() => Boolean(buscar.value || tipo.value));

const aplicar = () => {
    router.get(route('mis-certificados'), {
        buscar: buscar.value || undefined,
        tipo: tipo.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

let espera;
watch(buscar, () => { clearTimeout(espera); espera = setTimeout(aplicar, 350); });
watch(tipo, aplicar);

const fechaLarga = (valor) => {
    if (!valor) return '';
    const [a, m, d] = String(valor).slice(0, 10).split('-').map(Number);
    return new Date(a, m - 1, d).toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<style scoped>
.campo {
    @apply block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
}
</style>
