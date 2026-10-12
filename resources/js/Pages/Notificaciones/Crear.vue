<template>
    <AppLayout title="Enviar anuncio" :auth="auth">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 shadow-sm shadow-indigo-500/30">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3.5 10.5v3a1 1 0 0 0 1 1h2.2l6.3 3.6V5.9L6.7 9.5H4.5a1 1 0 0 0-1 1Z" />
                            <path d="M16.8 9.2a4 4 0 0 1 0 5.6" />
                            <path d="M19.3 6.7a7.5 7.5 0 0 1 0 10.6" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">Enviar anuncio</h2>
                        <p class="text-sm text-gray-500">Comunica información relevante a los usuarios del sistema</p>
                    </div>
                </div>
                <Link
                    href="/notificaciones"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Volver a la bandeja
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-6xl">
            <form @submit.prevent="revisarEnvio" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Columna principal -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Destinatarios -->
                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-900">1. ¿Quién debe recibirlo?</h3>

                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <button
                                v-for="opcion in modos" :key="opcion.valor"
                                type="button"
                                @click="form.modo = opcion.valor"
                                :class="[
                                    'rounded-lg border p-3 text-left transition-colors',
                                    form.modo === opcion.valor
                                        ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500'
                                        : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
                                ]"
                            >
                                <span class="block text-sm font-medium"
                                      :class="form.modo === opcion.valor ? 'text-indigo-900' : 'text-gray-900'">
                                    {{ opcion.etiqueta }}
                                </span>
                                <span class="mt-0.5 block text-xs"
                                      :class="form.modo === opcion.valor ? 'text-indigo-700' : 'text-gray-500'">
                                    {{ opcion.descripcion }}
                                </span>
                            </button>
                        </div>

                        <!-- Por rol -->
                        <div v-if="form.modo === 'rol'" class="mt-4">
                            <label class="mb-2 block text-xs font-medium text-gray-700">Rol destinatario</label>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <button
                                    v-for="rol in roles" :key="rol"
                                    type="button"
                                    @click="form.rol = rol"
                                    :class="[
                                        'flex items-center justify-between rounded-lg border px-3 py-2 text-sm transition-colors',
                                        form.rol === rol
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-900'
                                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                    ]"
                                >
                                    <span>{{ rol }}</span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                        {{ conteoPorRol[rol] ?? 0 }}
                                    </span>
                                </button>
                            </div>
                            <p v-if="form.errors.rol" class="mt-2 text-xs text-red-600">{{ form.errors.rol }}</p>
                        </div>

                        <!-- Usuarios concretos -->
                        <div v-if="form.modo === 'usuarios'" class="mt-4">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <label class="text-xs font-medium text-gray-700">Destinatarios</label>
                                <div class="flex gap-3 text-xs">
                                    <button type="button" @click="seleccionarVisibles" class="font-medium text-indigo-600 hover:underline">
                                        Seleccionar los {{ usuariosFiltrados.length }} visibles
                                    </button>
                                    <button type="button" @click="form.usuario_ids = []" class="font-medium text-gray-500 hover:underline">
                                        Quitar todos
                                    </button>
                                </div>
                            </div>

                            <input
                                v-model="busqueda"
                                type="search"
                                placeholder="Buscar por nombre, correo o rol…"
                                class="mb-2 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <div class="max-h-64 divide-y divide-gray-100 overflow-y-auto rounded-lg border border-gray-200">
                                <label
                                    v-for="usuario in usuariosFiltrados" :key="usuario.id"
                                    class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors hover:bg-gray-50"
                                >
                                    <input
                                        type="checkbox"
                                        :value="usuario.id"
                                        v-model="form.usuario_ids"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-[10px] font-semibold text-indigo-700">
                                        {{ iniciales(usuario.name) }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm text-gray-900">{{ usuario.name }}</span>
                                        <span class="block truncate text-xs text-gray-500">{{ usuario.email }}</span>
                                    </span>
                                    <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">
                                        {{ usuario.rol }}
                                    </span>
                                </label>
                                <p v-if="usuariosFiltrados.length === 0" class="px-3 py-6 text-center text-sm text-gray-500">
                                    Ningún usuario coincide con la búsqueda.
                                </p>
                            </div>
                            <p v-if="form.errors.usuario_ids" class="mt-2 text-xs text-red-600">{{ form.errors.usuario_ids }}</p>
                        </div>
                    </section>

                    <!-- Contenido -->
                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-900">2. ¿Qué quieres comunicar?</h3>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">Título</label>
                                <input
                                    v-model="form.titulo"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Ej. Apertura de inscripciones al Encuentro de Semilleros"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="form.errors.titulo" class="mt-1 text-xs text-red-600">{{ form.errors.titulo }}</p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700">Tipo de aviso</label>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    <button
                                        v-for="t in tipos" :key="t.valor"
                                        type="button"
                                        @click="form.tipo = t.valor"
                                        :class="[
                                            'flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors',
                                            form.tipo === t.valor
                                                ? 'border-indigo-500 bg-indigo-50 text-indigo-900'
                                                : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                        ]"
                                    >
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="t.punto"></span>
                                        {{ t.etiqueta }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label class="text-xs font-medium text-gray-700">Mensaje</label>
                                    <span class="text-xs" :class="form.mensaje.length > 2000 ? 'text-red-600' : 'text-gray-400'">
                                        {{ form.mensaje.length }} / 2000
                                    </span>
                                </div>
                                <textarea
                                    v-model="form.mensaje"
                                    rows="5"
                                    maxlength="2000"
                                    placeholder="Escribe el contenido del anuncio…"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                                <p v-if="form.errors.mensaje" class="mt-1 text-xs text-red-600">{{ form.errors.mensaje }}</p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Columna lateral: resumen y vista previa -->
                <div class="space-y-6">
                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-900">Resumen del envío</h3>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-gray-500">Destinatarios</dt>
                                <dd class="text-right font-medium text-gray-900">{{ descripcionDestinatarios }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3">
                                <dt class="text-gray-500">Personas</dt>
                                <dd>
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-sm font-semibold text-indigo-700">
                                        {{ totalDestinatarios }}
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <p v-if="form.errors.destinatarios" class="mt-3 rounded-lg bg-red-50 p-2 text-xs text-red-700">
                            {{ form.errors.destinatarios }}
                        </p>

                        <button
                            type="submit"
                            :disabled="!listoParaEnviar || form.processing"
                            class="mt-5 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ form.processing ? 'Enviando…' : 'Revisar y enviar' }}
                        </button>
                        <p v-if="!listoParaEnviar" class="mt-2 text-center text-xs text-gray-500">
                            Completa el título, el mensaje y los destinatarios.
                        </p>
                    </section>

                    <!-- Vista previa -->
                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">Vista previa</h3>
                        <p class="mb-3 text-xs text-gray-500">Así lo verá cada destinatario en su bandeja.</p>

                        <article class="rounded-lg border border-gray-200 bg-indigo-50/40 p-3" :class="bordeTipo">
                            <div class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full" :class="fondoTipo">
                                    <svg class="h-4 w-4" :class="colorTipo" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="iconoTipo" />
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ form.titulo || 'Título del anuncio' }}
                                    </p>
                                    <p class="mt-0.5 whitespace-pre-line break-words text-xs text-gray-600">
                                        {{ form.mensaje || 'Aquí aparecerá el contenido del mensaje.' }}
                                    </p>
                                    <p class="mt-2 text-[11px] text-gray-400">
                                        Enviado por {{ auth.user.name }} · ahora
                                    </p>
                                </div>
                            </div>
                        </article>
                    </section>
                </div>
            </form>
        </div>

        <!-- Confirmación -->
        <div v-if="confirmando" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4" @click.self="confirmando = false">
            <div class="mx-auto mt-24 w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="px-6 py-5">
                    <h3 class="text-base font-semibold text-gray-900">Confirmar envío</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Vas a enviar <strong>«{{ form.titulo }}»</strong> a
                        <strong>{{ totalDestinatarios }}</strong>
                        {{ totalDestinatarios === 1 ? 'persona' : 'personas' }} ({{ descripcionDestinatarios.toLowerCase() }}).
                    </p>
                    <p class="mt-2 text-sm text-gray-500">Esta acción no se puede deshacer.</p>
                </div>
                <div class="flex justify-end gap-2 bg-gray-50 px-6 py-4">
                    <button type="button" @click="confirmando = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button type="button" @click="enviar" :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                        {{ form.processing ? 'Enviando…' : 'Enviar anuncio' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    roles: Array,
    usuarios: Array,
    conteoPorRol: Object,
    totalUsuarios: Number,
})

