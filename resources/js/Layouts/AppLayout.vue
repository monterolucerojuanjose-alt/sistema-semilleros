<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
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

// Se parte del ancho real para que en celular el menú nazca cerrado; antes
// aparecía abierto un instante y tapaba la pantalla hasta que corría onMounted.
const esMovil = () => typeof window !== 'undefined' && window.innerWidth < 768;

const isSidebarCollapsed = ref(esMovil());
const isMobile = ref(esMovil());

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

// En celular, Safari y Chrome disparan «resize» al ocultar la barra de
// direcciones al desplazar. Sólo se reacciona cuando se cruza el límite de 768 px
// de ancho; si no, el menú abierto se cerraba solo al hacer scroll.
const checkMobile = () => {
    const ahoraMovil = esMovil();
    if (ahoraMovil === isMobile.value) return;

    isMobile.value = ahoraMovil;
    // Al pasar a celular se cierra; al pasar a escritorio se muestra completo.
    isSidebarCollapsed.value = ahoraMovil;
};

// Escape cierra el menú en celular (teclado externo / accesibilidad).
const alPulsarTecla = (evento) => {
    if (evento.key === 'Escape') closeMobileSidebar();
};

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile, { passive: true });
    window.addEventListener('keydown', alPulsarTecla);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
    window.removeEventListener('keydown', alPulsarTecla);
});

// Cerrar sidebar en móvil al hacer clic fuera
const closeMobileSidebar = () => {
    if (isMobile.value) {
        isSidebarCollapsed.value = true;
    }
};

// Al elegir una opción del menú en celular, el menú debe cerrarse; antes se
// quedaba abierto tapando la pantalla a la que se acababa de navegar.
watch(rutaActual, closeMobileSidebar);
</script>

<template>
    <!-- El nombre del sistema lo añade app.js a todos los títulos; aquí sólo
         va el nombre de la pantalla, o se repetiría dos veces en la pestaña. -->
    <Head :title="title || 'Inicio'" />

    <div class="min-h-app bg-gray-50">
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
            <div v-if="$slots.header" class="animar-entrada px-4 pt-4 sm:px-6 sm:pt-6">
                <slot name="header" />
            </div>

            <!-- Contenido de la página. La clave hace que Vue vuelva a montar
                 el bloque en cada navegación y la animación se repita.
                 min-w-0 evita que una tabla ancha estire toda la pantalla. -->
            <main :key="rutaActual" class="animar-entrada min-w-0 p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
