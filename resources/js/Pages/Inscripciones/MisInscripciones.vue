<template>
    <AppLayout title="Mis Inscripciones" :auth="page.props.auth">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 shadow-sm shadow-indigo-500/30">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 3.5h6a1 1 0 0 1 1 1V6H8V4.5a1 1 0 0 1 1-1Z" />
                            <path d="M16 5h1.5A1.5 1.5 0 0 1 19 6.5v13A1.5 1.5 0 0 1 17.5 21h-11A1.5 1.5 0 0 1 5 19.5v-13A1.5 1.5 0 0 1 6.5 5H8" />
                            <path d="M8.75 12.4l1.9 1.9 3.85-3.85" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">Mis inscripciones</h2>
                        <p class="text-sm text-gray-500">
                            Tus ponencias, pósteres y proyectos presentados a eventos
                        </p>
                    </div>
                </div>
                <Link
                    :href="route('eventos.index')"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Ver eventos
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Indicadores: además de informar, filtran al pulsarlos -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <button
                    v-for="tarjeta in tarjetas" :key="tarjeta.etiqueta"
                    type="button"
                    @click="selectedEstado = tarjeta.estado"
                    :class="[
                        'cursor-pointer rounded-xl border bg-white p-4 text-left shadow-sm transition-colors hover:border-indigo-300 hover:bg-indigo-50/40',
                        selectedEstado === tarjeta.estado ? 'border-indigo-400 ring-1 ring-indigo-400' : 'border-gray-200'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <span :class="['inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', tarjeta.fondo]">
                            <svg class="h-5 w-5" :class="tarjeta.color" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="tarjeta.icono" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-gray-500">{{ tarjeta.etiqueta }}</p>
                            <p class="text-xl font-semibold text-gray-900">{{ tarjeta.valor }}</p>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="relative min-w-0 flex-1 sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 110-16 8 8 0 010 16z" />
                    </svg>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar por proyecto…"
                        class="w-full rounded-lg border-gray-300 py-1.5 pl-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <select v-model="selectedEstado" class="rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos los estados</option>
                    <option v-for="estado in estados" :key="estado" :value="estado">{{ estado }}</option>
                </select>
                <select v-model="selectedTipo" class="rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos los tipos</option>
                    <option v-for="tipo in tipos" :key="tipo" :value="tipo">{{ tipo }}</option>
                </select>
                <button
                    v-if="search || selectedEstado || selectedTipo"
                    @click="limpiarFiltros"
                    class="text-xs font-medium text-indigo-600 hover:underline"
                >
                    Quitar filtros
                </button>
                <span class="ml-auto text-xs text-gray-500">
                    {{ filteredInscripciones.length }}
                    {{ filteredInscripciones.length === 1 ? 'registro' : 'registros' }}
                </span>
            </div>

            <!-- Listado: una sola maqueta para escritorio y móvil -->
            <div v-if="filteredInscripciones.length > 0" class="space-y-3">
                <article
                    v-for="inscripcion in filteredInscripciones" :key="inscripcion.id"
                    class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <!-- Distintivo del tipo de participación -->
                        <span :class="['inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', tipoInfo(inscripcion).fondo]">
                            <svg class="h-6 w-6" :class="tipoInfo(inscripcion).color" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="tipoInfo(inscripcion).icono" />
                            </svg>
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="break-words text-base font-semibold text-gray-900">
                                    {{ inscripcion.proyecto?.titulo || 'Sin título' }}
                                </h3>
                                <span :class="['shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold', estadoClase(inscripcion.estado)]">
                                    {{ inscripcion.estado }}
                                </span>
                            </div>

                            <!-- Datos de la inscripción -->
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a2 2 0 011.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 014 10V5a2 2 0 012-2z" />
                                    </svg>
                                    {{ tipoInfo(inscripcion).etiqueta }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 font-mono">
                                    {{ inscripcion.codigo_inscripcion }}
                                </span>
                                <span v-if="inscripcion.propuesta_data?.evento" class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ inscripcion.propuesta_data.evento.titulo }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ formatDate(inscripcion.created_at) }}
                                </span>
                            </div>

                            <!-- Aula y horario, cuando la propuesta ya fue programada -->
                            <div
                                v-if="inscripcion.propuesta_data?.aula || inscripcion.propuesta_data?.horario || inscripcion.propuesta_data?.evaluador"
                                class="mt-3 flex flex-wrap gap-2"
                            >
                                <span v-if="inscripcion.propuesta_data.aula" class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    Aula: {{ inscripcion.propuesta_data.aula }}
                                </span>
                                <span v-if="inscripcion.propuesta_data.horario" class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    Horario: {{ inscripcion.propuesta_data.horario }}
                                </span>
                                <span v-if="inscripcion.propuesta_data.evaluador" class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                    Jurado: {{ inscripcion.propuesta_data.evaluador.name }}
                                </span>
                            </div>

                            <!-- Motivo del rechazo, para que el estudiante sepa qué corregir -->
                            <p
                                v-if="inscripcion.propuesta_data?.motivo_rechazo"
                                class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"
                            >
                                <strong>Motivo:</strong> {{ inscripcion.propuesta_data.motivo_rechazo }}
                            </p>
                        </div>

                        <!-- Acciones -->
                        <div class="flex shrink-0 flex-wrap gap-2 sm:flex-col">
                            <template v-if="inscripcion.tipo_inscripcion !== 'propuesta_evento'">
                                <Link
                                    :href="route('inscripciones.show', inscripcion.id)"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-medium text-white transition-colors hover:bg-indigo-700"
                                >
                                    Ver detalle
                                </Link>
                                <Link
                                    :href="route('inscripciones.comprobante', inscripcion.id)"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Comprobante
                                </Link>
                            </template>

                            <template v-else>
                                <button
                                    @click="verDetallePropuesta(inscripcion)"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-medium text-white transition-colors hover:bg-indigo-700"
                                >
                                    Ver detalle
                                </button>
                                <!-- Propuesta rechazada: se corrige desde la pantalla del evento,
                                     donde vive el formulario de reenvío. -->
                                <Link
                                    v-if="inscripcion.propuesta_data?.estado === 'rechazado' && inscripcion.propuesta_data?.evento?.id"
                                    :href="route('eventos.show', inscripcion.propuesta_data.evento.id)"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-violet-600 px-3.5 py-2 text-xs font-medium text-white transition-colors hover:bg-violet-700"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Corregir y reenviar
                                </Link>
                                <a
                                    v-if="inscripcion.propuesta_data?.archivo_proyecto"
                                    :href="`/storage/${inscripcion.propuesta_data.archivo_proyecto}`"
                                    target="_blank"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Archivo
                                </a>
                            </template>
                        </div>
                    </div>
                </article>

                <!-- Paginación -->
                <div v-if="inscripciones.prev_page_url || inscripciones.next_page_url" class="flex items-center justify-between pt-2">
                    <Link
                        v-if="inscripciones.prev_page_url"
                        :href="inscripciones.prev_page_url"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Anteriores
                    </Link>
                    <span v-else></span>
                    <Link
                        v-if="inscripciones.next_page_url"
                        :href="inscripciones.next_page_url"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Siguientes
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Sin resultados -->
            <div v-else class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
                <h3 class="mt-3 text-sm font-medium text-gray-900">
                    {{ hayFiltros ? 'Ninguna inscripción coincide con el filtro' : 'Todavía no tienes inscripciones' }}
                </h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                    {{ hayFiltros
                        ? 'Prueba con otro estado o tipo de participación.'
                        : 'Cuando inscribas un proyecto a un evento o presentes una propuesta, aparecerá aquí con su estado.' }}
                </p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <button
                        v-if="hayFiltros"
                        @click="limpiarFiltros"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Quitar filtros
                    </button>
                    <Link
                        v-else
                        :href="route('eventos.index')"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        Ver eventos disponibles
                    </Link>
                </div>
            </div>
        </div>

        <!-- Modal para ver detalle de propuesta -->
        <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="cerrarModal">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-xl bg-white" @click.stop>
                <div class="mt-3">
                    <!-- Header del modal -->
                    <div class="flex items-center justify-between pb-4 border-b">
                        <h3 class="text-xl font-bold text-gray-900">Detalle del Proyecto Aprobado</h3>
                        <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Contenido del modal -->
                    <div class="mt-6" v-if="propuestaSeleccionada">
                        <!-- Información del evento -->
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="text-lg font-bold text-blue-900 mb-2">{{ propuestaSeleccionada.evento.titulo }}</h4>
                        </div>

                        <!-- ESTADO: APROBADO -->
                        <div v-if="propuestaSeleccionada.estado === 'aprobado'">
                            <!-- Información de programación -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                    <div class="flex items-center mb-2">
                                        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-sm font-bold text-gray-600 uppercase tracking-wide">AULA ASIGNADA</span>
                                    </div>
                                    <p class="text-lg font-bold text-gray-900">{{ propuestaSeleccionada.aula || 'No asignada' }}</p>
                                </div>

                                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                    <div class="flex items-center mb-2">
                                        <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-sm font-bold text-gray-600 uppercase tracking-wide">HORARIO</span>
                                    </div>
                                    <p class="text-lg font-bold text-gray-900">{{ propuestaSeleccionada.horario || 'No asignado' }}</p>
                                </div>
                            </div>

                            <!-- Jurado asignado -->
                            <div class="mb-6" v-if="propuestaSeleccionada.evaluador">
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <div class="flex items-center mb-2">
                                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-sm font-bold text-gray-600 uppercase tracking-wide">JURADO ASIGNADO</span>
                                    </div>
                                    <p class="text-lg font-bold text-gray-900">{{ propuestaSeleccionada.evaluador.name }}</p>
                                    <p class="text-sm text-gray-600">{{ propuestaSeleccionada.evaluador.email }}</p>
                                </div>
                            </div>

                            <!-- Programación completa -->
                            <div class="mb-6" v-if="propuestaSeleccionada.programacion">
                                <div class="bg-gray-50 p-4 rounded-lg border">
                                    <div class="flex items-center mb-3">
                                        <svg class="w-5 h-5 text-purple-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-sm font-bold text-gray-600 uppercase tracking-wide">PROGRAMACIÓN COMPLETA</span>
                                    </div>
                                    <p class="text-gray-700 whitespace-pre-line leading-relaxed">{{ propuestaSeleccionada.programacion }}</p>
                                </div>
                            </div>

                            <!-- Información de revisión -->
                            <div v-if="propuestaSeleccionada.revisor" class="mt-6 pt-4 border-t border-gray-200">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Aprobado por:</span> {{ propuestaSeleccionada.revisor.name }}<br>
                                    <span class="font-medium">Fecha de aprobación:</span> {{ formatDate(propuestaSeleccionada.fecha_revision) }}
                                </p>
                            </div>
                        </div>

                        <!-- ESTADO: PENDIENTE -->
                        <div v-if="propuestaSeleccionada.estado === 'pendiente'" class="p-6 bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-bold text-yellow-800">En Revisión</h3>
                                    <p class="text-yellow-700">Tu propuesta está siendo evaluada por el administrador</p>
                                </div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border border-yellow-200">
                                <p class="text-sm text-gray-600">⏳ El proceso de revisión puede tomar algunos días. Te notificaremos cuando tengamos una respuesta.</p>
                            </div>
                        </div>

                        <!-- ESTADO: RECHAZADO -->
                        <div v-if="propuestaSeleccionada.estado === 'rechazado'" class="p-6 bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200 rounded-xl">
                            <div class="flex items-center mb-6">
                                <div class="flex-shrink-0">
                                    <svg class="h-10 w-10 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-xl font-bold text-red-800">No Aceptada</h3>
                                    <p class="text-red-700 font-medium">Tu propuesta no fue aprobada</p>
                                </div>
                            </div>

                            <div class="bg-white p-5 rounded-lg border border-red-200 mb-4" v-if="propuestaSeleccionada.motivo_rechazo">
                                <div class="flex items-center mb-3">
                                    <svg class="w-5 h-5 text-orange-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm font-bold text-gray-600 uppercase tracking-wide">OBSERVACIONES DEL EVALUADOR</span>
                                </div>
                                <p class="text-gray-700 whitespace-pre-line leading-relaxed">{{ propuestaSeleccionada.motivo_rechazo }}</p>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-blue-800">💡 Sugerencia:</p>
                                        <p class="text-sm text-blue-700">Toma en cuenta las observaciones para mejorar tu propuesta y participa en futuros eventos.</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="propuestaSeleccionada.revisor" class="mt-4 pt-4 border-t border-red-200">
                                <p class="text-xs text-gray-600">
                                    <span class="font-medium">Revisado por:</span> {{ propuestaSeleccionada.revisor.name }}<br>
                                    <span class="font-medium">Fecha:</span> {{ formatDate(propuestaSeleccionada.fecha_revision) }}
                                </p>
                            </div>
                        </div>

                        <!-- Participantes (para todos los estados) -->
                        <div class="mb-6" v-if="propuestaSeleccionada.participantes">
                            <h4 class="text-sm font-semibold text-gray-600 mb-3">PARTICIPANTES</h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="(p, idx) in propuestaSeleccionada.participantes" :key="idx" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 text-sm rounded-full border border-blue-200 font-medium">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                    {{ p.nombre }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Botones del modal -->
                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                        <button
                            @click="cerrarModal"
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg font-medium transition-colors duration-200"
                        >
                            Cerrar
                        </button>
                        <a
                            v-if="propuestaSeleccionada && propuestaSeleccionada.archivo_proyecto"
                            :href="`/storage/${propuestaSeleccionada.archivo_proyecto}`"
                            target="_blank"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200"
                        >
                            Ver Archivo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    inscripciones: Object,
    propuestasEventosAprobadas: Array, // NUEVO
    propuestasPendientesRechazadas: Array, // NUEVO
    resumen: Object,
    semilleros: Array,
    estados: Array,
    tipos: Array,
    filtros: Object,
});

