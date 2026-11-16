<script setup>
import { computed, ref, watch } from 'vue';

/**
 * Campos del certificado, compartidos por «Emitir» y «Editar» para que las
 * dos pantallas pidan exactamente lo mismo.
 *
 * Recibe el objeto de useForm() del padre; el envío lo hace cada página.
 */
const props = defineProps({
    form: { type: Object, required: true },
    estudiantes: { type: Array, default: () => [] },
    eventos: { type: Array, default: () => [] },
    tipos: { type: Object, default: () => ({}) },
    // Sólo al editar: permite cambiar el estado (vigente / revocado).
    estados: { type: Object, default: null },
    // Sólo al editar: nombre del PDF propio que ya tiene el certificado.
    archivoActual: { type: String, default: null },
});

const hoy = new Date();
const fechaMaxima = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;

const archivoInput = ref(null);
const errorArchivo = ref('');

/* ---------- Título sugerido ----------
 * Mientras el título no se haya escrito a mano, se propone uno a partir del
 * tipo y del evento («Certificado de ponencia — Encuentro de Semilleros»).
 */
const tituloEditadoAMano = ref(Boolean(props.form.titulo));

const tituloSugerido = computed(() => {
    const tipo = props.tipos[props.form.tipo];
    if (!tipo) return '';
    const evento = props.eventos.find((e) => String(e.id) === String(props.form.evento_id));
    return `Certificado de ${tipo.toLowerCase()}${evento ? ` — ${evento.titulo}` : ''}`;
});

watch(tituloSugerido, (sugerido) => {
    if (!tituloEditadoAMano.value && sugerido) props.form.titulo = sugerido;
});

const alEscribirTitulo = () => {
    tituloEditadoAMano.value = props.form.titulo.trim() !== '' && props.form.titulo !== tituloSugerido.value;
};

/* ---------- Archivo PDF opcional ---------- */
const alElegirArchivo = (evento) => {
    const archivo = evento.target.files?.[0];
    errorArchivo.value = '';
    if (!archivo) return;

    if (archivo.type !== 'application/pdf') {
        errorArchivo.value = 'El archivo debe ser un PDF.';
        evento.target.value = '';
        return;
    }
    if (archivo.size > 10 * 1024 * 1024) {
        errorArchivo.value = 'El archivo no puede pesar más de 10 MB.';
        evento.target.value = '';
        return;
    }

    props.form.archivo_certificado = archivo;
    props.form.quitar_archivo = false;
};

const quitarArchivoNuevo = () => {
    props.form.archivo_certificado = null;
    if (archivoInput.value) archivoInput.value.value = '';
};

const nombreArchivoActual = computed(() => props.archivoActual?.split('/').pop() ?? '');

const tamanoLegible = (bytes) => (bytes < 1024 * 1024 ? `${Math.round(bytes / 1024)} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`);
</script>

