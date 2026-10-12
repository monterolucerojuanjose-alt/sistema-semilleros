<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    links: {
        type: Array,
        required: true,
    },
    /**
     * Datos del paginador de Laravel (from, to, total). Son opcionales: si no
     * se envían, simplemente no se muestra la línea de "Mostrando X a Y".
     * Antes se intentaban deducir de la URL y salían cifras inventadas.
     */
    from: { type: Number, default: null },
    to: { type: Number, default: null },
    total: { type: Number, default: null },
})

const hayResumen = computed(() => props.total !== null)

const primero = computed(() => props.links[0])
const ultimo = computed(() => props.links[props.links.length - 1])

const claseEnlace = (link, index) => [
    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
    index === 0 ? 'rounded-l-md' : index === props.links.length - 1 ? 'rounded-r-md' : '',
    link.active
        ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
]

const claseDeshabilitado = (index) => [
    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
    index === 0 ? 'rounded-l-md' : index === props.links.length - 1 ? 'rounded-r-md' : '',
    'bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed',
]
</script>

<template>
    <div v-if="links.length > 3" class="flex items-center justify-between">
        <!-- Móvil -->
        <div class="flex flex-1 justify-between sm:hidden">
            <Link
                v-if="primero?.url"
                :href="primero.url"
                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Anterior
            </Link>
            <span
                v-else
                class="relative inline-flex cursor-not-allowed items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-300"
            >
                Anterior
            </span>

            <Link
                v-if="ultimo?.url"
                :href="ultimo.url"
                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Siguiente
            </Link>
            <span
                v-else
                class="relative ml-3 inline-flex cursor-not-allowed items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-300"
            >
                Siguiente
            </span>
        </div>

        <!-- Escritorio -->
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <p v-if="hayResumen" class="text-sm text-gray-700">
                Mostrando
                <span class="font-medium">{{ from ?? 0 }}</span>
                a
                <span class="font-medium">{{ to ?? 0 }}</span>
                de
                <span class="font-medium">{{ total }}</span>
                resultados
            </p>
            <span v-else></span>

            <nav class="relative z-0 -space-x-px inline-flex rounded-md shadow-sm" aria-label="Paginación">
                <template v-for="(link, index) in links" :key="index">
                    <!-- Los extremos deshabilitados llegan con url nula. Antes se
                         pasaban igual al Link de Inertia, que fallaba al hacer
                         href.toString() y dejaba un error en cada página. -->
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        :class="claseEnlace(link, index)"
                    />
                    <span
                        v-else
                        v-html="link.label"
                        :class="claseDeshabilitado(index)"
                    />
                </template>
            </nav>
        </div>
    </div>
</template>
