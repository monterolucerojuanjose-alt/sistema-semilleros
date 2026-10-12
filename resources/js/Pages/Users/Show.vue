<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    user: { type: Object, required: true },
    resumen: { type: Object, default: () => ({}) },
});

const rol = computed(() => props.user.roles?.[0]?.name ?? 'Sin rol asignado');
const estaActivo = computed(() => props.user.estado !== 'inactivo');

const COLOR_ROL = {
    'Administrador': 'bg-red-100 text-red-700',
    'Coordinador': 'bg-violet-100 text-violet-700',
    'Docente/Tutor': 'bg-blue-100 text-blue-700',
    'Jurado': 'bg-amber-100 text-amber-700',
    'Estudiante': 'bg-emerald-100 text-emerald-700',
};
const colorRol = computed(() => COLOR_ROL[rol.value] ?? 'bg-gray-100 text-gray-700');

const ESTADO_PROYECTO = {
    'Aprobado': 'bg-emerald-100 text-emerald-700',
    'Rechazado': 'bg-red-100 text-red-700',
    'En revisión': 'bg-amber-100 text-amber-700',
    'Inscrito': 'bg-blue-100 text-blue-700',
};

// Sólo se muestran los indicadores que tienen sentido para este rol; de lo
// contrario un estudiante aparecería con «0 semilleros como tutor».
const indicadores = computed(() => {
    const r = props.resumen;
    const todos = [
        { clave: 'proyectos', titulo: 'Proyectos', valor: r.proyectos, roles: ['Estudiante'] },
        { clave: 'semilleros', titulo: 'Semilleros', valor: r.semilleros, roles: ['Estudiante'] },
        { clave: 'semillerosComoTutor', titulo: 'Semilleros a cargo', valor: r.semillerosComoTutor, roles: ['Docente/Tutor', 'Coordinador'] },
        { clave: 'participacionesEventos', titulo: 'Eventos', valor: r.participacionesEventos, roles: ['Estudiante'] },
        { clave: 'certificados', titulo: 'Certificados', valor: r.certificados, roles: ['Estudiante'] },
    ];

    const propios = todos.filter((i) => i.roles.includes(rol.value));

    // Para los roles sin indicadores propios (Administrador, Jurado) se muestra
    // lo que tenga algún valor, en lugar de dejar el bloque vacío.
    return propios.length ? propios : todos.filter((i) => i.valor > 0);
});

const formatFecha = (fecha) => {
    if (!fecha) return 'Sin registrar';
    return new Date(fecha).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <AppLayout title="Ficha del usuario">
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Volver al listado -->
            <Link
                :href="route('users.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Volver a la gestión de usuarios
            </Link>

            <!-- Identidad -->
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-4">
                        <Avatar :usuario="user" tamano="h-16 w-16" texto-tamano="text-xl" />
                        <div class="min-w-0">
                            <h1 class="truncate text-xl font-bold text-gray-900">{{ user.name }}</h1>
                            <p class="truncate text-sm text-gray-500">{{ user.email }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', colorRol]">
                                    {{ rol }}
                                </span>
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        estaActivo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{ estaActivo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <Link
                        :href="route('users.edit', user.id)"
                        class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        Editar usuario
                    </Link>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-100 pt-5 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Registrado</dt>
                        <dd class="mt-0.5 text-gray-900">{{ formatFecha(user.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Correo verificado</dt>
                        <dd class="mt-0.5 text-gray-900">
                            {{ user.email_verified_at ? formatFecha(user.email_verified_at) : 'Sin verificar' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- Actividad en el sistema -->
            <section v-if="indicadores.length" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    v-for="indicador in indicadores"
                    :key="indicador.clave"
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <p class="text-2xl font-bold text-gray-900">{{ indicador.valor ?? 0 }}</p>
                    <p class="text-xs text-gray-500">{{ indicador.titulo }}</p>
                </div>
            </section>

            <!-- Semilleros a los que pertenece -->
            <section v-if="user.semilleros_aprobados?.length" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold text-gray-900">Semilleros</h2>
                <ul class="divide-y divide-gray-100">
                    <li v-for="semillero in user.semilleros_aprobados" :key="semillero.id">
                        <Link :href="route('semilleros.show', semillero.id)" class="flex items-center justify-between gap-3 py-2.5 transition-colors hover:text-indigo-600">
                            <span class="truncate text-sm">{{ semillero.nombre }}</span>
                            <span
                                :class="[
                                    'shrink-0 rounded-full px-2 py-0.5 text-xs font-medium',
                                    semillero.estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                {{ semillero.estado }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- Semilleros que tutoriza -->
            <section v-if="user.semilleros_como_tutor?.length" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold text-gray-900">Semilleros a su cargo</h2>
                <ul class="divide-y divide-gray-100">
                    <li v-for="semillero in user.semilleros_como_tutor" :key="semillero.id">
                        <Link :href="route('semilleros.show', semillero.id)" class="flex items-center justify-between gap-3 py-2.5 transition-colors hover:text-indigo-600">
                            <span class="truncate text-sm">{{ semillero.nombre }}</span>
                            <span
                                :class="[
                                    'shrink-0 rounded-full px-2 py-0.5 text-xs font-medium',
                                    semillero.estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                {{ semillero.estado }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- Proyectos de los que es autor -->
            <section v-if="user.proyectos?.length" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold text-gray-900">Proyectos</h2>
                <ul class="divide-y divide-gray-100">
                    <li v-for="proyecto in user.proyectos" :key="proyecto.id">
                        <Link :href="route('proyectos.show', proyecto.id)" class="flex items-center justify-between gap-3 py-2.5 transition-colors hover:text-indigo-600">
                            <span class="min-w-0">
                                <span class="block truncate text-sm">{{ proyecto.titulo }}</span>
                                <span class="block font-mono text-xs text-gray-400">{{ proyecto.codigo }}</span>
                            </span>
                            <span :class="['shrink-0 rounded-full px-2 py-0.5 text-xs font-medium', ESTADO_PROYECTO[proyecto.estado] ?? 'bg-gray-100 text-gray-600']">
                                {{ proyecto.estado }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