const page = usePage();

// Variables reactivas para filtros
const search = ref(props.filtros?.buscar || '');
const selectedEstado = ref(props.filtros?.estado || '');
const selectedTipo = ref(props.filtros?.tipo || '');

// Combinar inscripciones tradicionales con todas las propuestas de eventos
const allInscripciones = computed(() => {
    const inscripcionesTradicionales = props.inscripciones?.data || [];
    const propuestasAprobadas = props.propuestasEventosAprobadas || [];
    const propuestasPendientesRechazadas = props.propuestasPendientesRechazadas || [];

    // Convertir propuestas aprobadas al formato de la tabla
    const propuestasAprobadasFormateadas = propuestasAprobadas.map(propuesta => ({
        id: `propuesta_aprobada_${propuesta.id}`,
        tipo_inscripcion: 'propuesta_evento',
        proyecto: {
            titulo: propuesta.titulo_proyecto
        },
        codigo_inscripcion: `PE-${propuesta.id}`,
        tipo: 'Proyecto de Evento',
        estado: 'Aprobado',
        created_at: propuesta.created_at,
        // Datos específicos de la propuesta
        propuesta_data: {
            evento: propuesta.evento,
            aula: propuesta.aula,
            horario: propuesta.horario,
            programacion: propuesta.programacion,
            participantes: propuesta.participantes,
            archivo_proyecto: propuesta.archivo_proyecto,
            revisor: propuesta.revisor,
            evaluador: propuesta.evaluador,
            fecha_revision: propuesta.fecha_revision,
            estado: 'aprobado'
        }
    }));

    // Convertir propuestas pendientes y rechazadas al formato de la tabla
    const propuestasPendientesRechazadasFormateadas = propuestasPendientesRechazadas.map(propuesta => ({
        id: `propuesta_${propuesta.estado}_${propuesta.id}`,
        tipo_inscripcion: 'propuesta_evento',
        proyecto: {
            titulo: propuesta.titulo_proyecto
        },
        codigo_inscripcion: `PE-${propuesta.id}`,
        tipo: 'Proyecto de Evento',
        estado: propuesta.estado === 'pendiente' ? 'En revisión' : 'Rechazado',
        created_at: propuesta.created_at,
        // Datos específicos de la propuesta
        propuesta_data: {
            evento: propuesta.evento,
            aula: propuesta.aula,
            horario: propuesta.horario,
            programacion: propuesta.programacion,
            participantes: propuesta.participantes,
            archivo_proyecto: propuesta.archivo_proyecto,
            revisor: propuesta.revisor,
            fecha_revision: propuesta.fecha_revision,
            estado: propuesta.estado,
            motivo_rechazo: propuesta.motivo_rechazo,
            intentos_envio: propuesta.intentos_envio,
        }
    }));

    // Combinar y ordenar por fecha
    return [...inscripcionesTradicionales, ...propuestasAprobadasFormateadas, ...propuestasPendientesRechazadasFormateadas]
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

// Usar los datos combinados
const filteredInscripciones = computed(() => {
    return allInscripciones.value;
});

// Aplicar filtros con debounce
let searchTimeout;
watch([search, selectedEstado, selectedTipo], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const filtros = {};
        if (search.value) filtros.buscar = search.value;
        if (selectedEstado.value) filtros.estado = selectedEstado.value;
        if (selectedTipo.value) filtros.tipo = selectedTipo.value;

        router.get(route('mis-inscripciones'), filtros, {
            preserveState: true,
            preserveScroll: true
        });
    }, 500);
}, { deep: true });

