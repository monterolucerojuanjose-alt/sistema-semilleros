<template>
    <AppLayout title="Certificados">
        <div class="mx-auto max-w-7xl space-y-6">
            <!-- Encabezado -->
            <div class="animar-entrada flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestión de certificados</h1>
                    <p class="mt-1 text-sm text-gray-500">Emite, consulta y revoca los certificados de los estudiantes.</p>
                </div>
                <Link
                    :href="route('certificados.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Emitir certificado
                </Link>
            </div>

            <AvisoFlash />

            <!-- Indicadores -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    v-for="(tarjeta, i) in tarjetas"
                    :key="tarjeta.etiqueta"
                    :class="['animar-entrada rounded-xl border border-gray-200 bg-white p-4 shadow-sm', `retraso-${i + 1}`]"
                >
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ tarjeta.etiqueta }}</p>
                    <p :class="['mt-1 text-2xl font-bold tabular-nums', tarjeta.color]">{{ tarjeta.valor }}</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="animar-entrada rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                    <div class="relative md:col-span-6">
                        <label for="buscar" class="sr-only">Buscar</label>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input id="buscar" v-model="buscar" type="search" placeholder="Buscar por título, código, estudiante o evento" class="campo pl-9" />
                    </div>
                    <div class="md:col-span-3">
                        <label for="tipo" class="sr-only">Tipo</label>
                        <select id="tipo" v-model="tipo" class="campo">
                            <option value="">Todos los tipos</option>
                            <option v-for="(etiqueta, valor) in tipos" :key="valor" :value="valor">{{ etiqueta }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label for="estado" class="sr-only">Estado</label>
                        <select id="estado" v-model="estado" class="campo">
                            <option value="">Todos los estados</option>
                            <option v-for="(etiqueta, valor) in estados" :key="valor" :value="valor">{{ etiqueta }}</option>
                        </select>
                    </div>
                </div>
                <div v-if="hayFiltros" class="mt-3 flex items-center justify-between text-sm">
                    <span class="text-gray-500">{{ certificados.total }} {{ certificados.total === 1 ? 'resultado' : 'resultados' }}</span>
                    <button type="button" @click="limpiar" class="font-medium text-indigo-600 hover:text-indigo-700">Limpiar filtros</button>
                </div>
            </div>

            <!-- Listado -->
            <div v-if="certificados.data.length" class="animar-entrada overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <!-- Tabla en pantallas medianas y grandes -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Certificado</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Estudiante</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Tipo</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Emisión</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Estado</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="c in certificados.data" :key="c.id" class="transition-colors hover:bg-gray-50">
                                <td class="max-w-xs px-5 py-4">
                                    <Link :href="route('certificados.show', c.id)" class="line-clamp-2 text-sm font-medium text-gray-900 hover:text-indigo-700">{{ c.titulo }}</Link>
                                    <p class="mt-0.5 font-mono text-xs text-gray-500">{{ c.codigo }}</p>
                                    <p v-if="c.evento" class="mt-0.5 truncate text-xs text-gray-500">{{ c.evento.titulo }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm text-gray-900">{{ c.usuario?.name }}</p>
                                    <p class="text-xs text-gray-500">{{ c.usuario?.email }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', colorTipo(c.tipo)]">{{ tipos[c.tipo] ?? c.tipo }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">{{ fechaCorta(c.fecha_emision) }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', c.estado === 'revocado' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800']">{{ estados[c.estado] ?? c.estado }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex justify-end gap-1">
                                        <Link :href="route('certificados.show', c.id)" class="boton-icono" title="Ver detalle" aria-label="Ver detalle">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </Link>
                                        <a :href="route('certificados.descargar', c.id)" class="boton-icono" title="Descargar PDF" aria-label="Descargar PDF">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        </a>
                                        <Link :href="route('certificados.edit', c.id)" class="boton-icono" title="Editar" aria-label="Editar">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </Link>
                                        <button type="button" @click="eliminar(c)" class="boton-icono hover:bg-red-50 hover:text-red-600" title="Eliminar" aria-label="Eliminar">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tarjetas en celular -->
                <ul class="divide-y divide-gray-100 md:hidden">
                    <li v-for="c in certificados.data" :key="c.id" class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <Link :href="route('certificados.show', c.id)" class="min-w-0">
                                <p class="break-words text-sm font-medium text-gray-900">{{ c.titulo }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ c.usuario?.name }}</p>
                            </Link>
                            <span :class="['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold', c.estado === 'revocado' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800']">{{ estados[c.estado] ?? c.estado }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                            <span :class="['rounded-full px-2 py-0.5 font-semibold', colorTipo(c.tipo)]">{{ tipos[c.tipo] ?? c.tipo }}</span>
                            <span>{{ fechaCorta(c.fecha_emision) }}</span>
                            <span class="font-mono">{{ c.codigo }}</span>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <Link :href="route('certificados.show', c.id)" class="flex-1 rounded-lg border border-gray-300 py-2 text-center text-sm font-medium text-gray-700">Ver</Link>
                            <a :href="route('certificados.descargar', c.id)" class="flex-1 rounded-lg bg-indigo-600 py-2 text-center text-sm font-medium text-white">PDF</a>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Sin resultados -->
            <div v-else class="animar-entrada rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                <h3 class="mt-3 text-sm font-semibold text-gray-900">{{ hayFiltros ? 'Ningún certificado coincide' : 'Aún no se han emitido certificados' }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ hayFiltros ? 'Prueba con otra búsqueda o quita los filtros.' : 'Emite el primero; el sistema genera el PDF automáticamente.' }}</p>
                <button v-if="hayFiltros" type="button" @click="limpiar" class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-700">Limpiar filtros</button>
                <Link v-else :href="route('certificados.create')" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Emitir certificado</Link>
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
const estado = ref(props.filtros?.estado ?? '');

const hayFiltros = computed(() => Boolean(buscar.value || tipo.value || estado.value));

const tarjetas = computed(() => [
    { etiqueta: 'Emitidos', valor: props.resumen?.total ?? 0, color: 'text-gray-900' },
    { etiqueta: 'Vigentes', valor: props.resumen?.vigentes ?? 0, color: 'text-emerald-700' },
    { etiqueta: 'Revocados', valor: props.resumen?.revocados ?? 0, color: 'text-red-700' },
    { etiqueta: `En ${new Date().getFullYear()}`, valor: props.resumen?.este_anio ?? 0, color: 'text-indigo-700' },
]);

const aplicar = () => {
    router.get(route('certificados.index'), {
        buscar: buscar.value || undefined,
        tipo: tipo.value || undefined,
        estado: estado.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

let espera;
watch(buscar, () => { clearTimeout(espera); espera = setTimeout(aplicar, 350); });
watch([tipo, estado], aplicar);

const limpiar = () => {
    buscar.value = '';
    tipo.value = '';
    estado.value = '';
};

const COLORES_TIPO = {
    participacion: 'bg-blue-100 text-blue-800',
    ponencia: 'bg-emerald-100 text-emerald-800',
    poster: 'bg-violet-100 text-violet-800',
    organizacion: 'bg-amber-100 text-amber-800',
    voluntariado: 'bg-pink-100 text-pink-800',
};
const colorTipo = (valor) => COLORES_TIPO[valor] ?? 'bg-gray-100 text-gray-700';

// Sólo la parte de la fecha, para que la zona horaria no la corra un día.
const fechaCorta = (valor) => {
    if (!valor) return '—';
    const [a, m, d] = String(valor).slice(0, 10).split('-').map(Number);
    return new Date(a, m - 1, d).toLocaleDateString('es-CO', { day: 'numeric', month: 'short', year: 'numeric' });
};

const eliminar = (c) => {
    if (confirm(`¿Eliminar el certificado «${c.titulo}» de ${c.usuario?.name}? Esta acción no se puede deshacer.`)) {
        router.delete(route('certificados.destroy', c.id), { preserveScroll: true });
    }
};
</script>

<style scoped>
.campo {
    @apply block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
}
.boton-icono {
    @apply inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900;
}
</style>
