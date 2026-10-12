<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

// Mismas clases que la pantalla de acceso, para que las dos se vean iguales.
const inputClass =
    '!py-2 pl-9 text-sm mt-0.5 block w-full rounded-lg border-gray-200 bg-white/80 shadow-sm ring-1 ring-gray-200/80 transition duration-200 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 sm:!py-2.5 sm:pl-10 sm:rounded-xl';

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contraseña" />

        <div class="text-center">
            <div
                class="mb-1.5 inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50/80 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-indigo-700 sm:mb-2 sm:gap-2 sm:px-3 sm:py-1 sm:text-xs"
            >
                <span class="relative flex h-1.5 w-1.5 sm:h-2 sm:w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-indigo-400 opacity-75 motion-reduce:animate-none" />
                    <span class="relative inline-flex h-full w-full rounded-full bg-indigo-600" />
                </span>
                Recuperación
            </div>
            <h2
                class="bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-800 bg-clip-text text-2xl font-bold tracking-tight text-transparent sm:text-3xl"
            >
                ¿Olvidaste tu contraseña?
            </h2>
            <p class="mt-1 text-xs text-slate-600 sm:mt-1.5 sm:text-sm">
                Escribe tu correo y te enviaremos un enlace para crear una nueva.
            </p>
            <div class="mx-auto mt-3 h-0.5 w-12 rounded-full bg-gradient-to-r from-indigo-500 via-violet-500 to-cyan-400 sm:mt-4 sm:h-1 sm:w-16" />
        </div>

        <!-- Confirmación de que el correo salió -->
        <div
            v-if="status"
            class="mb-3 mt-4 flex items-center gap-2 rounded-lg border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-teal-50/80 px-3 py-2 text-xs text-emerald-900 shadow-sm sm:mb-4 sm:mt-5 sm:gap-3 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm"
        >
            <svg class="h-4 w-4 shrink-0 text-emerald-600 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium">{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="mt-4 space-y-3 sm:mt-5 sm:space-y-4">
            <div class="group">
                <InputLabel for="email" value="Correo electrónico" class="mb-1 block text-xs font-semibold text-slate-700 sm:text-sm" />
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 transition-colors group-focus-within:text-indigo-500 sm:pl-3">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <TextInput
                        id="email"
                        type="email"
                        :class="inputClass"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="tu@correo.edu"
                    />
                </div>
                <InputError class="mt-1 text-xs sm:mt-1.5" :message="form.errors.email" />
            </div>

            <PrimaryButton
                class="group/btn relative w-full justify-center overflow-hidden rounded-xl bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-600 bg-[length:200%_100%] py-2.5 text-sm font-semibold shadow-lg shadow-indigo-500/25 transition-all duration-300 hover:scale-[1.01] hover:bg-[position:100%_0] hover:shadow-indigo-500/35 active:scale-[0.99] motion-reduce:transform-none sm:py-3 sm:text-base"
                :class="{ 'opacity-60': form.processing }"
                :disabled="form.processing"
            >
                <span class="relative flex items-center justify-center gap-2">
                    <svg v-if="form.processing" class="h-5 w-5 animate-spin text-white motion-reduce:animate-none" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    {{ form.processing ? 'Enviando…' : 'Enviar enlace de recuperación' }}
                </span>
            </PrimaryButton>
        </form>

        <p class="mt-4 text-center text-xs text-slate-600 sm:text-sm">
            ¿Ya la recordaste?
            <Link :href="route('login')" class="font-semibold text-indigo-600 hover:text-indigo-800">
                Volver al inicio de sesión
            </Link>
        </p>
    </GuestLayout>
</template>
