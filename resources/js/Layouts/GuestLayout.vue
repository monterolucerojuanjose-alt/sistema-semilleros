<script setup>
import GuestParticleCanvas from '@/Components/GuestParticleCanvas.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    /**
     * Sin scroll dentro de la tarjeta: la página crece y el scroll es el del documento
     * (p. ej. registro con muchos campos).
     */
    naturalScroll: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="relative flex min-h-0 flex-col bg-white"
        :class="
            props.naturalScroll
                ? 'min-h-[100svh] overflow-x-hidden'
                : 'h-[100svh] overflow-hidden'
        "
    >
        <!-- Capa base: gradiente claro animado -->
        <div
            class="pointer-events-none absolute inset-0 z-0 bg-gradient-to-br from-sky-50 via-white to-indigo-100 bg-[length:400%_400%] animate-gradient-flow motion-reduce:animate-none"
            aria-hidden="true"
        />

        <div
            class="pointer-events-none absolute inset-0 z-0 overflow-hidden motion-reduce:animate-none"
            aria-hidden="true"
        >
            <div
                class="absolute -left-1/4 top-0 h-[28rem] w-[28rem] rounded-full bg-gradient-to-br from-indigo-200/50 via-violet-200/35 to-transparent blur-3xl animate-float sm:h-[36rem] sm:w-[36rem]"
            />
            <div
                class="absolute -right-1/4 bottom-0 h-[26rem] w-[26rem] rounded-full bg-gradient-to-tl from-cyan-200/40 via-sky-200/30 to-transparent blur-3xl animate-float-slow sm:h-[34rem] sm:w-[34rem]"
            />
            <div
                class="absolute left-1/3 top-1/4 h-56 w-56 rounded-full bg-violet-200/25 blur-3xl motion-reduce:animate-none animate-drift sm:h-72 sm:w-72"
            />
            <div
                class="absolute right-1/4 bottom-1/3 h-48 w-48 rounded-full bg-indigo-100/40 blur-2xl motion-reduce:animate-none animate-pulse-soft sm:h-64 sm:w-64"
            />
        </div>

        <!-- Partículas tipo “píldora” + repulsión al cursor (Canvas) -->
        <GuestParticleCanvas />

        <div
            class="pointer-events-none absolute inset-0 z-[1] flex select-none items-center justify-center overflow-hidden"
            aria-hidden="true"
        >
            <p
                class="max-w-[100vw] rotate-[-10deg] px-4 text-center text-[clamp(0.85rem,3vw,1.75rem)] font-semibold leading-none tracking-tight text-indigo-600/[0.05] sm:text-3xl"
            >
                usuario@umariana.edu.co
            </p>
        </div>

        <!--
          Login: tarjeta centrada; cuerpo con scroll interno si hace falta.
          naturalScroll: sin scroll en la tarjeta; scroll de página.
        -->
        <div
            class="relative z-10 flex w-full flex-col px-3 py-3 sm:px-5 sm:py-4"
            :class="
                props.naturalScroll
                    ? 'min-h-[100svh] flex-1 justify-start py-8 sm:py-10'
                    : 'min-h-0 flex-1 overflow-hidden'
            "
        >
            <div
                class="mx-auto flex w-full max-w-md flex-col"
                :class="
                    props.naturalScroll
                        ? ''
                        : 'h-full min-h-0 flex-1 justify-center'
                "
            >
                <div
                    class="flex w-full flex-col gap-2 sm:gap-3"
                    :class="props.naturalScroll ? '' : 'max-h-full min-h-0'"
                >
                    <div
                        class="relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white/95 shadow-lg shadow-slate-200/60 ring-1 ring-slate-100 sm:rounded-3xl sm:shadow-xl"
                        :class="
                            props.naturalScroll
                                ? ''
                                : 'max-h-[min(100%,calc(100svh-6.5rem))] min-h-0 sm:max-h-[min(100%,calc(100svh-7rem))]'
                        "
                    >
                        <div
                            class="pointer-events-none absolute inset-x-0 top-0 z-10 h-px bg-gradient-to-r from-transparent via-indigo-300/60 to-transparent"
                            aria-hidden="true"
                        />

                        <div
                            class="relative z-10 shrink-0 border-b border-slate-100/90 bg-white/98 px-4 py-3 sm:px-6 sm:py-4"
                        >
                            <div
                                class="flex justify-center opacity-0 motion-reduce:opacity-100 motion-reduce:transform-none animate-fade-in-up motion-reduce:animate-none"
                            >
                                <Link
                                    href="/login"
                                    class="group flex items-center gap-3 rounded-xl p-0.5 transition-transform duration-300 hover:scale-[1.02] focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white sm:gap-4"
                                >
                                    <div
                                        class="relative overflow-hidden rounded-xl border border-slate-200/90 bg-white p-2 shadow-md ring-1 ring-slate-200/80 transition-all duration-300 group-hover:border-indigo-200 group-hover:shadow-lg group-hover:shadow-indigo-500/15 group-hover:ring-indigo-300/40 sm:rounded-2xl sm:p-2.5"
                                    >
                                        <div
                                            class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                            style="
                                                background: linear-gradient(
                                                    135deg,
                                                    rgba(99, 102, 241, 0.2) 0%,
                                                    rgba(255, 255, 255, 0) 100%
                                                );
                                            "
                                        />
                                        <img
                                            src="/images/5c2a2a6b4c875f31275aa66759e3307f.webp"
                                            alt=""
                                            class="relative h-10 w-10 object-contain sm:h-12 sm:w-12"
                                        />
                                    </div>
                                    <div class="min-w-0 text-left">
                                        <h1
                                            class="text-base font-bold tracking-tight text-slate-900 sm:text-xl"
                                        >
                                            <span
                                                class="bg-gradient-to-r from-slate-900 via-indigo-800 to-indigo-700 bg-clip-text text-transparent"
                                            >
                                                SEMILLEROS
                                            </span>
                                        </h1>
                                        <p
                                            class="text-[11px] font-medium text-slate-600 transition-colors group-hover:text-indigo-700 sm:text-sm"
                                        >
                                            Sistema de Gestión
                                        </p>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <div
                            class="[-webkit-overflow-scrolling:touch]"
                            :class="
                                props.naturalScroll
                                    ? ''
                                    : 'min-h-0 flex-1 overflow-y-auto overscroll-contain'
                            "
                        >
                            <div
                                class="opacity-0 motion-reduce:opacity-100 motion-reduce:transform-none animate-fade-in-up motion-reduce:animate-none [animation-delay:120ms] [animation-fill-mode:forwards]"
                            >
                                <div
                                    class="rounded-b-[calc(1rem-1px)] bg-white px-4 py-4 sm:rounded-b-[calc(1.5rem-1px)] sm:px-8 sm:py-5"
                                >
                                    <slot />
                                </div>
                            </div>
                        </div>
                    </div>

                    <p
                        class="shrink-0 text-center text-[10px] leading-tight text-slate-400 opacity-0 motion-reduce:opacity-100 motion-reduce:animate-none animate-fade-in [animation-delay:280ms] [animation-fill-mode:forwards] sm:text-xs"
                    >
                        © {{ new Date().getFullYear() }} Sistema de Semilleros de Investigación
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
