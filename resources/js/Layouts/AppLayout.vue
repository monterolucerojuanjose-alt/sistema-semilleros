<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Header from '@/Components/Header.vue';

const props = defineProps({
    auth: Object,
    children: Object,
    // Las páginas ya enviaban `title`, pero al no estar declarado Vue lo
    // descartaba con una advertencia y todas las pestañas mostraban el mismo
    // nombre. Ahora se usa para titular cada pantalla del navegador.
    title: {
        type: String,
        default: '',
    },
});

const page = usePage();
const layoutAuth = computed(() => page.props.auth ?? props.auth ?? {});

// Cambia con cada navegación; sirve de clave para reanimar el contenido.
const rutaActual = computed(() => page.url);

const isSidebarCollapsed = ref(false);
const isMobile = ref(false);

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

const checkMobile = () => {
    isMobile.value = window.innerWidth < 768;
    if (isMobile.value) {
        isSidebarCollapsed.value = true;
    }
};

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
});

// Cerrar sidebar en móvil al hacer clic fuera
const closeMobileSidebar = () => {
    if (isMobile.value) {
        isSidebarCollapsed.value = true;
    }
};
</script>

<template>
    <!-- El nombre del sistema lo añade app.js a todos los títulos; aquí sólo
         va el nombre de la pantalla, o se repetiría dos veces en la pestaña. -->
    <Head :title="title || 'Inicio'" />

    <div class="min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <div
            :class="[
                'fixed inset-y-0 left-0 z-50 transition-transform duration-300 ease-in-out',
                isSidebarCollapsed ? '-translate-x-full' : 'translate-x-0',
                'md:translate-x-0'
            ]"
        >
            <Sidebar
                :auth="layoutAuth"
                :is-collapsed="isSidebarCollapsed"
            />
        </div>

        <!-- Overlay para móvil -->
        <div
            v-if="!isSidebarCollapsed && isMobile"
            @click="closeMobileSidebar"
            class="fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden"
        ></div>

        <!-- Contenido principal -->
        <div
            :class="[
                'transition-all duration-300 ease-in-out',
                isSidebarCollapsed ? 'md:ml-16' : 'md:ml-64'
            ]"
        >
            <!-- Header -->
            <Header
                :auth="layoutAuth"
                :is-collapsed="isSidebarCollapsed"
                @toggle-sidebar="toggleSidebar"
            />

            <!-- Encabezado propio de cada página (slot opcional) -->
            <div v-if="$slots.header" class="animar-entrada px-6 pt-6">
                <slot name="header" />
            </div>

            <!-- Contenido de la página. La clave hace que Vue vuelva a montar
                 el bloque en cada navegación y la animación se repita. -->
            <main :key="rutaActual" class="animar-entrada p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
