<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateProfilePhotoForm from './Partials/UpdateProfilePhotoForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const auth = computed(() => page.props.auth);
const user = computed(() => page.props.auth?.user || {});
</script>

<template>
    <Head title="Mi Perfil" />

    <AppLayout :auth="auth" title="Mi Perfil">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <img
                        v-if="user.avatar_url"
                        :src="user.avatar_url"
                        alt=""
                        class="h-12 w-12 rounded-full object-cover ring-2 ring-indigo-100 shadow"
                    />
                    <div
                        v-else
                        class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xl font-bold shadow"
                    >
                        {{ (user.name || 'U').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ user.name }}</h2>
                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div
                    v-if="status && status !== 'verification-link-sent'"
                    class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm"
                    role="status"
                >
                    {{ status }}
                </div>
                <div class="space-y-6">
                    <div class="bg-white p-6 shadow rounded-lg">
                        <UpdateProfilePhotoForm />
                    </div>

                    <div class="bg-white p-6 shadow rounded-lg">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Información</h3>
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>

                    <div class="bg-white p-6 shadow rounded-lg">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Seguridad</h3>
                        <UpdatePasswordForm />
                    </div>

                    <div class="bg-white p-6 shadow rounded-lg">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Zona peligrosa</h3>
                        <DeleteUserForm />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
