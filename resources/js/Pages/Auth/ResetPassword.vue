<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

// Mismas clases que la pantalla de acceso, para que las dos se vean iguales.
const inputClass =
    '!py-2 pl-9 text-sm mt-0.5 block w-full rounded-lg border-gray-200 bg-white/80 shadow-sm ring-1 ring-gray-200/80 transition duration-200 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 sm:!py-2.5 sm:pl-10 sm:rounded-xl';

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Restablecer contraseña" />

        <div class="text-center">
            <div
                class="mb-1.5 inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50/80 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-indigo-700 sm:mb-2 sm:gap-2 sm:px-3 sm:py-1 sm:text-xs"
            >
                <span class="relative flex h-1.5 w-1.5 sm:h-2 sm:w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-indigo-400 opacity-75 motion-reduce:animate-none" />
                    <span class="relative inline-flex h-full w-full rounded-full bg-indigo-600" />
                </span>
                Nueva contraseña
            </div>
            <h2
                class="bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-800 bg-clip-text text-2xl font-bold tracking-tight text-transparent sm:text-3xl"
            >
                Restablece tu contraseña
            </h2>
            <p class="mt-1 text-xs text-slate-600 sm:mt-1.5 sm:text-sm">
                Elige una contraseña nueva para tu cuenta.
            </p>
            <div class="mx-auto mt-3 h-0.5 w-12 rounded-full bg-gradient-to-r from-indigo-500 via-violet-500 to-cyan-400 sm:mt-4 sm:h-1 sm:w-16" />
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
                    />
                </div>
                <InputError class="mt-1 text-xs sm:mt-1.5" :message="form.errors.email" />
            </div>

            <div class="group">
                <InputLabel for="password" value="Nueva contraseña" class="mb-1 block text-xs font-semibold text-slate-700 sm:text-sm" />
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
                        autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                    />
                </div>
                <InputError class="mt-1 text-xs sm:mt-1.5" :message="form.errors.password" />
            </div>

            <div class="group">
                <InputLabel for="password_confirmation" value="Confirmar contraseña" class="mb-1 block text-xs font-semibold text-slate-700 sm:text-sm" />
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 transition-colors group-focus-within:text-indigo-500 sm:pl-3">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        :class="inputClass"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repite la contraseña"
                    />
                </div>
                <InputError class="mt-1 text-xs sm:mt-1.5" :message="form.errors.password_confirmation" />
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
                    {{ form.processing ? 'Guardando…' : 'Guardar contraseña' }}
                </span>
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
