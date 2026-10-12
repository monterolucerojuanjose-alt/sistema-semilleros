<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    subtitle: {
        type: String,
        default: ''
    },
    icon: {
        type: String,
        default: 'document'
    },
    color: {
        type: String,
        default: 'blue'
    },
    hover: {
        type: Boolean,
        default: true
    },
    clickable: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['click']);

const colorClasses = computed(() => {
    const colors = {
        blue: {
            bg: 'bg-blue-50',
            border: 'border-blue-200',
            icon: 'text-blue-600',
            title: 'text-blue-900',
            subtitle: 'text-blue-700'
        },
        green: {
            bg: 'bg-green-50',
            border: 'border-green-200',
            icon: 'text-green-600',
            title: 'text-green-900',
            subtitle: 'text-green-700'
        },
        purple: {
            bg: 'bg-purple-50',
            border: 'border-purple-200',
            icon: 'text-purple-600',
            title: 'text-purple-900',
            subtitle: 'text-purple-700'
        },
        orange: {
            bg: 'bg-orange-50',
            border: 'border-orange-200',
            icon: 'text-orange-600',
            title: 'text-orange-900',
            subtitle: 'text-orange-700'
        },
        red: {
            bg: 'bg-red-50',
            border: 'border-red-200',
            icon: 'text-red-600',
            title: 'text-red-900',
            subtitle: 'text-red-700'
        },
        gray: {
            bg: 'bg-gray-50',
            border: 'border-gray-200',
            icon: 'text-gray-600',
            title: 'text-gray-900',
            subtitle: 'text-gray-700'
        }
    };
    return colors[props.color] || colors.blue;
});

const getIcon = (iconName) => {
    const icons = {
        'document': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'users': 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
        'chart': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'folder': 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z',
        'academic': 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
        'check': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'star': 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        'bell': 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        'home': 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
    };
    return icons[iconName] || icons.document;
};

const handleClick = () => {
    if (props.clickable) {
        emit('click');
    }
};
</script>

<template>
    <div
        :class="[
            'rounded-xl border-2 p-6 transition-all duration-200',
            colorClasses.bg,
            colorClasses.border,
            hover ? 'hover:shadow-lg hover:scale-105' : '',
            clickable ? 'cursor-pointer' : ''
        ]"
        @click="handleClick"
    >
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div :class="[
                    'w-12 h-12 rounded-lg flex items-center justify-center',
                    colorClasses.bg
                ]">
                    <svg
                        :class="['w-6 h-6', colorClasses.icon]"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            :d="getIcon(icon)"
                        />
                    </svg>
                </div>
            </div>

            <div class="ml-4 flex-1">
                <h3 :class="['text-lg font-semibold', colorClasses.title]">
                    {{ title }}
                </h3>
                <p v-if="subtitle" :class="['text-sm mt-1', colorClasses.subtitle]">
                    {{ subtitle }}
                </p>

                <!-- Slot para contenido adicional -->
                <div class="mt-3">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