<template>
    <div class="space-y-6">
        <!-- Datos del certificado -->
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-semibold text-gray-900">Datos del certificado</h3>
            <p class="mt-1 text-sm text-gray-500">Así aparecerá en el documento que recibe el estudiante.</p>

            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="tipo" class="mb-1.5 block text-sm font-medium text-gray-700">Tipo de certificado <span class="text-red-500">*</span></label>
                    <select id="tipo" v-model="form.tipo" required class="campo" :class="{ 'campo-error': form.errors.tipo }">
                        <option value="" disabled>Selecciona el tipo</option>
                        <option v-for="(etiqueta, valor) in tipos" :key="valor" :value="valor">{{ etiqueta }}</option>
                    </select>
                    <p v-if="form.errors.tipo" class="mensaje-error">{{ form.errors.tipo }}</p>
                </div>

                <div>
                    <label for="evento_id" class="mb-1.5 block text-sm font-medium text-gray-700">Evento</label>
                    <select id="evento_id" v-model="form.evento_id" class="campo" :class="{ 'campo-error': form.errors.evento_id }">
                        <option value="">Sin evento (reconocimiento general)</option>
                        <option v-for="evento in eventos" :key="evento.id" :value="evento.id">{{ evento.titulo }}</option>
                    </select>
                    <p v-if="form.errors.evento_id" class="mensaje-error">{{ form.errors.evento_id }}</p>
                </div>

                <div class="sm:col-span-2">
                    <label for="titulo" class="mb-1.5 block text-sm font-medium text-gray-700">Título <span class="text-red-500">*</span></label>
                    <input
                        id="titulo"
                        v-model="form.titulo"
                        @input="alEscribirTitulo"
                        type="text"
                        required
                        maxlength="255"
                        class="campo"
                        :class="{ 'campo-error': form.errors.titulo }"
                        placeholder="Se propone automáticamente al elegir el tipo y el evento"
                    />
                    <p v-if="form.errors.titulo" class="mensaje-error">{{ form.errors.titulo }}</p>
                    <p v-else-if="!form.evento_id" class="mt-1 text-xs text-gray-500">
                        Sin evento, el certificado dirá «… por su participación en <em>{{ form.titulo || 'este título' }}</em>».
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label for="descripcion" class="mb-1.5 block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea
                        id="descripcion"
                        v-model="form.descripcion"
                        rows="3"
                        maxlength="1000"
                        class="campo"
                        :class="{ 'campo-error': form.errors.descripcion }"
                        placeholder="Texto breve que se imprime bajo el motivo. Ej.: Presentó los resultados de su investigación ante el jurado evaluador."
                    ></textarea>
                    <div class="mt-1 flex justify-between gap-2 text-xs">
                        <p v-if="form.errors.descripcion" class="text-red-600">{{ form.errors.descripcion }}</p>
                        <span class="ml-auto text-gray-400">{{ (form.descripcion || '').length }}/1000</span>
                    </div>
                </div>

                <div>
                    <label for="fecha_emision" class="mb-1.5 block text-sm font-medium text-gray-700">Fecha de emisión <span class="text-red-500">*</span></label>
                    <input id="fecha_emision" v-model="form.fecha_emision" type="date" required :max="fechaMaxima" class="campo" :class="{ 'campo-error': form.errors.fecha_emision }" />
                    <p v-if="form.errors.fecha_emision" class="mensaje-error">{{ form.errors.fecha_emision }}</p>
                </div>

                <div>
                    <label for="horas" class="mb-1.5 block text-sm font-medium text-gray-700">Intensidad horaria</label>
                    <div class="relative">
                        <input id="horas" v-model="form.horas" type="number" min="1" max="1000" step="1" inputmode="numeric" class="campo pr-16" :class="{ 'campo-error': form.errors.horas }" placeholder="Opcional" />
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">horas</span>
                    </div>
                    <p v-if="form.errors.horas" class="mensaje-error">{{ form.errors.horas }}</p>
                </div>

                <div v-if="estados" class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700">Estado</span>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label
                            v-for="(etiqueta, valor) in estados"
                            :key="valor"
                            :class="[
                                'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors',
                                form.estado === valor
                                    ? (valor === 'revocado' ? 'border-red-400 bg-red-50' : 'border-emerald-400 bg-emerald-50')
                                    : 'border-gray-200 hover:bg-gray-50',
                            ]"
                        >
                            <input v-model="form.estado" type="radio" :value="valor" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                            <span>
                                <span class="block text-sm font-medium text-gray-900">{{ etiqueta }}</span>
                                <span class="block text-xs text-gray-500">
                                    {{ valor === 'revocado' ? 'Queda anulado: el estudiante ya no puede descargarlo.' : 'Válido y disponible para descarga.' }}
                                </span>
                            </span>
                        </label>
                    </div>
                    <p v-if="form.errors.estado" class="mensaje-error">{{ form.errors.estado }}</p>
                </div>
            </div>
        </section>

        <!-- Destinatario -->
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-semibold text-gray-900">Destinatario</h3>
            <div class="mt-4">
                <label for="user_id" class="mb-1.5 block text-sm font-medium text-gray-700">Estudiante <span class="text-red-500">*</span></label>
                <select id="user_id" v-model="form.user_id" required class="campo" :class="{ 'campo-error': form.errors.user_id }">
                    <option value="" disabled>Selecciona el estudiante</option>
                    <option v-for="estudiante in estudiantes" :key="estudiante.id" :value="estudiante.id">
                        {{ estudiante.name }} — {{ estudiante.email }}
                    </option>
                </select>
                <p v-if="form.errors.user_id" class="mensaje-error">{{ form.errors.user_id }}</p>
            </div>
        </section>

        <!-- Documento -->
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="text-base font-semibold text-gray-900">Documento PDF</h3>
            <div class="mt-3 flex items-start gap-3 rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>
                    No necesitas adjuntar nada: el sistema genera el certificado con el formato institucional.
                    Adjunta un PDF sólo si ya tienes uno firmado y prefieres entregar ese.
                </p>
            </div>

            <!-- PDF propio que ya tenía (sólo al editar) -->
            <div v-if="archivoActual && !form.archivo_certificado" class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 p-3">
                <div class="flex min-w-0 items-center gap-3">
                    <svg class="h-8 w-8 shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium" :class="form.quitar_archivo ? 'text-gray-400 line-through' : 'text-gray-900'">{{ nombreArchivoActual }}</p>
                        <p class="text-xs text-gray-500">{{ form.quitar_archivo ? 'Se quitará al guardar y se usará el certificado generado.' : 'PDF propio adjunto actualmente.' }}</p>
                    </div>
                </div>
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.quitar_archivo" type="checkbox" class="rounded text-red-600 focus:ring-red-500" />
                    Quitar y usar el generado
                </label>
            </div>

            <div class="mt-4">
                <div v-if="form.archivo_certificado" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-emerald-900">{{ form.archivo_certificado.name }}</p>
                        <p class="text-xs text-emerald-700">{{ tamanoLegible(form.archivo_certificado.size) }} · se adjuntará al guardar</p>
                    </div>
                    <button type="button" @click="quitarArchivoNuevo" class="text-sm font-medium text-red-600 hover:text-red-700">Quitar</button>
                </div>

                <label
                    v-else
                    for="archivo_certificado"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 px-6 py-6 text-center transition-colors hover:border-indigo-400 hover:bg-gray-50"
                >
                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <span class="mt-2 text-sm font-medium text-indigo-600">{{ archivoActual ? 'Reemplazar por otro PDF' : 'Adjuntar un PDF propio (opcional)' }}</span>
                    <span class="mt-0.5 text-xs text-gray-500">Sólo PDF, hasta 10 MB</span>
                    <input id="archivo_certificado" ref="archivoInput" type="file" accept="application/pdf,.pdf" class="sr-only" @change="alElegirArchivo" />
                </label>
                <p v-if="errorArchivo || form.errors.archivo_certificado" class="mensaje-error">{{ errorArchivo || form.errors.archivo_certificado }}</p>
            </div>
        </section>

        <!-- Notas internas -->
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <label for="observaciones" class="block text-base font-semibold text-gray-900">Observaciones internas</label>
            <p class="mt-1 text-sm text-gray-500">No se imprimen en el certificado; sólo las ven quienes gestionan certificados.</p>
            <textarea id="observaciones" v-model="form.observaciones" rows="2" maxlength="1000" class="campo mt-3" :class="{ 'campo-error': form.errors.observaciones }"></textarea>
            <p v-if="form.errors.observaciones" class="mensaje-error">{{ form.errors.observaciones }}</p>
        </section>
    </div>
</template>

<style scoped>
.campo {
    @apply block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
}
.campo-error {
    @apply border-red-400 focus:border-red-500 focus:ring-red-500;
}
.mensaje-error {
    @apply mt-1 text-sm text-red-600;
}
</style>
