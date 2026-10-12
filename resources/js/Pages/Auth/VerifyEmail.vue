<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verificar correo" />

        <div class="text-center">
            <span class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </span>
            <h2 class="bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-800 bg-clip-text text-2xl font-bold tracking-tight text-transparent sm:text-3xl">
                Verifica tu correo
            </h2>
            <p class="mt-1 text-xs text-slate-600 sm:mt-1.5 sm:text-sm">
                Te enviamos un enlace de verificación al correo con el que te registraste.
                Ábrelo para activar tu cuenta; si no te llegó, puedes pedir otro.
            </p>
            <div class="mx-auto mt-3 h-0.5 w-12 rounded-full bg-gradient-to-r from-indigo-500 via-violet-500 to-cyan-400 sm:mt-4 sm:h-1 sm:w-16" />
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-3 mt-4 flex items-center gap-2 rounded-lg border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-teal-50/80 px-3 py-2 text-xs text-emerald-900 shadow-sm sm:mt-5 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm"
        >
            <svg class="h-4 w-4 shrink-0 text-emerald-600 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium">Te enviamos un enlace nuevo al correo de tu registro.</span>
        </div>

        <form @submit.prevent="submit" class="mt-4 space-y-3 sm:mt-5">
            <PrimaryButton
                class="group/btn relative w-full justify-center overflow-hidden rounded-xl bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-600 bg-[length:200%_100%] py-2.5 text-sm font-semibold shadow-lg shadow-indigo-500/25 transition-all duration-300 hover:scale-[1.01] hover:bg-[position:100%_0] active:scale-[0.99] motion-reduce:transform-none sm:py-3 sm:text-base"
                :class="{ 'opacity-60': form.processing }"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Enviando…' : 'Reenviar el correo de verificación' }}
            </PrimaryButton>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="block w-full rounded-lg py-2 text-center text-xs font-medium text-slate-600 transition-colors hover:text-slate-900 sm:text-sm"
            >
                Cerrar sesión
            </Link>
        </form>
    </GuestLayout>
</template>
