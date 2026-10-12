<template>
    <AppLayout title="Notificaciones" :auth="auth">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 shadow-sm shadow-indigo-500/30">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8.5a6 6 0 1 0-12 0c0 4.2-1.4 5.6-1.9 6.1a.6.6 0 0 0 .4 1h15a.6.6 0 0 0 .4-1c-.5-.5-1.9-1.9-1.9-6.1Z" />
                            <path d="M10.2 19a2 2 0 0 0 3.6 0" />
                        </svg>
                        <span v-if="estadisticas.no_leidas > 0"
                              class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-white">
                            {{ estadisticas.no_leidas > 99 ? '99+' : estadisticas.no_leidas }}
                        </span>
                    </span>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">Mis notificaciones</h2>
                        <p class="text-sm text-gray-500">
                            {{ estadisticas.no_leidas > 0
                                ? `Tienes ${estadisticas.no_leidas} sin leer`
                                : 'Estás al día, no hay avisos pendientes' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="puedeEnviar"
                        href="/notificaciones/crear"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Enviar anuncio
                    </Link>
                    <button
                        v-if="estadisticas.no_leidas > 0"
                        @click="marcarTodasComoLeidas"
                        :disabled="procesando"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Marcar todas como leídas
                    </button>
                    <button
                        v-if="estadisticas.leidas > 0"
                        @click="confirmarVaciado = true"
                        :disabled="procesando"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16" />
                        </svg>
                        Vaciar leídas
                    </button>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Indicadores -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <button
                    v-for="tarjeta in tarjetas" :key="tarjeta.etiqueta"
                    type="button"
                    @click="aplicarVista(tarjeta.vista)"
                    :class="[
                        'cursor-pointer rounded-xl border bg-white p-4 text-left shadow-sm transition-colors hover:border-indigo-300 hover:bg-indigo-50/40',
                        vistaActiva === tarjeta.vista ? 'border-indigo-400 ring-1 ring-indigo-400' : 'border-gray-200'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <span :class="['inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', tarjeta.fondo]">
                            <svg class="h-5 w-5" :class="tarjeta.color" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="tarjeta.icono" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-gray-500">{{ tarjeta.etiqueta }}</p>
                            <p class="text-xl font-semibold text-gray-900">{{ tarjeta.valor }}</p>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <span class="text-xs font-medium text-gray-500">Filtrar por</span>
                <select v-model="filtros.estado" @change="aplicarFiltros"
                        class="rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todas</option>
                    <option value="no_leidas">Sin leer</option>
                    <option value="leidas">Leídas</option>
                </select>
                <select v-model="filtros.tipo" @change="aplicarFiltros"
                        class="rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Cualquier tipo</option>
                    <option value="info">Información</option>
                    <option value="exito">Éxito</option>
                    <option value="alerta">Alerta</option>
                    <option value="error">Urgente</option>
                </select>
                <span v-if="filtros.periodo === 'hoy'"
                      class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">
                    Solo de hoy
                    <button @click="filtros.periodo = ''; aplicarFiltros()" class="hover:text-sky-900" aria-label="Quitar el filtro de hoy">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
                <button v-if="hayFiltros" @click="limpiarFiltros"
                        class="text-xs font-medium text-indigo-600 hover:underline">
                    Quitar filtros
                </button>
                <span class="ml-auto text-xs text-gray-500">{{ notificaciones.total }} en total</span>
            </div>

            <!-- Listado -->
            <div v-if="notificaciones.data.length" class="space-y-2">
                <article
                    v-for="notificacion in notificaciones.data" :key="notificacion.id"
                    :class="[
                        'group rounded-xl border bg-white p-4 shadow-sm transition-colors',
                        notificacion.leida ? 'border-gray-200' : 'border-indigo-200 bg-indigo-50/30'
                    ]"
                >
                    <div class="flex items-start gap-3">
                        <span :class="['mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full', fondoTipo(notificacion.tipo)]">
                            <svg class="h-5 w-5" :class="colorTipo(notificacion.tipo)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconoTipo(notificacion.tipo)" />
                            </svg>
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 :class="['text-sm', notificacion.leida ? 'font-medium text-gray-700' : 'font-semibold text-gray-900']">
                                    {{ notificacion.titulo }}
                                </h3>
                                <span v-if="!notificacion.leida"
                                      class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-700">
                                    Nueva
                                </span>
                            </div>

                            <p class="mt-1 whitespace-pre-line break-words text-sm text-gray-600">{{ notificacion.mensaje }}</p>

                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
                                <span>{{ formatFecha(notificacion.created_at) }}</span>
                                <span v-if="notificacion.remitente">·</span>
                                <span v-if="notificacion.remitente">Enviado por {{ notificacion.remitente.name }}</span>
                                <span v-else>· Generado por el sistema</span>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <button
                                v-if="!notificacion.leida"
                                @click="marcarComoLeida(notificacion.id)"
                                :disabled="procesando"
                                title="Marcar como leída"
                                class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600 disabled:opacity-50"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </button>
                            <button
                                @click="eliminar(notificacion.id)"
                                :disabled="procesando"
                                title="Eliminar"
                                class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-50"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>

                <div class="pt-2">
                    <Pagination :links="notificaciones.links" :from="notificaciones.from" :to="notificaciones.to" :total="notificaciones.total" />
                </div>
            </div>

            <!-- Vacío -->
            <div v-else class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                </span>
                <h3 class="mt-3 text-sm font-medium text-gray-900">
                    {{ hayFiltros ? 'Ninguna notificación coincide con el filtro' : 'No tienes notificaciones' }}
                </h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                    {{ hayFiltros
                        ? 'Prueba con otro estado o tipo de aviso.'
                        : 'Aquí aparecerán los avisos del sistema y los anuncios que te envíen.' }}
                </p>
                <button v-if="hayFiltros" @click="limpiarFiltros"
                        class="mt-4 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Quitar filtros
                </button>
            </div>
        </div>

        <!-- Confirmar vaciado -->
        <div v-if="confirmarVaciado" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4" @click.self="confirmarVaciado = false">
            <div class="mx-auto mt-24 w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="px-6 py-5">
                    <h3 class="text-base font-semibold text-gray-900">Vaciar notificaciones leídas</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Se eliminarán {{ estadisticas.leidas }}
                        {{ estadisticas.leidas === 1 ? 'notificación ya leída' : 'notificaciones ya leídas' }}.
                        Las que están sin leer se conservan.
                    </p>
                </div>
                <div class="flex justify-end gap-2 bg-gray-50 px-6 py-4">
                    <button @click="confirmarVaciado = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button @click="eliminarLeidas" :disabled="procesando"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">
                        Vaciar
                    </button>
                </div>
            </div>
        </div>

        <!-- Aviso -->
        <div v-if="aviso" class="fixed bottom-4 right-4 z-50">
            <div :class="['rounded-lg px-5 py-3 text-sm text-white shadow-lg', aviso.tipo === 'error' ? 'bg-red-600' : 'bg-emerald-600']">
                {{ aviso.mensaje }}
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    auth: Object,
    notificaciones: Object,
    filtros: Object,
    estadisticas: Object,
    puedeEnviar: Boolean,
})

