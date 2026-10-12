<template>
    <Head title="Eventos" />

    <AppLayout :auth="auth">
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Eventos Académicos</h1>
                            <p class="text-gray-600 mt-2">
                                {{ isAdmin ? 'Gestiona los eventos académicos' : 'Explora y participa en eventos académicos' }}
                            </p>
                        </div>
                        <div v-if="isAdmin" class="flex space-x-3">
                            <Link
                                :href="route('eventos.create')"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Nuevo Evento
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Mensajes de éxito/error -->
                <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Sin esto el estudiante recorría una lista de eventos ya
                     cerrados sin ningún botón y sin saber por qué. -->
                <div
                    v-if="eventosAbiertos === 0 && eventos.length > 0"
                    class="animar-entrada mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3"
                >
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-amber-900">
                            Por ahora no hay eventos con inscripciones abiertas
                        </p>
                        <p class="mt-0.5 text-sm text-amber-800">
                            Todos los eventos publicados ya comenzaron o terminaron, por eso no aparece
                            el botón para enviar propuestas. Cuando el coordinador programe un evento
                            nuevo lo verás aquí arriba.
                        </p>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar evento</label>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por título o ubicación..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div v-if="isAdmin">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select
                                v-model="selectedEstado"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Todos los estados</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="finalizado">Finalizado</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="w-full bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200"
                            >
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de eventos -->
                <div v-if="filteredEventos.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="evento in filteredEventos" :key="evento.id"
                         class="tarjeta-interactiva animar-entrada overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <!-- Imagen del evento -->
                        <div v-if="evento.imagen" class="h-48 bg-gray-200 overflow-hidden">
                            <img :src="`/storage/${evento.imagen}`" :alt="evento.titulo" class="w-full h-full object-cover">
                        </div>
                        <div v-else class="h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <svg class="w-16 h-16 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <div class="p-6 bg-gradient-to-br from-white to-gray-50">
                            <!-- Header de la tarjeta -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 line-clamp-2">
                                        {{ evento.titulo }}
                                    </h3>
                                </div>
                                <span :class="getEstadoBadgeClass(evento.estado_dinamico)" class="px-4 py-2 text-xs font-bold rounded-full ml-2 shadow-sm">
                                    <div class="flex items-center">
                                        <div :class="getEstadoDotClass(evento.estado_dinamico)" class="w-2 h-2 rounded-full mr-2"></div>
                                        {{ evento.estado_texto || evento.estado_dinamico }}
                                    </div>
                                </span>
                            </div>

                            <!-- Descripción -->
                            <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                                {{ evento.descripcion }}
                            </p>

                            <!-- Detalles del evento -->
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ formatDate(evento.fecha_inicio) }}</span>
                                </div>
                                <div v-if="evento.ubicacion" class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>{{ evento.ubicacion }}</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="font-semibold">{{ formatPrice(evento.precio) }}</span>
                                </div>
                                <div v-if="evento.cupo_maximo" class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>{{ evento.cupos_disponibles ?? evento.cupo_maximo }} cupos disponibles</span>
                                </div>
                            </div>

                            <!-- Motivo del rechazo: para que el estudiante sepa qué corregir sin
                                 tener que ir a otra pantalla -->
                            <div
                                v-if="!isAdmin && evento.fue_rechazado && evento.propuesta_rechazada?.motivo_rechazo"
                                class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4"
                            >
                                <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-red-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    Propuesta rechazada
                                </p>
                                <p class="mt-1 text-sm text-red-800">{{ evento.propuesta_rechazada.motivo_rechazo }}</p>
                                <p class="mt-1.5 text-xs text-red-600">Corrígela y envíala de nuevo cuando quieras.</p>
                            </div>

                            <!-- Estado de inscripción para estudiantes -->
                            <div v-if="!isAdmin && evento.esta_inscrito" class="mb-4 p-4 rounded-xl shadow-sm"
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

                            <!-- Acciones -->
                            <div class="flex gap-3">
                                <!-- Botones para Admin/Coordinador -->
                                <template v-if="isAdmin">
                                    <Link
                                        :href="route('eventos.participantes', evento.id)"
                                        class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-3 rounded-lg text-sm font-semibold text-center transition-all duration-200 shadow-md hover:shadow-lg min-w-0"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <span class="truncate">Ver Inscritos ({{ evento.participantes_count || 0 }})</span>
                                        </div>
                                    </Link>
                                    <Link
                                        :href="route('eventos.edit', evento.id)"
                                        class="flex-1 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-4 py-3 rounded-lg text-sm font-semibold text-center transition-all duration-200 shadow-md hover:shadow-lg min-w-0"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span class="truncate">Editar</span>
                                        </div>
                                    </Link>
                                    <button
                                        @click="confirmDelete(evento)"
                                        class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-4 py-3 rounded-lg text-sm font-semibold text-center transition-all duration-200 shadow-md hover:shadow-lg min-w-0"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span class="truncate">Eliminar</span>
                                        </div>
                                    </button>
                                </template>

                                <!-- Botón para Estudiantes -->
                                <template v-else>
                                    <button
                                        v-if="!evento.esta_inscrito && evento.estado_dinamico === 'activo' && !evento.fue_rechazado"
                                        @click="inscribirse(evento)"
                                        :disabled="evento.cupo_maximo && evento.cupos_disponibles <= 0"
                                        class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg v-if="!(evento.cupo_maximo && evento.cupos_disponibles <= 0)" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span>{{ evento.cupo_maximo && evento.cupos_disponibles <= 0 ? 'Sin cupos' : 'Enviar Propuesta' }}</span>
                                        </div>
                                    </button>

                                    <!-- Propuesta rechazada: el estudiante puede corregirla y reenviarla -->
                                    <button
                                        v-if="evento.fue_rechazado && evento.estado_dinamico === 'activo'"
                                        @click="corregirYReenviar(evento)"
                                        :disabled="evento.cupo_maximo && evento.cupos_disponibles <= 0"
                                        class="flex-1 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span>{{ evento.cupo_maximo && evento.cupos_disponibles <= 0 ? 'Sin cupos' : 'Corregir y reenviar' }}</span>
                                        </div>
                                    </button>

                                    <!-- Rechazada y el evento ya cerró: no hay nada más que hacer -->
                                    <button
                                        v-if="evento.fue_rechazado && evento.estado_dinamico !== 'activo'"
                                        disabled
                                        class="flex-1 bg-gradient-to-r from-gray-400 to-gray-500 text-white px-4 py-3 rounded-xl text-sm font-bold cursor-not-allowed shadow-lg"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            <span>Propuesta Rechazada</span>
                                        </div>
                                    </button>

                                    <!-- Botón para evento en curso -->
                                    <button
                                        v-if="evento.estado_dinamico === 'en_curso' && !evento.fue_rechazado"
                                        disabled
                                        class="flex-1 bg-gradient-to-r from-orange-400 to-amber-500 text-white px-4 py-3 rounded-xl text-sm font-bold cursor-not-allowed shadow-lg"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Evento en Curso</span>
                                        </div>
                                    </button>

                                    <!-- Botón para evento finalizado -->
                                    <button
                                        v-if="evento.estado_dinamico === 'finalizado' && !evento.fue_rechazado"
                                        disabled
                                        class="flex-1 bg-gradient-to-r from-blue-400 to-indigo-500 text-white px-4 py-3 rounded-xl text-sm font-bold cursor-not-allowed shadow-lg"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Evento Finalizado</span>
                                        </div>
                                    </button>
                                    <button
                                        v-if="evento.esta_inscrito"
                                        @click="cancelarInscripcion(evento)"
                                        class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 shadow-lg hover:shadow-xl"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            <span>Cancelar</span>
                                        </div>
                                    </button>
                                    <Link
                                        :href="route('eventos.show', evento.id)"
                                        class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-3 rounded-xl text-sm font-bold text-center transition-all duration-200 shadow-lg hover:shadow-xl"
                                    >
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Ver Detalles</span>
                                        </div>
                                    </Link>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje cuando no hay eventos -->
                <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay eventos disponibles</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ isAdmin ? 'Comienza creando un nuevo evento.' : 'No hay eventos disponibles en este momento.' }}
                    </p>
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

                                    <!-- Motivo del rechazo anterior, visible mientras se corrige -->
                                    <div
                                        v-if="modoCorreccion && eventoSeleccionado?.propuesta_rechazada?.motivo_rechazo"
                                        class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4"
                                    >
                                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">
                                            Motivo del rechazo anterior
                                        </p>
                                        <p class="mt-1 text-sm text-amber-900">
                                            {{ eventoSeleccionado.propuesta_rechazada.motivo_rechazo }}
                                        </p>
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
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    eventos: Array,
    auth: Object,
    // Cuántos eventos admiten propuestas ahora. Llega null para los roles que
    // gestionan eventos, porque el aviso es solo para el estudiante.
    eventosAbiertos: { type: Number, default: null },
    modalidades: { type: Array, default: () => [] },
    areasParticipacion: { type: Array, default: () => [] },
});

