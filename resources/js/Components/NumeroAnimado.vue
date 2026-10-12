<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';

/**
 * Muestra una cifra contando desde cero hasta su valor.
 *
 * Se usa en los indicadores del panel para que la pantalla se sienta viva al
 * entrar. Si la persona pidió reducir el movimiento en su sistema, el número
 * aparece directo, sin animación.
 */
const props = defineProps({
    valor: { type: [Number, String], default: 0 },
    decimales: { type: Number, default: 0 },
    duracion: { type: Number, default: 900 },
});

const mostrado = ref(0);
let animacion = null;
let salvaguarda = null;

const prefiereMenosMovimiento = () =>
    typeof window !== 'undefined' &&
    window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

const detener = () => {
    if (animacion) cancelAnimationFrame(animacion);
    if (salvaguarda) clearTimeout(salvaguarda);
    animacion = null;
    salvaguarda = null;
};

const animar = (destino) => {
    const fin = Number(destino);

    // Un valor no numérico se muestra tal cual, sin contar.
    if (!Number.isFinite(fin)) {
        mostrado.value = 0;
        return;
    }

    detener();

    // Sin animación si se pidió reducir el movimiento, o si la pestaña está en
    // segundo plano: allí requestAnimationFrame no se ejecuta y la cifra se
    // quedaría congelada a mitad de la cuenta, mostrando un dato falso.
    if (prefiereMenosMovimiento() || props.duracion <= 0 || document.hidden) {
        mostrado.value = fin;
        return;
    }

    const inicio = performance.now();

    const paso = (ahora) => {
        const avance = Math.min(1, (ahora - inicio) / props.duracion);
        // Desaceleración al final, para que la cifra "aterrice".
        const suave = 1 - Math.pow(1 - avance, 3);

        mostrado.value = fin * suave;

        if (avance < 1) {
            animacion = requestAnimationFrame(paso);
        } else {
            mostrado.value = fin;
            detener();
        }
    };

    animacion = requestAnimationFrame(paso);

    // Red de seguridad: pase lo que pase con los fotogramas (pestaña oculta,
    // navegador que los limita), la cifra correcta queda puesta.
    salvaguarda = setTimeout(() => {
        mostrado.value = fin;
        detener();
    }, props.duracion + 150);
};

// Si la persona vuelve a la pestaña, se garantiza el valor definitivo.
const alCambiarVisibilidad = () => {
    if (!document.hidden) {
        detener();
        mostrado.value = Number(props.valor) || 0;
    }
};

onMounted(() => {
    animar(props.valor);
    document.addEventListener('visibilitychange', alCambiarVisibilidad);
});

watch(() => props.valor, (nuevo) => animar(nuevo));

onBeforeUnmount(() => {
    detener();
    document.removeEventListener('visibilitychange', alCambiarVisibilidad);
});
</script>

<template>
    <span>{{ Number(mostrado).toFixed(decimales) }}</span>
</template>