const page = usePage()
const procesando = ref(false)
const confirmarVaciado = ref(false)
const aviso = ref(null)

const filtros = ref({
    estado: props.filtros?.estado ?? '',
    tipo: props.filtros?.tipo ?? '',
    periodo: props.filtros?.periodo ?? '',
})

const hayFiltros = computed(() =>
    filtros.value.estado !== '' || filtros.value.tipo !== '' || filtros.value.periodo !== ''
)

// Cada indicador es también un acceso directo a una vista de la bandeja.
const tarjetas = computed(() => [
    {
        etiqueta: 'Total', valor: props.estadisticas.total, vista: 'todas',
        fondo: 'bg-indigo-50', color: 'text-indigo-600',
        icono: 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
    },
    {
        etiqueta: 'Sin leer', valor: props.estadisticas.no_leidas, vista: 'no_leidas',
        fondo: props.estadisticas.no_leidas > 0 ? 'bg-red-50' : 'bg-gray-100',
        color: props.estadisticas.no_leidas > 0 ? 'text-red-600' : 'text-gray-400',
        icono: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
    },
    {
        etiqueta: 'Leídas', valor: props.estadisticas.leidas, vista: 'leidas',
        fondo: 'bg-emerald-50', color: 'text-emerald-600',
        icono: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        etiqueta: 'Recibidas hoy', valor: props.estadisticas.hoy, vista: 'hoy',
        fondo: 'bg-sky-50', color: 'text-sky-600',
        icono: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
])

/** Indicador resaltado según los filtros vigentes. */
const vistaActiva = computed(() => {
    if (filtros.value.periodo === 'hoy') return 'hoy'
    if (filtros.value.estado === 'no_leidas') return 'no_leidas'
    if (filtros.value.estado === 'leidas') return 'leidas'
    if (filtros.value.estado === '' && filtros.value.tipo === '') return 'todas'
    return null
})

const ICONOS = {
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    exito: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    alerta: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    error: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
}
const iconoTipo = (tipo) => ICONOS[tipo] ?? ICONOS.info
const colorTipo = (tipo) => ({
    info: 'text-blue-600', exito: 'text-emerald-600', alerta: 'text-amber-600', error: 'text-red-600',
}[tipo] ?? 'text-gray-500')
const fondoTipo = (tipo) => ({
    info: 'bg-blue-100', exito: 'bg-emerald-100', alerta: 'bg-amber-100', error: 'bg-red-100',
}[tipo] ?? 'bg-gray-100')

const formatFecha = (fecha) => {
    const d = new Date(fecha)
    const minutos = Math.floor((Date.now() - d.getTime()) / 60000)
    if (minutos < 1) return 'Hace un momento'
    if (minutos < 60) return `Hace ${minutos} min`
    if (minutos < 1440) return `Hace ${Math.floor(minutos / 60)} h`
    if (minutos < 10080) return `Hace ${Math.floor(minutos / 1440)} días`
    return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}

const mostrarAviso = (mensaje, tipo = 'success') => {
    aviso.value = { mensaje, tipo }
    setTimeout(() => (aviso.value = null), 3500)
}

// Se incluye la prop compartida para que el distintivo del menú lateral
// se actualice junto con la bandeja y no quede desfasado.
const recargar = () =>
    router.reload({ only: ['notificaciones', 'estadisticas', 'notificacionesNoLeidas'] })

const marcarComoLeida = async (id) => {
    procesando.value = true
    try {
        await axios.put(`/notificaciones/${id}/leida`)
        recargar()
    } catch {
        mostrarAviso('No se pudo marcar la notificación', 'error')
    } finally {
        procesando.value = false
    }
}

const marcarTodasComoLeidas = async () => {
    procesando.value = true
    try {
        const { data } = await axios.put('/notificaciones/marcar-todas-leidas')
        mostrarAviso(data.mensaje)
        recargar()
    } catch {
        mostrarAviso('No se pudieron marcar las notificaciones', 'error')
    } finally {
        procesando.value = false
    }
}

const eliminar = async (id) => {
    procesando.value = true
    try {
        await axios.delete(`/notificaciones/${id}`)
        recargar()
    } catch {
        mostrarAviso('No se pudo eliminar la notificación', 'error')
    } finally {
        procesando.value = false
    }
}

const eliminarLeidas = async () => {
    procesando.value = true
    try {
        const { data } = await axios.delete('/notificaciones/eliminar-leidas')
        confirmarVaciado.value = false
        mostrarAviso(data.mensaje)
        recargar()
    } catch {
        mostrarAviso('No se pudieron eliminar las notificaciones', 'error')
    } finally {
        procesando.value = false
    }
}

const aplicarFiltros = () => {
    router.get('/notificaciones', { ...filtros.value }, { preserveState: true, preserveScroll: true })
}

/** Cada indicador reconfigura los filtros de la bandeja. */
const aplicarVista = (vista) => {
    filtros.value = {
        todas: { estado: '', tipo: '', periodo: '' },
        no_leidas: { estado: 'no_leidas', tipo: '', periodo: '' },
        leidas: { estado: 'leidas', tipo: '', periodo: '' },
        hoy: { estado: '', tipo: '', periodo: 'hoy' },
    }[vista]

    aplicarFiltros()
}

const limpiarFiltros = () => {
    filtros.value = { estado: '', tipo: '', periodo: '' }
    router.get('/notificaciones')
}

// Confirmación tras enviar un anuncio desde el formulario.
onMounted(() => {
    const exito = page.props?.flash?.exito
    if (exito) mostrarAviso(exito)
})
</script>