const search = ref('');
const selectedEstado = ref('');

const isAdmin = computed(() => {
    return props.auth?.user?.roles?.some(role => ['Administrador', 'Coordinador'].includes(role.name));
});

// Variables para el modal
const showModal = ref(false);
const eventoSeleccionado = ref(null);
// Distingue un primer envío de una corrección, para el título del modal y
// para mostrar el motivo del rechazo anterior mientras se reenvía.
const modoCorreccion = ref(false);

// Formulario para la propuesta
const form = useForm({
    titulo_proyecto: '',
    descripcion_proyecto: '',
    archivo_proyecto: null,
    modalidad: '',
    area_participacion: '',
    participantes: [
        { nombre: '', email: '' }
    ]
});

const filteredEventos = computed(() => {
    let filtered = props.eventos || [];

    if (search.value) {
        const searchLower = search.value.toLowerCase();
        filtered = filtered.filter(evento =>
            evento.titulo.toLowerCase().includes(searchLower) ||
            (evento.ubicacion && evento.ubicacion.toLowerCase().includes(searchLower))
        );
    }

    if (selectedEstado.value) {
        filtered = filtered.filter(evento => evento.estado === selectedEstado.value);
    }

    return filtered;
});

const clearFilters = () => {
    search.value = '';
    selectedEstado.value = '';
};

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
        'activo': 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200',
        'en_curso': 'bg-gradient-to-r from-orange-100 to-amber-100 text-orange-800 border border-orange-200',
        'inactivo': 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-200',
        'finalizado': 'bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border border-blue-200',
    };
    return classes[estado] || 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-200';
};