const form = useForm({
    modo: 'todos',
    rol: '',
    usuario_ids: [],
    titulo: '',
    mensaje: '',
    tipo: 'info',
})

const busqueda = ref('')
const confirmando = ref(false)

const modos = [
    { valor: 'todos', etiqueta: 'Todos', descripcion: 'Cada usuario del sistema' },
    { valor: 'rol', etiqueta: 'Por rol', descripcion: 'Un perfil concreto' },
    { valor: 'usuarios', etiqueta: 'Usuarios', descripcion: 'Selección manual' },
]

const tipos = [
    { valor: 'info', etiqueta: 'Información', punto: 'bg-blue-500' },
    { valor: 'exito', etiqueta: 'Éxito', punto: 'bg-emerald-500' },
    { valor: 'alerta', etiqueta: 'Alerta', punto: 'bg-amber-500' },
    { valor: 'error', etiqueta: 'Urgente', punto: 'bg-red-500' },
]

const usuariosFiltrados = computed(() => {
    const termino = busqueda.value.trim().toLowerCase()
    if (!termino) return props.usuarios
    return props.usuarios.filter((u) =>
        `${u.name} ${u.email} ${u.rol}`.toLowerCase().includes(termino)
    )
})

const totalDestinatarios = computed(() => {
    if (form.modo === 'todos') return props.totalUsuarios
    if (form.modo === 'rol') return form.rol ? (props.conteoPorRol[form.rol] ?? 0) : 0
    return form.usuario_ids.length
})

