<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    semillero: Object,
    tutores: Array,
    auth: Object,
});

const form = useForm({
    nombre: props.semillero.nombre,
    descripcion: props.semillero.descripcion,
    linea_tematica: props.semillero.linea_tematica,
    tutor_id: props.semillero.tutor_id,
    estado: props.semillero.estado,
    cupo_maximo: props.semillero.cupo_maximo ?? '',
    // Vacío conserva la clave actual; con quitar_clave se elimina.
    clave_acceso: '',
    quitar_clave: false,
});

const submit = () => {
    form.put(route('semilleros.update', props.semillero.id));
};
</script>

<template>
    <Head title="Editar Semillero" />

    <AppLayout :auth="auth">
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Header -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-900">Editar Semillero</h1>
                            <p class="text-gray-600 mt-2">Modifica la información del semillero "{{ semillero.nombre }}"</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Nombre del semillero -->
                            <div>
                                <InputLabel for="nombre" value="Nombre del Semillero" />
                                <TextInput
                                    id="nombre"
                                    v-model="form.nombre"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Ej: Semillero de Inteligencia Artificial"
                                    required
                                />
                                <InputError :message="form.errors.nombre" class="mt-2" />
                            </div>

                            <!-- Línea temática -->
                            <div>
                                <InputLabel for="linea_tematica" value="Línea Temática" />
                                <TextInput
                                    id="linea_tematica"
                                    v-model="form.linea_tematica"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Ej: Inteligencia Artificial y Machine Learning"
                                    required
                                />
                                <InputError :message="form.errors.linea_tematica" class="mt-2" />
                            </div>

                            <!-- Tutor responsable -->
                            <div>
                                <InputLabel for="tutor_id" value="Tutor Responsable" />
                                <select
                                    id="tutor_id"
                                    v-model="form.tutor_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="">Seleccionar tutor</option>
                                    <option v-for="tutor in tutores" :key="tutor.id" :value="tutor.id">
                                        {{ tutor.name }} ({{ tutor.email }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.tutor_id" class="mt-2" />
                            </div>

                            <!-- Estado -->
                            <div>
                                <InputLabel for="estado" value="Estado" />
                                <select
                                    id="estado"
                                    v-model="form.estado"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Un semillero inactivo deja de admitir nuevas solicitudes de ingreso.
                                </p>
                                <InputError :message="form.errors.estado" class="mt-2" />
                            </div>

                            <!-- Control de acceso e ingreso -->
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <h3 class="text-sm font-semibold text-gray-900">Ingreso de estudiantes</h3>

                                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="cupo_maximo" value="Cupo máximo de integrantes" />
                                        <TextInput
                                            id="cupo_maximo"
                                            v-model="form.cupo_maximo"
                                            type="number"
                                            min="1"
                                            max="500"
                                            class="mt-1 block w-full"
                                            placeholder="Sin límite"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Integrantes aprobados actualmente:
                                            {{ semillero.miembros_aprobados?.length ?? 0 }}
                                        </p>
                                        <InputError :message="form.errors.cupo_maximo" class="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel for="clave_acceso" value="Clave de acceso" />
                                        <TextInput
                                            id="clave_acceso"
                                            v-model="form.clave_acceso"
                                            type="text"
                                            class="mt-1 block w-full"
                                            :placeholder="semillero.requiere_clave ? 'Escribe una nueva para cambiarla' : 'Sin clave'"
                                            :disabled="form.quitar_clave"
                                            autocomplete="off"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ semillero.requiere_clave
                                                ? 'Este semillero ya tiene clave. Déjalo vacío para conservarla.'
                                                : 'Si la defines, el estudiante deberá escribirla para solicitar el ingreso.' }}
                                        </p>
                                        <label v-if="semillero.requiere_clave" class="mt-2 flex items-center gap-2 text-xs text-gray-700">
                                            <input
                                                type="checkbox"
                                                v-model="form.quitar_clave"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            Quitar la clave y dejar el semillero abierto
                                        </label>
                                        <InputError :message="form.errors.clave_acceso" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Descripción -->
                            <div>
                                <InputLabel for="descripcion" value="Descripción" />
                                <textarea
                                    id="descripcion"
                                    v-model="form.descripcion"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    placeholder="Describe los objetivos, metodología y alcance del semillero..."
                                    required
                                ></textarea>
                                <InputError :message="form.errors.descripcion" class="mt-2" />
                            </div>

                            <!-- Botones -->
                            <div class="flex items-center justify-end space-x-4">
                                <Link
                                    :href="route('semilleros.index')"
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200"
                                >
                                    Cancelar
                                </Link>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200"
                                >
                                    {{ form.processing ? 'Actualizando...' : 'Actualizar Semillero' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>





