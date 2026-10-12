<template>
    <Head :title="evento.titulo" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center mb-4">
                        <Link :href="route('eventos.index')" class="text-gray-600 hover:text-gray-900 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900">{{ evento.titulo }}</h1>
                        </div>
                        <span :class="getEstadoBadgeClass(evento.estado)" class="px-4 py-2 text-sm font-medium rounded-full">
                            {{ evento.estado_texto || evento.estado }}
                        </span>
                    </div>
                </div>

                <!-- Mensajes de éxito/error -->
                <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Imagen del evento -->
                <div v-if="evento.imagen" class="mb-6 rounded-lg overflow-hidden shadow-lg">
                    <img :src="`/storage/${evento.imagen}`" :alt="evento.titulo" class="w-full h-96 object-cover">
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Contenido Principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Descripción -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Descripción del Evento</h2>
                            <p class="text-gray-700 whitespace-pre-line">{{ evento.descripcion }}</p>
                        </div>

                        <!-- Participantes (para Admin) -->
                        <div v-if="isAdmin && evento.participantes && evento.participantes.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-bold text-gray-900">Participantes Inscritos</h2>
                                <Link
                                    :href="route('eventos.participantes', evento.id)"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                >
                                    Ver todos ({{ evento.participantes.length }})
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <div v-for="participante in evento.participantes.slice(0, 5)" :key="participante.id" class="flex items-center space-x-3">
                                    <Avatar :usuario="participante.usuario" tamano="h-10 w-10" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ participante.usuario.name }}</p>
                                        <p class="text-xs text-gray-500">{{ participante.usuario.email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar con información -->
                    <div class="space-y-6">
                        <!-- Card de Información -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-bold text-gray-900 mb-4">Información del Evento</h2>
                            <div class="space-y-4">
                                <!-- Precio -->
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Precio</p>
                                        <p class="text-lg font-bold text-gray-700">{{ formatPrice(evento.precio) }}</p>
                                    </div>
                                </div>

                                <!-- Fecha de Inicio -->
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Fecha y Hora</p>
                                        <p class="text-sm text-gray-700">{{ formatDate(evento.fecha_inicio) }}</p>
                                        <p v-if="evento.fecha_fin" class="text-xs text-gray-500 mt-1">
                                            Hasta: {{ formatDate(evento.fecha_fin) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Ubicación -->
                                <div v-if="evento.ubicacion" class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Ubicación</p>
                                        <p class="text-sm text-gray-700">{{ evento.ubicacion }}</p>
                                    </div>
                                </div>

                                <!-- Cupos -->
                                <div v-if="evento.cupo_maximo" class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Cupos</p>
                                        <p class="text-sm text-gray-700">{{ evento.cupos_disponibles ?? evento.cupo_maximo }} de {{ evento.cupo_maximo }} disponibles</p>
                                    </div>
                                </div>

                                <!-- Organizador -->
                                <div v-if="evento.creador" class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Organizador</p>
                                        <p class="text-sm text-gray-700">{{ evento.creador.name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado de inscripción para estudiantes -->
                        <div v-if="!isAdmin" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div v-if="evento.esta_inscrito" class="text-center">
                                <!-- Estado específico de la inscripción -->
                                <div class="mb-4 p-4 rounded-xl shadow-sm"
                                     :class="getInscripcionClass(evento.estado_inscripcion)">
                                    <div class="flex items-center justify-center">
                                        <div class="flex-shrink-0 mr-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                                 :class="getInscripcionIconClass(evento.estado_inscripcion)">
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path v-if="evento.estado_inscripcion === 'aprobado'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <p class="font-bold text-sm" :class="getInscripcionTextClass(evento.estado_inscripcion)">
                                                {{ getInscripcionMessage(evento.estado_inscripcion) }}
                                            </p>
                                            <p class="text-xs mt-1" :class="getInscripcionSubtextClass(evento.estado_inscripcion)">
                                                {{ getInscripcionSubmessage(evento.estado_inscripcion) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón de cancelar solo si está pendiente o aprobado -->
                                <button
                                    v-if="['pendiente', 'aprobado'].includes(evento.estado_inscripcion)"
                                    @click="cancelarInscripcion"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                                >
                                    Cancelar Inscripción
                                </button>
                            </div>
                            <div v-else-if="evento.estado_dinamico === 'activo' && !evento.fue_rechazado">
                                <button
                                    @click="inscribirse"
                                    :disabled="evento.cupo_maximo && evento.cupos_disponibles <= 0"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ evento.cupo_maximo && evento.cupos_disponibles <= 0 ? 'Sin cupos disponibles' : 'Enviar Propuesta de Proyecto' }}
                                </button>
                            </div>
                            <div
                                v-else-if="evento.fue_rechazado && evento.estado_dinamico === 'activo'"
                                class="rounded-md border border-red-200 bg-red-50 p-4 text-center"
                            >
                                <div class="mb-2 flex items-center justify-center gap-2 text-red-700">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span class="font-semibold">Propuesta rechazada</span>
                                </div>
                                <p v-if="evento.propuesta_rechazada?.motivo_rechazo" class="mb-3 whitespace-pre-line text-left text-sm text-red-800">
                                    {{ evento.propuesta_rechazada.motivo_rechazo }}
                                </p>
                                <button
                                    @click="corregirYReenviar"
                                    :disabled="evento.cupo_maximo && evento.cupos_disponibles <= 0"
                                    class="w-full rounded-md bg-violet-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{ evento.cupo_maximo && evento.cupos_disponibles <= 0 ? 'Sin cupos disponibles' : 'Corregir y reenviar' }}
                                </button>
                            </div>
                            <div v-else-if="evento.fue_rechazado" class="text-center p-3 bg-red-100 text-red-700 rounded-md">
                                <div class="flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span class="font-semibold">Propuesta Rechazada</span>
                                </div>
                                <p class="text-sm">Este evento ya cerró, así que no puedes reenviar tu propuesta corregida.</p>
                            </div>
                            <div v-else-if="evento.estado_dinamico === 'en_curso'" class="text-center p-3 bg-orange-100 text-orange-700 rounded-md">
                                <div class="flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="font-semibold">Evento en Curso</span>
                                </div>
                                <p class="text-sm">Este evento ya está en curso. No se permiten más inscripciones.</p>
                            </div>
                            <div v-else-if="evento.estado_dinamico === 'finalizado'" class="text-center p-3 bg-blue-100 text-blue-700 rounded-md">
                                <div class="flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="font-semibold">Evento Finalizado</span>
                                </div>
                                <p class="text-sm">Este evento ya ha finalizado.</p>
                            </div>
                            <div v-else class="text-center p-3 bg-gray-100 text-gray-700 rounded-md">
                                Este evento no está disponible para inscripciones
                            </div>
                        </div>

                        <!-- Acciones para Admin -->
                        <div v-if="isAdmin" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Acciones</h3>
                            <div class="space-y-3">
                                <Link
                                    :href="route('eventos.participantes', evento.id)"
                                    class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium text-center transition-colors duration-200"
                                >
                                    Ver Participantes
                                </Link>
                                <Link
                                    :href="route('eventos.edit', evento.id)"
                                    class="block w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md font-medium text-center transition-colors duration-200"
                                >
                                    Editar Evento
                                </Link>
                                <button
                                    @click="confirmDelete"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                                >
                                    Eliminar Evento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Inscripción con Propuesta -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>

                <!-- Modal Content -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <form @submit.prevent="submitPropuesta">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full">
                                    <h3 class="text-2xl font-bold text-gray-900 mb-4" id="modal-title">
                                        {{ modoCorreccion ? 'Corregir y reenviar propuesta' : 'Enviar Propuesta de Proyecto' }}
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-6">
                                        {{ modoCorreccion
                                            ? 'Ya rellenamos el formulario con lo que habías enviado. Corrige lo que se te señaló y sube el documento actualizado.'
                                            : 'Complete el formulario con los datos de su proyecto para inscribirse en el evento.' }}
                                    </p>

                                    <div
                                        v-if="modoCorreccion && evento.propuesta_rechazada?.motivo_rechazo"
                                        class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4"
                                    >
                                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">
                                            Motivo del rechazo anterior
                                        </p>
                                        <p class="mt-1 text-sm text-amber-900">{{ evento.propuesta_rechazada.motivo_rechazo }}</p>
                                    </div>

                                    <!-- Título del Proyecto -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Título del Proyecto *</label>
                                        <input
                                            v-model="form.titulo_proyecto"
                                            type="text"
                                            required
                                            maxlength="255"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Ingrese el título de su proyecto"
                                        />
                                        <div v-if="form.errors.titulo_proyecto" class="text-red-600 text-sm mt-1">{{ form.errors.titulo_proyecto }}</div>
                                    </div>

                                    <!-- Descripción -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción del Proyecto *</label>
                                        <textarea
                                            v-model="form.descripcion_proyecto"
                                            required
                                            maxlength="2000"
                                            rows="4"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Describa su proyecto (máx. 2000 caracteres)"
                                        ></textarea>
                                        <div class="text-xs text-gray-500 mt-1">{{ form.descripcion_proyecto?.length || 0 }} / 2000 caracteres</div>
                                        <div v-if="form.errors.descripcion_proyecto" class="text-red-600 text-sm mt-1">{{ form.errors.descripcion_proyecto }}</div>
                                    </div>

                                    <!-- Archivo del Proyecto -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Archivo del Proyecto (PDF, DOC, DOCX) *</label>
                                        <input
                                            @change="handleFileChange"
                                            type="file"
                                            accept=".pdf,.doc,.docx"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">Tamaño máximo: 10MB</p>
                                        <div v-if="form.errors.archivo_proyecto" class="text-red-600 text-sm mt-1">{{ form.errors.archivo_proyecto }}</div>
                                    </div>

                                    <!-- Modalidad de participación -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Modalidad de Participación *</label>
                                        <select
                                            v-model="form.modalidad"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option value="">Selecciona la modalidad</option>
                                            <option v-for="modalidad in modalidades" :key="modalidad" :value="modalidad">{{ modalidad }}</option>
                                        </select>
                                        <div v-if="form.errors.modalidad" class="text-red-600 text-sm mt-1">{{ form.errors.modalidad }}</div>
                                    </div>

                                    <!-- Área de participación -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Área de Participación *</label>
                                        <select
                                            v-model="form.area_participacion"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option value="">Selecciona el área</option>
                                            <option v-for="area in areasParticipacion" :key="area" :value="area">{{ area }}</option>
                                        </select>
                                        <div v-if="form.errors.area_participacion" class="text-red-600 text-sm mt-1">{{ form.errors.area_participacion }}</div>
                                    </div>

                                    <!-- Participantes -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Participantes del Proyecto *</label>
                                        <div v-for="(participante, index) in form.participantes" :key="index" class="mb-3 p-3 bg-gray-50 rounded-md">
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-sm font-medium text-gray-700">Participante {{ index + 1 }}</span>
                                                <button
                                                    v-if="form.participantes.length > 1"
                                                    type="button"
                                                    @click="removerParticipante(index)"
                                                    class="text-red-600 hover:text-red-800 text-sm"
                                                >
                                                    Eliminar
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <input
                                                        v-model="participante.nombre"
                                                        type="text"
                                                        required
                                                        placeholder="Nombre completo"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <div v-if="form.errors[`participantes.${index}.nombre`]" class="text-red-600 text-xs mt-1">
                                                        {{ form.errors[`participantes.${index}.nombre`] }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <input
                                                        v-model="participante.email"
                                                        type="email"
                                                        required
                                                        placeholder="correo@ejemplo.com"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <div v-if="form.errors[`participantes.${index}.email`]" class="text-red-600 text-xs mt-1">
                                                        {{ form.errors[`participantes.${index}.email`] }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="agregarParticipante"
                                            class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            + Agregar otro participante
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                :class="modoCorreccion ? 'bg-violet-600 hover:bg-violet-700 focus:ring-violet-500' : 'bg-green-600 hover:bg-green-700 focus:ring-green-500'"
                                class="w-full sm:w-auto inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50"
                            >
                                <span v-if="form.processing">Enviando...</span>
                                <span v-else>{{ modoCorreccion ? 'Reenviar propuesta corregida' : 'Enviar Propuesta' }}</span>
                            </button>
                            <button
                                type="button"
                                @click="showModal = false"
                                :disabled="form.processing"
                                class="mt-3 w-full sm:mt-0 sm:w-auto inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    evento: Object,
    auth: Object,
    modalidades: { type: Array, default: () => [] },
    areasParticipacion: { type: Array, default: () => [] },
});

const showModal = ref(false);
// Distingue un primer envío de una corrección, para el título del modal y
// para mostrar el motivo del rechazo anterior mientras se reenvía.
const modoCorreccion = ref(false);

const form = useForm({
    titulo_proyecto: '',
    descripcion_proyecto: '',
    archivo_proyecto: null,
    modalidad: '',
    area_participacion: '',
    participantes: [{ nombre: '', email: '' }]
});

const isAdmin = computed(() => {
    return props.auth?.user?.roles?.some(role => ['Administrador', 'Coordinador'].includes(role.name));
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatPrice = (price) => {
    return price > 0
        ? `$${parseFloat(price).toLocaleString('es-CO')}`
        : 'Gratis';
};

const getEstadoBadgeClass = (estado) => {
    const classes = {
        'activo': 'bg-green-100 text-green-800',
        'en_curso': 'bg-orange-100 text-orange-800',
        'inactivo': 'bg-gray-100 text-gray-800',
        'finalizado': 'bg-blue-100 text-blue-800',
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const agregarParticipante = () => {
    form.participantes.push({ nombre: '', email: '' });
};

const removerParticipante = (index) => {
    if (form.participantes.length > 1) {
        form.participantes.splice(index, 1);
    }
};

const handleFileChange = (event) => {
    form.archivo_proyecto = event.target.files[0];
};

const inscribirse = () => {
    modoCorreccion.value = false;
    showModal.value = true;
};

/**
 * Reenviar una propuesta rechazada: abre el mismo modal, pero prellenado con
 * lo que el estudiante ya había escrito, para que sólo corrija lo que señaló
 * el revisor. El archivo sigue siendo obligatorio: se exige un documento
 * actualizado en lugar de dejar pasar la misma versión ya rechazada.
 */
const corregirYReenviar = () => {
    modoCorreccion.value = true;
    showModal.value = true;

    const anterior = props.evento.propuesta_rechazada ?? {};
    form.reset();
    form.titulo_proyecto = anterior.titulo_proyecto ?? '';
    form.descripcion_proyecto = anterior.descripcion_proyecto ?? '';
    form.modalidad = anterior.modalidad ?? '';
    form.area_participacion = anterior.area_participacion ?? '';
    form.participantes = anterior.participantes?.length
        ? anterior.participantes.map((p) => ({ nombre: p.nombre, email: p.email }))
        : [{ nombre: '', email: '' }];
};

const submitPropuesta = () => {
    form.post(route('eventos.inscribir', props.evento.id), {
        forceFormData: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
            form.participantes = [{ nombre: '', email: '' }];
        }
    });
};

const cancelarInscripcion = () => {
    if (confirm(`¿Estás seguro de cancelar tu inscripción a "${props.evento.titulo}"?`)) {
        router.post(route('eventos.cancelar-inscripcion', props.evento.id), {}, {
            preserveScroll: true,
        });
    }
};

const confirmDelete = () => {
    if (confirm(`¿Estás seguro de eliminar el evento "${props.evento.titulo}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('eventos.destroy', props.evento.id));
    }
};

// Métodos para manejar estados de inscripción
const getInscripcionClass = (estado) => {
    const classes = {
        'pendiente': 'bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200',
        'aprobado': 'bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200',
        'rechazado': 'bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200',
        'inscrito': 'bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200',
        'asistio': 'bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200',
        'no_asistio': 'bg-gradient-to-r from-gray-50 to-slate-50 border-2 border-gray-200',
        'cancelado': 'bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200',
    };
    return classes[estado] || 'bg-gradient-to-r from-gray-50 to-slate-50 border-2 border-gray-200';
};

const getInscripcionIconClass = (estado) => {
    const classes = {
        'pendiente': 'bg-gradient-to-r from-yellow-500 to-orange-500',
        'aprobado': 'bg-gradient-to-r from-green-500 to-emerald-500',
        'rechazado': 'bg-gradient-to-r from-red-500 to-pink-500',
        'inscrito': 'bg-gradient-to-r from-blue-500 to-indigo-500',
        'asistio': 'bg-gradient-to-r from-green-500 to-emerald-500',
        'no_asistio': 'bg-gradient-to-r from-gray-500 to-slate-500',
        'cancelado': 'bg-gradient-to-r from-red-500 to-pink-500',
    };
    return classes[estado] || 'bg-gradient-to-r from-gray-500 to-slate-500';
};

const getInscripcionTextClass = (estado) => {
    const classes = {
        'pendiente': 'text-yellow-800',
        'aprobado': 'text-green-800',
        'rechazado': 'text-red-800',
        'inscrito': 'text-blue-800',
        'asistio': 'text-green-800',
        'no_asistio': 'text-gray-800',
        'cancelado': 'text-red-800',
    };
    return classes[estado] || 'text-gray-800';
};

const getInscripcionSubtextClass = (estado) => {
    const classes = {
        'pendiente': 'text-yellow-600',
        'aprobado': 'text-green-600',
        'rechazado': 'text-red-600',
        'inscrito': 'text-blue-600',
        'asistio': 'text-green-600',
        'no_asistio': 'text-gray-600',
        'cancelado': 'text-red-600',
    };
    return classes[estado] || 'text-gray-600';
};

const getInscripcionMessage = (estado) => {
    const messages = {
        'pendiente': 'Propuesta Enviada',
        'aprobado': '¡Inscripción Confirmada!',
        'rechazado': 'Propuesta Rechazada',
        'inscrito': 'Inscrito',
        'asistio': '¡Asistencia Confirmada!',
        'no_asistio': 'No Asistió',
        'cancelado': 'Inscripción Cancelada',
    };
    return messages[estado] || 'Estado Desconocido';
};

const getInscripcionSubmessage = (estado) => {
    const messages = {
        'pendiente': 'Tu propuesta está siendo revisada por los coordinadores',
        'aprobado': 'Tu participación está garantizada',
        'rechazado': 'Tu propuesta no fue aprobada para este evento',
        'inscrito': 'Estás inscrito en este evento',
        'asistio': 'Confirmaste tu asistencia al evento',
        'no_asistio': 'No asististe al evento',
        'cancelado': 'Has cancelado tu participación',
    };
    return messages[estado] || 'Estado no definido';
};
</script>