const getEstadoDotClass = (estado) => {
    const classes = {
        'activo': 'bg-green-500',
        'en_curso': 'bg-orange-500',
        'inactivo': 'bg-gray-500',
        'finalizado': 'bg-blue-500',
    };
    return classes[estado] || 'bg-gray-500';
};

const inscribirse = (evento) => {
    // Abrir el modal con el formulario de inscripción
    eventoSeleccionado.value = evento;
    modoCorreccion.value = false;
    showModal.value = true;

    // Resetear el formulario
    form.reset();
    form.participantes = [{ nombre: '', email: '' }];
};

/**
 * Reenviar una propuesta rechazada: abre el mismo modal, pero prellenado con
 * lo que el estudiante ya había escrito, para que sólo corrija lo que señaló
 * el revisor en lugar de escribir todo de nuevo. El archivo sí es obligatorio
 * de nuevo: forzar a subir un documento actualizado evita que se apruebe por
 * error la misma versión que ya fue rechazada.
 */
const corregirYReenviar = (evento) => {
    eventoSeleccionado.value = evento;
    modoCorreccion.value = true;
    showModal.value = true;

    const anterior = evento.propuesta_rechazada ?? {};
    form.reset();
    form.titulo_proyecto = anterior.titulo_proyecto ?? '';
    form.descripcion_proyecto = anterior.descripcion_proyecto ?? '';
    form.modalidad = anterior.modalidad ?? '';
    form.area_participacion = anterior.area_participacion ?? '';
    form.participantes = anterior.participantes?.length
        ? anterior.participantes.map((p) => ({ nombre: p.nombre, email: p.email }))
        : [{ nombre: '', email: '' }];
};

const cancelarInscripcion = (evento) => {
    if (confirm(`¿Estás seguro de cancelar tu inscripción a "${evento.titulo}"?`)) {
        router.post(route('eventos.cancelar-inscripcion', evento.id), {}, {
            preserveScroll: true,
        });
    }
};

// Funciones para el modal
const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.archivo_proyecto = file;
    }
};

const agregarParticipante = () => {
    form.participantes.push({ nombre: '', email: '' });
};

const removerParticipante = (index) => {
    if (form.participantes.length > 1) {
        form.participantes.splice(index, 1);
    }
};

const submitPropuesta = () => {
    if (!eventoSeleccionado.value) return;

    form.post(route('eventos.inscribir', eventoSeleccionado.value.id), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
            form.participantes = [{ nombre: '', email: '' }];
        }
    });
};

const confirmDelete = (evento) => {
    if (confirm(`¿Estás seguro de eliminar el evento "${evento.titulo}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('eventos.destroy', evento.id));
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
