<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

const page = usePage();
const user = computed(() => page.props.auth.user);
const fileInput = ref(null);
const cropImgRef = ref(null);
const showCropModal = ref(false);
const cropImageSrc = ref('');
let cropperInstance = null;

const form = useForm({
    avatar: null,
});

const previewUrl = ref(null);

watch(
    () => user.value?.avatar_url,
    () => {
        if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = null;
        }
    }
);

const displayImage = computed(() => {
    if (previewUrl.value) {
        return previewUrl.value;
    }
    return user.value?.avatar_url || null;
});

function destroyCropper() {
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
}

function onCropImageLoad() {
    nextTick(() => {
        destroyCropper();
        if (!cropImgRef.value) {
            return;
        }
        cropperInstance = new Cropper(cropImgRef.value, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            responsive: true,
            background: false,
        });
    });
}

function closeCropModal() {
    showCropModal.value = false;
    destroyCropper();
    if (cropImageSrc.value) {
        URL.revokeObjectURL(cropImageSrc.value);
        cropImageSrc.value = '';
    }
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function onFileChange(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }
    if (!file.type.startsWith('image/')) {
        return;
    }
    if (cropImageSrc.value) {
        URL.revokeObjectURL(cropImageSrc.value);
    }
    cropImageSrc.value = URL.createObjectURL(file);
    showCropModal.value = true;
}

function confirmCrop() {
    if (!cropperInstance) {
        return;
    }
    const canvas = cropperInstance.getCroppedCanvas({
        width: 400,
        height: 400,
        imageSmoothingQuality: 'high',
    });
    canvas.toBlob(
        (blob) => {
            if (!blob) {
                return;
            }
            const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
            form.avatar = file;
            if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl.value);
            }
            previewUrl.value = URL.createObjectURL(blob);
            destroyCropper();
            showCropModal.value = false;
            if (cropImageSrc.value) {
                URL.revokeObjectURL(cropImageSrc.value);
                cropImageSrc.value = '';
            }
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        'image/jpeg',
        0.92
    );
}

const submitAvatar = () => {
    if (!form.avatar) {
        return;
    }
    form.post(route('profile.avatar.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('avatar');
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl.value);
                previewUrl.value = null;
            }
        },
    });
};

const removeForm = useForm({});

const removeAvatar = () => {
    removeForm.delete(route('profile.avatar.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            previewUrl.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            form.reset('avatar');
        },
    });
};

const openPicker = () => {
    fileInput.value?.click();
};

const clearSelection = () => {
    form.reset('avatar');
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
};

watch(showCropModal, (open) => {
    if (!open) {
        destroyCropper();
    }
});

onUnmounted(() => {
    destroyCropper();
    if (cropImageSrc.value) {
        URL.revokeObjectURL(cropImageSrc.value);
    }
    if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <section>
        <header class="mb-5">
            <h2 class="text-lg font-medium text-gray-900">
                Foto de perfil
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Elige una imagen, recórtala a un cuadrado en el siguiente paso y, cuando estés conforme, guárdala en tu cuenta (JPG o PNG, máx. 2&nbsp;MB al subir).
            </p>
        </header>

        <div
            class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 via-white to-slate-50 p-5 shadow-sm ring-1 ring-black/5"
        >
            <div class="flex flex-col items-center gap-5 sm:flex-row sm:items-start">
                <div class="relative shrink-0">
                    <div
                        class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-3xl font-bold text-white shadow-lg ring-4 ring-white"
                    >
                        <img
                            v-if="displayImage"
                            :src="displayImage"
                            alt="Foto de perfil"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>
                            {{ (user?.name || 'U').charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <span
                        v-if="user?.avatar_url || previewUrl"
                        class="absolute bottom-1 right-1 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-white shadow ring-2 ring-white"
                        title="Imagen activa o pendiente"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>
                </div>

                <div class="min-w-0 flex-1 space-y-4 text-center sm:text-left">
                    <input
                        ref="fileInput"
                        type="file"
                        class="hidden"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        @change="onFileChange"
                    />

                    <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                        <SecondaryButton type="button" @click="openPicker">
                            Elegir y recortar imagen
                        </SecondaryButton>
                        <PrimaryButton
                            type="button"
                            :disabled="!form.avatar || form.processing"
                            @click="submitAvatar"
                        >
                            Guardar en mi perfil
                        </PrimaryButton>
                    </div>

                    <p v-if="form.avatar && !form.processing" class="text-xs text-gray-500">
                        Vista previa lista. Pulsa «Guardar en mi perfil» para subirla al servidor.
                        <button
                            type="button"
                            class="ml-1 font-medium text-indigo-600 underline hover:text-indigo-800"
                            @click="clearSelection"
                        >
                            Descartar
                        </button>
                    </p>

                    <div
                        v-if="user?.avatar_url && !form.avatar"
                        class="flex justify-center sm:justify-start"
                    >
                        <DangerButton
                            type="button"
                            :disabled="removeForm.processing"
                            @click="removeAvatar"
                        >
                            Quitar foto actual
                        </DangerButton>
                    </div>

                    <InputError class="text-center sm:text-left" :message="form.errors.avatar" />
                </div>
            </div>
        </div>

        <Modal :show="showCropModal" max-width="4xl" @close="closeCropModal">
            <div class="p-5 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-900">
                    Recortar imagen
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Arrastra y ajusta el encuadre (proporción 1:1). Luego pulsa «Aplicar recorte».
                </p>

                <div
                    class="mt-4 max-h-[min(60vh,480px)] w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-100"
                >
                    <img
                        v-show="cropImageSrc"
                        ref="cropImgRef"
                        :src="cropImageSrc"
                        class="block max-h-[min(60vh,480px)] w-full"
                        alt="Vista para recortar"
                        @load="onCropImageLoad"
                    />
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-2 border-t border-gray-100 pt-4">
                    <SecondaryButton type="button" @click="closeCropModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton type="button" @click="confirmCrop">
                        Aplicar recorte
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