const estadoClase = (estado) => ({
    'Inscrito': 'bg-blue-50 text-blue-700',
    'En revisión': 'bg-amber-50 text-amber-700',
    'Aceptado': 'bg-emerald-50 text-emerald-700',
    'Aprobado': 'bg-emerald-50 text-emerald-700',
    'Rechazado': 'bg-red-50 text-red-700',
}[estado] ?? 'bg-gray-100 text-gray-600');

/**
 * Tipo de participación con su etiqueta e icono.
 *
 * Antes se resolvía con `tipo === 'ponencia' ? 'Ponencia' : 'Póster'`, de modo
 * que las propuestas a eventos aparecían todas como "Póster".
 */
const tipoInfo = (inscripcion) => {
    if (inscripcion.tipo_inscripcion === 'propuesta_evento') {
        return {
            etiqueta: 'Proyecto de evento',
            fondo: 'bg-violet-50',
            color: 'text-violet-600',
            icono: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        };
    }

    if (String(inscripcion.tipo).toLowerCase() === 'ponencia') {
        return {
            etiqueta: 'Ponencia',
            fondo: 'bg-indigo-50',
            color: 'text-indigo-600',
            icono: 'M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z',
        };
    }

    return {
        etiqueta: 'Póster',
        fondo: 'bg-sky-50',
        color: 'text-sky-600',
        icono: 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 9h.008v.008H18V9zm.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z M3.75 3.75h16.5a1.5 1.5 0 011.5 1.5v13.5a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V5.25a1.5 1.5 0 011.5-1.5z',
    };
};

