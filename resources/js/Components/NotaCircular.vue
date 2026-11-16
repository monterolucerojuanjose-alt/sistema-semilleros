<script setup>
import { computed } from 'vue';
import { formatoNota, nivelNota, porcentajeNota } from '@/utils/notas';

/**
 * Nota de 0 a 100 dibujada como anillo de progreso, con el color de su nivel.
 * Es la forma única de mostrar una calificación destacada en el sistema.
 */
const props = defineProps({
    nota: { type: [Number, String], default: null },
    // sm: listados · md: tarjetas · lg: cabecera de una ficha
    tamano: { type: String, default: 'md' },
    mostrarNivel: { type: Boolean, default: false },
});

const MEDIDAS = {
    sm: { lado: 48, grosor: 5, numero: 'text-sm' },
    md: { lado: 72, grosor: 7, numero: 'text-xl' },
    lg: { lado: 120, grosor: 10, numero: 'text-3xl' },
};

const medida = computed(() => MEDIDAS[props.tamano] ?? MEDIDAS.md);
const radio = computed(() => (medida.value.lado - medida.value.grosor) / 2);
const circunferencia = computed(() => 2 * Math.PI * radio.value);
const desplazamiento = computed(() => circunferencia.value * (1 - porcentajeNota(props.nota) / 100));
const nivel = computed(() => nivelNota(props.nota));
</script>

<template>
    <div class="inline-flex flex-col items-center">
        <div
            class="relative"
            :style="{ width: `${medida.lado}px`, height: `${medida.lado}px` }"
            role="img"
            :aria-label="nota === null ? 'Sin calificar' : `Nota ${formatoNota(nota)} de 100, ${nivel.texto}`"
        >
            <svg :width="medida.lado" :height="medida.lado" class="-rotate-90" aria-hidden="true">
                <circle :cx="medida.lado / 2" :cy="medida.lado / 2" :r="radio" fill="none" stroke="#f1f5f9" :stroke-width="medida.grosor" />
                <circle
                    :cx="medida.lado / 2"
                    :cy="medida.lado / 2"
                    :r="radio"
                    fill="none"
                    :stroke="nivel.trazo"
                    :stroke-width="medida.grosor"
                    stroke-linecap="round"
                    :stroke-dasharray="circunferencia"
                    :stroke-dashoffset="desplazamiento"
                    class="transition-[stroke-dashoffset] duration-700 ease-out"
                />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center leading-none">
                <span :class="['font-bold tabular-nums', medida.numero, nivel.texto_color]">{{ formatoNota(nota) }}</span>
                <span v-if="tamano === 'lg'" class="mt-1 text-[11px] font-medium text-gray-400">de 100</span>
            </div>
        </div>
        <span v-if="mostrarNivel" :class="['mt-2 rounded-full px-2.5 py-0.5 text-xs font-semibold', nivel.insignia]">{{ nivel.texto }}</span>
    </div>
</template>
