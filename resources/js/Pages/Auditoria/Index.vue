<template>
    <AppLayout title="Auditoría" :auth="auth">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <!-- Marca del módulo: registro verificado (trazabilidad) -->
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 shadow-sm shadow-indigo-500/30">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 3.5h6a1 1 0 0 1 1 1V6H8V4.5a1 1 0 0 1 1-1Z" />
                            <path d="M16 5h1.5A1.5 1.5 0 0 1 19 6.5v13A1.5 1.5 0 0 1 17.5 21h-11A1.5 1.5 0 0 1 5 19.5v-13A1.5 1.5 0 0 1 6.5 5H8" />
                            <path d="M8.75 12.4l1.9 1.9 3.85-3.85" />
                            <path d="M9 17.25h6" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">Registros de Auditoría</h2>
                        <p class="text-sm text-gray-500">Trazabilidad de las acciones realizadas en el sistema</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="esAdministrador"
                        @click="abrirLimpieza"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16" />
                        </svg>
                        Depurar histórico
                    </button>
                    <button
                        @click="exportarPDF"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3.5 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF
                    </button>
                    <button
                        @click="exportarExcel"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Excel
                    </button>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <!-- Indicadores -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                <div v-for="tarjeta in tarjetas" :key="tarjeta.etiqueta"
                     class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
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
                </div>
            </div>

            <!-- Aviso de seguridad -->
            <div v-if="stats.accesos_fallidos_semana > 0"
                 class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <p class="text-sm text-amber-800">
                    Se registraron <strong>{{ stats.accesos_fallidos_semana }}</strong> accesos fallidos en los últimos 7 días.
                    <button @click="filtrarPorAccion('login_fallido')" class="font-medium underline underline-offset-2 hover:text-amber-900">
                        Ver intentos
                    </button>
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Distribución por acción -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h3 class="mb-4 text-sm font-semibold text-gray-900">Distribución por tipo de acción</h3>
                    <div v-if="distribucion.length" class="space-y-3">
                        <div v-for="fila in distribucion" :key="fila.accion">
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <button @click="filtrarPorAccion(fila.accion)"
                                        class="font-medium text-gray-700 hover:text-indigo-600 hover:underline">
                                    {{ etiquetaAccion(fila.accion) }}
                                </button>
                                <span class="tabular-nums text-gray-500">{{ fila.total }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full transition-all"
                                     :class="barraAccion(fila.accion)"
                                     :style="{ width: fila.porcentaje + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-6 text-center text-sm text-gray-500">Aún no hay actividad registrada.</p>
                </div>

                <!-- Usuarios más activos -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-900">Usuarios más activos</h3>
                    <ul v-if="stats.usuarios_mas_activos?.length" class="space-y-3">
                        <li v-for="(usuario, i) in stats.usuarios_mas_activos" :key="usuario.usuario_id"
                            class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-semibold text-indigo-700">
                                {{ iniciales(usuario.usuario?.name) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">{{ usuario.usuario?.name || 'Usuario eliminado' }}</p>
                                <p class="text-xs text-gray-500">{{ usuario.total }} {{ usuario.total === 1 ? 'acción' : 'acciones' }}</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-400">#{{ i + 1 }}</span>
                        </li>
                    </ul>
                    <p v-else class="py-6 text-center text-sm text-gray-500">Sin datos todavía.</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <form @submit.prevent="aplicarFiltros">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Usuario</label>
                            <select v-model="filtros.usuario_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos</option>
                                <option v-for="usuario in usuarios" :key="usuario.id" :value="usuario.id">{{ usuario.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Acción</label>
                            <select v-model="filtros.accion" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todas</option>
                                <option v-for="accion in acciones" :key="accion" :value="accion">{{ etiquetaAccion(accion) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Entidad</label>
                            <select v-model="filtros.entidad" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todas</option>
                                <option v-for="entidad in entidades" :key="entidad" :value="entidad">{{ entidad }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Desde</label>
                            <input type="date" v-model="filtros.fecha_inicio" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700">Hasta</label>
                            <input type="date" v-model="filtros.fecha_fin" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <p class="text-xs text-gray-500">
                            <span v-if="hayFiltros">Filtros aplicados sobre {{ auditorias.total }} registros</span>
                            <span v-else>{{ auditorias.total }} registros en total</span>
                        </p>
                        <div class="flex gap-2">
                            <button type="button" @click="limpiarFiltros"
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                                Limpiar
                            </button>
                            <button type="submit"
                                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700">
                                Aplicar filtros
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabla -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div v-if="auditorias.data?.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Fecha</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Usuario</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Acción</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Entidad</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Detalle</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Origen</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="auditoria in auditorias.data" :key="auditoria.id" class="transition-colors hover:bg-gray-50">
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <p class="text-sm text-gray-900">{{ soloFecha(auditoria.created_at) }}</p>
                                    <p class="text-xs tabular-nums text-gray-500">{{ soloHora(auditoria.created_at) }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                                              :class="auditoria.usuario ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-400'">
                                            {{ auditoria.usuario ? iniciales(auditoria.usuario.name) : '—' }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-gray-900">
                                                {{ auditoria.usuario?.name || 'Usuario no identificado' }}
                                            </p>
                                            <p class="truncate text-xs text-gray-500">{{ auditoria.usuario?.email || 'Sin cuenta asociada' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium', colorAccion(auditoria.accion)]">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="puntoAccion(auditoria.accion)"></span>
                                        {{ etiquetaAccion(auditoria.accion) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-900">
                                    {{ auditoria.entidad_afectada }}
                                    <span v-if="auditoria.entidad_id" class="text-gray-400">#{{ auditoria.entidad_id }}</span>
                                </td>
                                <td class="max-w-sm px-5 py-3.5">
                                    <p class="truncate text-sm text-gray-600" :title="auditoria.detalle">
                                        {{ auditoria.detalle || 'Sin detalles' }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <p class="font-mono text-xs text-gray-600">{{ auditoria.ip_address || 'N/D' }}</p>
                                    <p class="truncate text-xs text-gray-400" :title="auditoria.user_agent" style="max-width: 11rem">
                                        {{ navegador(auditoria.user_agent) }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <button @click="verDetalles(auditoria)"
                                            class="rounded-md px-2.5 py-1.5 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-50">
                                        Ver detalle
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-16 text-center">
                    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.5h6a1 1 0 011 1V6H8V4.5a1 1 0 011-1z M16 5h1.5A1.5 1.5 0 0119 6.5v13a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 015 19.5v-13A1.5 1.5 0 016.5 5H8" />
                        </svg>
                    </span>
                    <h3 class="mt-3 text-sm font-medium text-gray-900">
                        {{ hayFiltros ? 'Sin resultados para estos filtros' : 'Todavía no hay actividad registrada' }}
                    </h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                        {{ hayFiltros
                            ? 'Prueba a ampliar el rango de fechas o quitar algún filtro.'
                            : 'El sistema registra automáticamente los inicios de sesión y los cambios sobre semilleros, proyectos, eventos, evaluaciones y certificados.' }}
                    </p>
                    <button v-if="hayFiltros" @click="limpiarFiltros"
                            class="mt-4 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Quitar filtros
                    </button>
                </div>
            </div>

            <div v-if="auditorias.data?.length" class="pb-4">
                <Pagination :links="auditorias.links" :from="auditorias.from" :to="auditorias.to" :total="auditorias.total" />
            </div>
        </div>

        <!-- Modal de detalle -->
        <div v-if="mostrarModal" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4" @click.self="cerrarModal">
            <div class="mx-auto mt-12 w-full max-w-3xl overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Detalle del registro</h3>
                        <p class="text-sm text-gray-500">Registro #{{ auditoriaSeleccionada?.id }}</p>
                    </div>
                    <button @click="cerrarModal" class="rounded-md p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="auditoriaSeleccionada" class="max-h-[70vh] space-y-5 overflow-y-auto px-6 py-5">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Usuario</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ auditoriaSeleccionada.usuario?.name || 'No identificado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Acción</dt>
                            <dd class="mt-0.5">
                                <span :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-medium', colorAccion(auditoriaSeleccionada.accion)]">
                                    {{ etiquetaAccion(auditoriaSeleccionada.accion) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Entidad</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">
                                {{ auditoriaSeleccionada.entidad_afectada }}
                                <span v-if="auditoriaSeleccionada.entidad_id" class="text-gray-400">#{{ auditoriaSeleccionada.entidad_id }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Fecha</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ formatFecha(auditoriaSeleccionada.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Dirección IP</dt>
                            <dd class="mt-0.5 font-mono text-sm text-gray-900">{{ auditoriaSeleccionada.ip_address || 'N/D' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Navegador</dt>
                            <dd class="mt-0.5 text-sm text-gray-900">{{ navegador(auditoriaSeleccionada.user_agent) }}</dd>
                        </div>
                    </dl>

                    <div>
                        <p class="text-xs font-medium text-gray-500">Detalle</p>
                        <p class="mt-1 rounded-lg bg-gray-50 p-3 text-sm text-gray-800">
                            {{ auditoriaSeleccionada.detalle || 'Sin detalles' }}
                        </p>
                    </div>

                    <div v-if="cambios.length">
                        <p class="mb-2 text-xs font-medium text-gray-500">Campos modificados</p>
                        <div class="overflow-hidden rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Campo</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Antes</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Después</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="cambio in cambios" :key="cambio.campo">
                                        <td class="px-3 py-2 font-medium text-gray-700">{{ cambio.campo }}</td>
                                        <td class="px-3 py-2 text-red-700"><span class="break-all">{{ cambio.antes }}</span></td>
                                        <td class="px-3 py-2 text-emerald-700"><span class="break-all">{{ cambio.despues }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de depuración del histórico -->
        <div v-if="mostrarLimpieza" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 p-4" @click.self="mostrarLimpieza = false">
            <div class="mx-auto mt-24 w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="px-6 py-5">
                    <h3 class="text-base font-semibold text-gray-900">Depurar histórico de auditoría</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Se eliminarán de forma permanente los registros anteriores al número de días indicado.
                        La propia depuración queda registrada en la auditoría.
                    </p>
                    <label class="mt-4 block text-xs font-medium text-gray-700">Conservar los últimos (días)</label>
                    <input type="number" v-model.number="diasLimpieza" min="30" max="365"
                           class="mt-1.5 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-500">Mínimo 30 días, máximo 365.</p>
                    <p v-if="errorLimpieza" class="mt-2 text-xs text-red-600">{{ errorLimpieza }}</p>
                </div>
                <div class="flex justify-end gap-2 bg-gray-50 px-6 py-4">
                    <button @click="mostrarLimpieza = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        Cancelar
                    </button>
                    <button @click="confirmarLimpieza" :disabled="limpiando"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">
                        {{ limpiando ? 'Depurando…' : 'Depurar' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Aviso -->
        <div v-if="aviso" class="fixed bottom-4 right-4 z-50">
            <div :class="['rounded-lg px-5 py-3 text-sm text-white shadow-lg', aviso.tipo === 'error' ? 'bg-red-600' : 'bg-emerald-600']">
                {{ aviso.mensaje }}
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    auth: Object,
    auditorias: Object,
    usuarios: Array,
    acciones: Array,
    entidades: Array,
    stats: Object,
    filters: Object,
})

const filtros = ref({
    usuario_id: props.filters?.usuario_id ?? '',
    accion: props.filters?.accion ?? '',
    entidad: props.filters?.entidad ?? '',
    fecha_inicio: props.filters?.fecha_inicio ?? '',
    fecha_fin: props.filters?.fecha_fin ?? '',
})

const mostrarModal = ref(false)
const auditoriaSeleccionada = ref(null)
const mostrarLimpieza = ref(false)
const diasLimpieza = ref(90)
const limpiando = ref(false)
const errorLimpieza = ref('')
const aviso = ref(null)

const esAdministrador = computed(
    () => props.auth?.user?.roles?.some((rol) => rol.name === 'Administrador') ?? false
)

const hayFiltros = computed(() => Object.values(filtros.value).some((valor) => valor !== '' && valor !== null))

/** Traducciones de las acciones registradas por el sistema. */
const ETIQUETAS = {
    create: 'Creación',
    update: 'Modificación',
    delete: 'Eliminación',
    restore: 'Restauración',
    force_delete: 'Eliminación definitiva',
    login: 'Inicio de sesión',
    logout: 'Cierre de sesión',
    login_fallido: 'Intento fallido',
    bloqueo: 'Bloqueo por intentos',
    cleanup: 'Depuración',
    download: 'Descarga',
    export: 'Exportación',
    approve: 'Aprobación',
    reject: 'Rechazo',
}
const etiquetaAccion = (accion) => ETIQUETAS[accion] ?? accion

const COLORES = {
    create: 'bg-emerald-50 text-emerald-700',
    update: 'bg-blue-50 text-blue-700',
    delete: 'bg-red-50 text-red-700',
    force_delete: 'bg-red-50 text-red-700',
    login: 'bg-indigo-50 text-indigo-700',
    logout: 'bg-gray-100 text-gray-600',
    login_fallido: 'bg-amber-50 text-amber-700',
    bloqueo: 'bg-amber-50 text-amber-800',
    cleanup: 'bg-violet-50 text-violet-700',
}
const colorAccion = (accion) => COLORES[accion] ?? 'bg-gray-100 text-gray-600'

const PUNTOS = {
    create: 'bg-emerald-500',
    update: 'bg-blue-500',
    delete: 'bg-red-500',
    force_delete: 'bg-red-500',
    login: 'bg-indigo-500',
    logout: 'bg-gray-400',
    login_fallido: 'bg-amber-500',
    bloqueo: 'bg-amber-600',
    cleanup: 'bg-violet-500',
}
const puntoAccion = (accion) => PUNTOS[accion] ?? 'bg-gray-400'
const barraAccion = (accion) => PUNTOS[accion] ?? 'bg-gray-400'

const tarjetas = computed(() => [
    {
        etiqueta: 'Total de registros',
        valor: props.stats?.total_registros ?? 0,
        fondo: 'bg-indigo-50',
        color: 'text-indigo-600',
        icono: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        etiqueta: 'Hoy',
        valor: props.stats?.actividades_hoy ?? 0,
        fondo: 'bg-emerald-50',
        color: 'text-emerald-600',
        icono: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        etiqueta: 'Últimos 7 días',
        valor: props.stats?.actividades_semana ?? 0,
        fondo: 'bg-sky-50',
        color: 'text-sky-600',
        icono: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        etiqueta: 'Usuarios con actividad',
        valor: props.stats?.usuarios_activos ?? 0,
        fondo: 'bg-violet-50',
        color: 'text-violet-600',
        icono: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
    },
    {
        etiqueta: 'Accesos fallidos (7 días)',
        valor: props.stats?.accesos_fallidos_semana ?? 0,
        fondo: (props.stats?.accesos_fallidos_semana ?? 0) > 0 ? 'bg-amber-50' : 'bg-gray-100',
        color: (props.stats?.accesos_fallidos_semana ?? 0) > 0 ? 'text-amber-600' : 'text-gray-400',
        icono: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    },
])

const distribucion = computed(() => {
    const datos = props.stats?.por_accion ?? {}
    const entradas = Object.entries(datos)
    const maximo = Math.max(...entradas.map(([, total]) => total), 1)
    return entradas.map(([accion, total]) => ({
        accion,
        total,
        porcentaje: Math.round((total / maximo) * 100),
    }))
})

/** Campos que cambiaron, comparando los datos anteriores y posteriores. */
const cambios = computed(() => {
    const antes = auditoriaSeleccionada.value?.datos_antes
    const despues = auditoriaSeleccionada.value?.datos_despues
    if (!antes && !despues) return []

    const OCULTOS = ['password', 'remember_token', 'updated_at', 'created_at']
    const campos = new Set([...Object.keys(antes ?? {}), ...Object.keys(despues ?? {})])

    return [...campos]
        .filter((campo) => !OCULTOS.includes(campo))
        .map((campo) => ({
            campo: campo.replace(/_/g, ' '),
            antes: formatValor(antes?.[campo]),
            despues: formatValor(despues?.[campo]),
        }))
})

const formatValor = (valor) => {
    if (valor === null || valor === undefined || valor === '') return '—'
    if (typeof valor === 'object') return JSON.stringify(valor)
    return String(valor).length > 120 ? String(valor).slice(0, 120) + '…' : String(valor)
}

const iniciales = (nombre) => {
    if (!nombre) return '—'
    return nombre.trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('')
}

/** Nombre legible del navegador a partir del user agent. */
const navegador = (userAgent) => {
    if (!userAgent) return 'Origen desconocido'
    if (/Edg\//.test(userAgent)) return 'Microsoft Edge'
    if (/OPR\/|Opera/.test(userAgent)) return 'Opera'
    if (/Chrome\//.test(userAgent)) return 'Google Chrome'
    if (/Firefox\//.test(userAgent)) return 'Mozilla Firefox'
    if (/Safari\//.test(userAgent)) return 'Safari'
    return 'Otro cliente'
}

const soloFecha = (fecha) =>
    new Date(fecha).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })

const soloHora = (fecha) =>
    new Date(fecha).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' })

const formatFecha = (fecha) => `${soloFecha(fecha)}, ${soloHora(fecha)}`

const mostrarAviso = (mensaje, tipo = 'success') => {
    aviso.value = { mensaje, tipo }
    setTimeout(() => (aviso.value = null), 3500)
}

const verDetalles = async (auditoria) => {
    try {
        const { data } = await axios.get(`/api/auditoria/${auditoria.id}`)
        auditoriaSeleccionada.value = data
        mostrarModal.value = true
    } catch (error) {
        mostrarAviso('No se pudo cargar el detalle del registro', 'error')
    }
}

const cerrarModal = () => {
    mostrarModal.value = false
    auditoriaSeleccionada.value = null
}

const aplicarFiltros = () => {
    router.get('/auditoria', filtros.value, { preserveState: true, preserveScroll: true })
}

const filtrarPorAccion = (accion) => {
    filtros.value.accion = accion
    aplicarFiltros()
}

const limpiarFiltros = () => {
    filtros.value = { usuario_id: '', accion: '', entidad: '', fecha_inicio: '', fecha_fin: '' }
    router.get('/auditoria')
}

const abrirLimpieza = () => {
    errorLimpieza.value = ''
    mostrarLimpieza.value = true
}

const confirmarLimpieza = async () => {
    errorLimpieza.value = ''
    if (diasLimpieza.value < 30 || diasLimpieza.value > 365) {
        errorLimpieza.value = 'Indica un valor entre 30 y 365 días.'
        return
    }

    limpiando.value = true
    try {
        const { data } = await axios.delete('/auditoria/limpiar', { data: { dias: diasLimpieza.value } })
        mostrarLimpieza.value = false
        mostrarAviso(data.mensaje)
        router.reload({ only: ['auditorias', 'stats', 'acciones', 'entidades'] })
    } catch (error) {
        errorLimpieza.value = error.response?.data?.message || 'No se pudo depurar el histórico.'
    } finally {
        limpiando.value = false
    }
}

const alPulsarTecla = (evento) => {
    if (evento.key !== 'Escape') return
    if (mostrarModal.value) cerrarModal()
    if (mostrarLimpieza.value) mostrarLimpieza.value = false
}

onMounted(() => window.addEventListener('keydown', alPulsarTecla))
onUnmounted(() => window.removeEventListener('keydown', alPulsarTecla))

const exportarPDF = () => {
    window.open(`/auditoria/exportar-pdf?${new URLSearchParams(filtros.value)}`, '_blank')
}

const exportarExcel = () => {
    window.open(`/auditoria/exportar-excel?${new URLSearchParams(filtros.value)}`, '_blank')
}
</script>