/**
 * Resumen por estado; cada tarjeta filtra el listado al pulsarla.
 *
 * Los totales vienen del servidor sin aplicar los filtros activos: si se
 * calcularan sobre la lista ya filtrada, los indicadores cambiarían al filtrar.
 */
const tarjetas = computed(() => {
    const r = props.resumen ?? { total: 0, en_revision: 0, aceptadas: 0, rechazadas: 0 };

    return [
        {
            etiqueta: 'Total', valor: r.total, estado: '',
            fondo: 'bg-indigo-50', color: 'text-indigo-600',
            icono: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        },
        {
            etiqueta: 'En revisión', valor: r.en_revision, estado: 'En revisión',
            fondo: 'bg-amber-50', color: 'text-amber-600',
            icono: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        {
            etiqueta: 'Aceptadas', valor: r.aceptadas, estado: 'Aceptado',
            fondo: 'bg-emerald-50', color: 'text-emerald-600',
            icono: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        {
            etiqueta: 'No aceptadas', valor: r.rechazadas, estado: 'Rechazado',
            fondo: r.rechazadas > 0 ? 'bg-red-50' : 'bg-gray-100',
            color: r.rechazadas > 0 ? 'text-red-600' : 'text-gray-400',
            icono: 'M6 18L18 6M6 6l12 12',
        },
    ];
});

const hayFiltros = computed(() => !!(search.value || selectedEstado.value || selectedTipo.value));

const limpiarFiltros = () => {
    search.value = '';
    selectedEstado.value = '';
    selectedTipo.value = '';
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

const getPropuestaEstadoBadgeClass = (estado) => {
    const classes = {
        'pendiente': 'bg-gradient-to-r from-yellow-400 to-orange-400 text-white shadow-lg',
        'aprobado': 'bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow-lg',
        'rechazado': 'bg-gradient-to-r from-red-400 to-pink-500 text-white shadow-lg',
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

// Modal para ver detalle de propuesta
const showModal = ref(false);
const propuestaSeleccionada = ref(null);

const verDetallePropuesta = (inscripcion) => {
    propuestaSeleccionada.value = inscripcion.propuesta_data;
    showModal.value = true;
};

const cerrarModal = () => {
    showModal.value = false;
    propuestaSeleccionada.value = null;
};
</script>
