<script setup>
import { computed } from 'vue';

/**
 * Foto de perfil de un usuario, con sus iniciales como respaldo.
 *
 * El modelo User expone `avatar_url` (con marca de tiempo para evitar la caché
 * del navegador). Antes cada pantalla dibujaba solo las iniciales, así que la
 * foto que subía un usuario nunca se veía desde las demás cuentas.
 */
const props = defineProps({
    usuario: {
        type: Object,
        default: null,
    },
    // Clases de tamaño: h-10 w-10, h-8 w-8, etc.
    tamano: {
        type: String,
        default: 'h-10 w-10',
    },
    textoTamano: {
        type: String,
        default: 'text-sm',
    },
});

const url = computed(() => props.usuario?.avatar_url || null);

const iniciales = computed(() => {
    const nombre = props.usuario?.name || props.usuario?.nombre || '';
    return nombre
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() || '')
        .join('') || '?';
});

const alt = computed(() => `Foto de ${props.usuario?.name || 'usuario'}`);
</script>

<template>
    <img
        v-if="url"
        :src="url"
        :alt="alt"
        :class="[tamano, 'shrink-0 rounded-full object-cover ring-1 ring-gray-200']"
        loading="lazy"
    />
    <span
        v-else
        :class="[tamano, textoTamano, 'inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-purple-500 font-bold text-white']"
        :title="usuario?.name"
    >
        {{ iniciales }}
    </span>
</template>
