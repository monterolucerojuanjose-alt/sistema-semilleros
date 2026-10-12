<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};

// Mismas clases que la pantalla de acceso, para que las dos se vean iguales.
const inputClass =
    '!py-2 pl-9 text-sm mt-0.5 block w-full rounded-lg border-gray-200 bg-white/80 shadow-sm ring-1 ring-gray-200/80 transition duration-200 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 sm:!py-2.5 sm:pl-10 sm:rounded-xl';
</script>

<template>
    <GuestLayout>
        <Head title="Confirmar contraseña" />

        <div class="text-center">
            <span class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </span>
            <h2 class="bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-800 bg-clip-text text-2xl font-bold tracking-tight text-transparent sm:text-3xl">
                Confirma tu contraseña
            </h2>
            <p class="mt-1 text-xs text-slate-600 sm:mt-1.5 sm:text-sm">
                Esta es una zona protegida del sistema. Vuelve a escribir tu contraseña para continuar.
            </p>
            <div class="mx-auto mt-3 h-0.5 w-12 rounded-full bg-gradient-to-r from-indigo-500 via-violet-500 to-cyan-400 sm:mt-4 sm:h-1 sm:w-16" />
        </div>

        <form @submit.prevent="submit" class="mt-4 space-y-3 sm:mt-5 sm:space-y-4">
            <div class="group">
                <InputLabel for="password" value="Contraseña" class="mb-1 block text-xs font-semibold text-slate-700 sm:text-sm" />
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 transition-colors group-focus-within:text-indigo-500 sm:pl-3">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <TextInput
                        id="password"
                        type="password"
                        :class="inputClass"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        autofocus
                        placeholder="Tu contraseña actual"
                    />
                </div>
                <InputError class="mt-1 text-xs sm:mt-1.5" :message="form.errors.password" />
            </div>

            <PrimaryButton
                class="group/btn relative w-full justify-center overflow-hidden rounded-xl bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-600 bg-[length:200%_100%] py-2.5 text-sm font-semibold shadow-lg shadow-indigo-500/25 transition-all duration-300 hover:scale-[1.01] hover:bg-[position:100%_0] active:scale-[0.99] motion-reduce:transform-none sm:py-3 sm:text-base"
                :class="{ 'opacity-60': form.processing }"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Comprobando…' : 'Confirmar' }}
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
