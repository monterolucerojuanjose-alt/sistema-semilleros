<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const inputClass =
    '!py-2 pl-9 text-sm mt-0.5 block w-full rounded-lg border-gray-200 bg-white/80 shadow-sm ring-1 ring-gray-200/80 transition duration-200 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 sm:!py-2.5 sm:pl-10 sm:rounded-xl';
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar Sesión" />

        <div class="text-center">
            <div
                class="mb-1.5 inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50/80 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-indigo-700 sm:mb-2 sm:gap-2 sm:px-3 sm:py-1 sm:text-xs"
            >
                <span class="relative flex h-1.5 w-1.5 sm:h-2 sm:w-2">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-indigo-400 opacity-75 motion-reduce:animate-none"
                    />
                    <span class="relative inline-flex h-full w-full rounded-full bg-indigo-600" />
                </span>
                Acceso
            </div>
            <h2
                class="bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-800 bg-clip-text text-2xl font-bold tracking-tight text-transparent sm:text-3xl"
            >
                ¡Bienvenido de vuelta!
            </h2>
            <p class="mt-1 text-xs text-slate-600 sm:mt-1.5 sm:text-sm">
                Inicia sesión para acceder a tu cuenta
            </p>
            <div
                class="mx-auto mt-3 h-0.5 w-12 rounded-full bg-gradient-to-r from-indigo-500 via-violet-500 to-cyan-400 sm:mt-4 sm:h-1 sm:w-16"
            />
        </div>

        <div
            v-if="status"
            class="mb-3 mt-4 flex items-center gap-2 rounded-lg border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-teal-50/80 px-3 py-2 text-xs text-emerald-900 shadow-sm sm:mb-4 sm:mt-5 sm:gap-3 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm"
        >
            <svg class="h-4 w-4 shrink-0 text-emerald-600 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20">
                <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                />
            </svg>
            <span class="font-medium">{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="mt-4 space-y-3 sm:mt-5 sm:space-y-4">
            <div class="group">
                <InputLabel
                    for="email"
                    value="Correo electrónico"
                    class="mb-1 block text-xs font-semibold text-slate-700 sm:text-sm"
                />
                <div class="relative">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 transition-colors group-focus-within:text-indigo-500 sm:pl-3"
                    >
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
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

            <div class="group">
                <InputLabel
                    for="password"
                    value="Contraseña"
                    class="mb-1 block text-xs font-semibold text-slate-700 sm:text-sm"
                />
                <div class="relative">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 transition-colors group-focus-within:text-indigo-500 sm:pl-3"
                    >
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                    </div>
                    <TextInput
                        id="password"
                        type="password"
                        :class="inputClass"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-1 text-xs sm:mt-1.5" :message="form.errors.password" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
                <label class="flex cursor-pointer items-center gap-1.5 rounded-lg px-0.5 py-0 transition-colors hover:bg-slate-50 sm:gap-2 sm:px-1 sm:py-0.5">
                    <Checkbox
                        name="remember"
                        v-model:checked="form.remember"
                        class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    />
                    <span class="text-xs text-slate-600 sm:text-sm">Recordar sesión</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs font-semibold text-indigo-600 underline-offset-2 transition hover:text-indigo-800 hover:underline sm:text-sm"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <div class="pt-0.5 sm:pt-1">
                <PrimaryButton
                    class="group/btn relative w-full justify-center overflow-hidden rounded-xl bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-600 bg-[length:200%_100%] py-2.5 text-sm font-semibold shadow-lg shadow-indigo-500/25 transition-all duration-300 hover:scale-[1.01] hover:bg-[position:100%_0] hover:shadow-indigo-500/35 active:scale-[0.99] motion-reduce:transform-none sm:py-3 sm:text-base"
                    :class="{ 'opacity-60': form.processing }"
                    :disabled="form.processing"
                >
                    <span
                        class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 opacity-0 transition-opacity duration-500 group-hover/btn:opacity-100 motion-reduce:opacity-0"
                    />
                    <span class="relative flex items-center justify-center gap-2">
                        <svg
                            v-if="form.processing"
                            class="h-5 w-5 animate-spin text-white motion-reduce:animate-none"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5 transition-transform duration-300 group-hover/btn:translate-x-0.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                            />
                        </svg>
                        {{ form.processing ? 'Iniciando sesión…' : 'Iniciar sesión' }}
                    </span>
                </PrimaryButton>
            </div>

            <p class="border-t border-slate-100 pt-3 text-center text-xs text-slate-600 sm:pt-4 sm:text-sm">
                ¿No tienes una cuenta?
                <Link
                    :href="route('register')"
                    class="font-bold text-indigo-600 underline-offset-2 transition hover:text-violet-700 hover:underline"
                >
                    Crear cuenta
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
