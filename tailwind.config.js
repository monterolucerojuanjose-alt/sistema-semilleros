import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                    '33%': { transform: 'translate(30px, -20px) scale(1.03)' },
                    '66%': { transform: 'translate(-20px, 15px) scale(0.98)' },
                },
                'float-slow': {
                    '0%, 100%': { transform: 'translate(0, 0)' },
                    '50%': { transform: 'translate(-25px, -30px)' },
                },
                'fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                'pulse-soft': {
                    '0%, 100%': { opacity: '0.4' },
                    '50%': { opacity: '0.7' },
                },
                'gradient-flow': {
                    '0%, 100%': { backgroundPosition: '0% 50%' },
                    '50%': { backgroundPosition: '100% 50%' },
                },
                drift: {
                    '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                    '50%': { transform: 'translate(15px, -12px) scale(1.05)' },
                },
                /** Movimiento muy lento del patrón de puntos (fondo invitado) */
                'dots-drift': {
                    '0%, 100%': { backgroundPosition: '0px 0px' },
                    '50%': { backgroundPosition: '14px 10px' },
                },
            },
            animation: {
                float: 'float 18s ease-in-out infinite',
                'float-slow': 'float-slow 22s ease-in-out infinite',
                'fade-in-up': 'fade-in-up 0.65s cubic-bezier(0.22, 1, 0.36, 1) forwards',
                'fade-in': 'fade-in 0.5s ease-out forwards',
                shimmer: 'shimmer 3s linear infinite',
                'pulse-soft': 'pulse-soft 4s ease-in-out infinite',
                'gradient-flow': 'gradient-flow 18s ease-in-out infinite',
                drift: 'drift 14s ease-in-out infinite',
                'dots-drift': 'dots-drift 48s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
