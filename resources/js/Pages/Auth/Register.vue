<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputClass =
    '!py-2 pl-9 text-sm mt-0.5 block w-full rounded-lg border-gray-200 bg-white/80 shadow-sm ring-1 ring-gray-200/80 transition duration-200 placeholder:text-gray-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 sm:!py-2.5 sm:pl-10 sm:rounded-xl';
</script>

<template>
    <GuestLayout natural-scroll>
        <Head title="Crear cuenta" />

        <div class="text-center">
            <div
                class="mb-1.5 inline-flex items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50/90 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-800 sm:mb-2 sm:gap-2 sm:px-3 sm:py-1 sm:text-xs"
            >
                <svg class="h-3.5 w-3.5 text-emerald-600 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                    />
                </svg>
                Nuevo usuario
            </div>
            <h2
                class="bg-gradient-to-br from-slate-900 via-emerald-900/90 to-slate-800 bg-clip-text text-2xl font-bold tracking-tight text-transparent sm:text-3xl"
            >
                Crea tu cuenta
            </h2>
            <p class="mt-1 text-xs text-slate-600 sm:mt-1.5 sm:text-sm">
                Únete al sistema de semilleros de investigación
            </p>
            <div
                class="mx-auto mt-3 h-0.5 w-12 rounded-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-400 sm:mt-4 sm:h-1 sm:w-16"
            />
        </div>

        <form @submit.prevent="submit" class="mt-4 space-y-2.5 pb-1 sm:mt-5 sm:space-y-3 sm:pb-2">
            <div class="group">
                <InputLabel
                    for="name"
                    value="Nombre completo"
                    class="mb-1 block text-xs font-semibold text-slate-700 sm:text-sm"
                />
                <div class="relative">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 transition-colors group-focus-within:text-emerald-600 sm:pl-3"
                    >
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>
                    <TextInput
                        id="name"
                        type="text"
                        :class="inputClass"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Tu nombre completo"
                    />
                </div>
                <InputError class="mt-1 text-xs sm:mt-1.5" :message="form.errors.name" />
            </div>

            <div class="group">
                <InputLabel
                    for="email"
                    value="Correo electrónico"
                    class="mb-1 block text-xs font-semibold text-slate-700 sm:text-sm"
                />
                <div class="relative">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 transition-colors group-focus-within:text-emerald-600 sm:pl-3"
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
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 transition-colors group-focus-within:text-emerald-600 sm:pl-3"
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
                        autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                    />
                </div>
                <InputError class="mt-1 text-xs sm:mt-1.5" :message="form.errors.password" />
            </div>

            <div class="group">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmar contraseña"
                    class="mb-1 block text-xs font-semibold text-slate-700 sm:text-sm"
                />
                <div class="relative">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 transition-colors group-focus-within:text-emerald-600 sm:pl-3"
                    >
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        :class="inputClass"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repite tu contraseña"
                    />
                </div>
                <InputError class="mt-1 text-xs sm:mt-1.5" :message="form.errors.password_confirmation" />
            </div>

            <div
                class="rounded-lg border border-emerald-100 bg-gradient-to-br from-emerald-50/90 to-teal-50/50 p-2.5 shadow-sm ring-1 ring-emerald-500/10 sm:rounded-xl sm:p-3"
            >
                <div class="flex gap-2 sm:gap-3">
                    <div
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 sm:h-8 sm:w-8 sm:rounded-lg"
                    >
                        <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="min-w-0 text-left text-[11px] leading-snug text-emerald-900/90 sm:text-xs">
                        <p class="font-semibold text-emerald-950">Privacidad</p>
                        <p class="mt-0.5 text-emerald-800/90">
                            Usamos tus datos solo para gestionar tu cuenta en el sistema de semilleros.
                        </p>
                    </div>
                </div>
            </div>

            <div class="pt-0.5 sm:pt-1">
                <PrimaryButton
                    class="group/btn relative w-full justify-center overflow-hidden rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 bg-[length:200%_100%] py-2.5 text-sm font-semibold shadow-lg shadow-emerald-500/25 transition-all duration-300 hover:scale-[1.01] hover:bg-[position:100%_0] hover:shadow-emerald-500/35 active:scale-[0.99] motion-reduce:transform-none sm:py-3 sm:text-base"
                    :class="{ 'opacity-60': form.processing }"
                    :disabled="form.processing"
                >
                    <span
                        class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 opacity-0 transition-opacity duration-500 group-hover/btn:opacity-100 motion-reduce:opacity-0"
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
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                            />
                        </svg>
                        {{ form.processing ? 'Creando cuenta…' : 'Crear cuenta' }}
                    </span>
                </PrimaryButton>
            </div>

            <p class="border-t border-slate-100 pt-3 text-center text-xs text-slate-600 sm:pt-4 sm:text-sm">
                ¿Ya tienes una cuenta?
                <Link
                    :href="route('login')"
                    class="font-bold text-indigo-600 underline-offset-2 transition hover:text-violet-700 hover:underline"
                >
                    Iniciar sesión
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
