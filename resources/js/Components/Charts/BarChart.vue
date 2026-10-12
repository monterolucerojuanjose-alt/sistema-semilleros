<template>
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
            <div v-if="loading" class="flex items-center text-sm text-gray-500">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Cargando...
            </div>
        </div>
        <div class="relative">
            <canvas ref="chartCanvas" :width="width" :height="height"></canvas>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    data: {
        type: Object,
        default: () => ({})
    },
    options: {
        type: Object,
        default: () => ({})
    },
    loading: {
        type: Boolean,
        default: false
    },
    width: {
        type: Number,
        default: 400
    },
    height: {
        type: Number,
        default: 200
    }
});

const chartCanvas = ref(null);
let chartInstance = null;

const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
        tooltip: {
            enabled: true,
            mode: 'index',
            intersect: false,
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
                color: '#6B7280'
            }
        },
        x: {
            grid: {
                color: 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
                color: '#6B7280'
            }
        }
    }
};

const createChart = () => {
    if (!chartCanvas.value) return;

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: props.data,
        options: { ...defaultOptions, ...props.options }
    });
};

const updateChart = () => {
    if (chartInstance && props.data) {
        chartInstance.data = props.data;
        chartInstance.update();
    }
};

watch(() => props.data, updateChart, { deep: true });

onMounted(() => {
    nextTick(() => {
        createChart();
    });
});

onUnmounted(() => {
    if (chartInstance) {
        chartInstance.destroy();
    }
});
</script>




