const descripcionDestinatarios = computed(() => {
    if (form.modo === 'todos') return 'Todos los usuarios'
    if (form.modo === 'rol') return form.rol || 'Rol sin elegir'
    return 'Selección manual'
})

const listoParaEnviar = computed(() =>
    form.titulo.trim() !== '' && form.mensaje.trim() !== '' && totalDestinatarios.value > 0
)

const ICONOS = {
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    exito: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    alerta: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    error: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
}
const iconoTipo = computed(() => ICONOS[form.tipo])
const colorTipo = computed(() => ({
    info: 'text-blue-600', exito: 'text-emerald-600', alerta: 'text-amber-600', error: 'text-red-600',
}[form.tipo]))
const fondoTipo = computed(() => ({
    info: 'bg-blue-100', exito: 'bg-emerald-100', alerta: 'bg-amber-100', error: 'bg-red-100',
}[form.tipo]))
const bordeTipo = computed(() => ({
    info: 'border-l-4 border-l-blue-500', exito: 'border-l-4 border-l-emerald-500',
    alerta: 'border-l-4 border-l-amber-500', error: 'border-l-4 border-l-red-500',
}[form.tipo]))

const iniciales = (nombre) =>
    (nombre || '').trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('')

const seleccionarVisibles = () => {
    const visibles = usuariosFiltrados.value.map((u) => u.id)
    form.usuario_ids = [...new Set([...form.usuario_ids, ...visibles])]
}

/** Un anuncio no se puede deshacer, así que siempre se confirma antes. */
const revisarEnvio = () => {
    if (!listoParaEnviar.value) return
    confirmando.value = true
}

const enviar = () => {
    form.post('/notificaciones/enviar', {
        preserveScroll: true,
        onFinish: () => (confirmando.value = false),
    })
}
</script>
