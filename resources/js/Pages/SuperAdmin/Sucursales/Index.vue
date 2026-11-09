<template>
    <SuperAdminLayout>
        <div class="space-y-8">
            <!-- Encabezado y Acciones de Sucursales -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-stone-900">Gestión de Sucursales, Cajas, Cocina y Bases</h1>
                    <p class="text-xs text-stone-500">
                        Administra tus sedes físicas, edita o activa/desactiva sucursales, y gestiona cajas, monitores de cocina, usuarios y bases diarias de efectivo por sede activa (RF-CFG-01).
                    </p>
                </div>
                <button
                    type="button"
                    @click="abrirModalCrearSucursal"
                    class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
                >
                    ➕ Registrar Nueva Sucursal
                </button>
            </div>

            <!-- Tabla de Sucursales Existentes (Editar, Activar/Desactivar, Modalidades, Seleccionar Sede Activa) -->
            <div class="bg-white rounded-3xl border border-stone-200 overflow-hidden shadow-xs">
                <div class="px-6 py-4 border-b border-stone-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-stone-50/60">
                    <div>
                        <h2 class="text-sm font-black text-stone-900">Directorio de Sedes del Restaurante</h2>
                        <p class="text-[11px] text-stone-500">Edita la información de cualquier sede, habilítala o deshabilítala, o selecciónala para configurar sus cajas y cocina.</p>
                    </div>
                    <form @submit.prevent="filtrarSucursales" class="flex items-center gap-2">
                        <input
                            v-model="terminoBuscar"
                            type="text"
                            placeholder="Buscar sede por nombre o dirección..."
                            class="rounded-xl border border-stone-300 px-3 py-1.5 text-xs bg-white"
                        />
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-stone-900 text-white text-xs font-bold">
                            Buscar
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-xs">
                        <thead class="bg-stone-50 text-stone-600 uppercase font-bold">
                            <tr>
                                <th class="px-5 py-3">Sede</th>
                                <th class="px-5 py-3">Dirección y Teléfono</th>
                                <th class="px-5 py-3">Zona / Moneda</th>
                                <th class="px-5 py-3">Modalidades Pedido</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3 text-right">Gestión de Sucursal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200">
                            <tr
                                v-for="sucursal in sucursales.data"
                                :key="sucursal.id"
                                :class="[
                                    'hover:bg-stone-50 transition',
                                    sucursalActiva?.id === sucursal.id ? 'bg-amber-50/40' : ''
                                ]"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-stone-900 text-sm">{{ sucursal.nombre }}</span>
                                        <span
                                            v-if="sucursalActiva?.id === sucursal.id"
                                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-stone-950"
                                        >
                                            Sede Activa
                                        </span>
                                    </div>
                                    <span class="text-[11px] font-mono text-stone-400">/{{ sucursal.slug }}</span>
                                </td>
                                <td class="px-5 py-4 text-stone-600">
                                    <div class="font-medium text-stone-800">{{ sucursal.direccion }}</div>
                                    <div class="font-mono text-[11px] text-stone-500">{{ sucursal.telefono }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-mono text-stone-600 block">{{ sucursal.zona_horaria }}</span>
                                    <span class="font-bold text-stone-800">{{ sucursal.moneda }} ({{ sucursal.idioma?.toUpperCase() || 'ES' }})</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <button
                                            type="button"
                                            @click="alternarModalidad(sucursal.id, 'domicilio')"
                                            :class="sucursal.acepta_domicilios !== false ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-stone-100 text-stone-400 border-stone-200 line-through'"
                                            class="px-2 py-0.5 rounded-md border text-[11px] font-semibold transition hover:scale-105 cursor-pointer"
                                            title="Alternar Domicilios"
                                        >
                                            🛵 Domicilio
                                        </button>
                                        <button
                                            type="button"
                                            @click="alternarModalidad(sucursal.id, 'para_llevar')"
                                            :class="sucursal.acepta_para_llevar !== false ? 'bg-blue-100 text-blue-800 border-blue-300' : 'bg-stone-100 text-stone-400 border-stone-200 line-through'"
                                            class="px-2 py-0.5 rounded-md border text-[11px] font-semibold transition hover:scale-105 cursor-pointer"
                                            title="Alternar Para Llevar"
                                        >
                                            🥡 Llevar
                                        </button>
                                        <button
                                            type="button"
                                            @click="alternarModalidad(sucursal.id, 'en_sitio')"
                                            :class="sucursal.acepta_en_sitio !== false ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-stone-100 text-stone-400 border-stone-200 line-through'"
                                            class="px-2 py-0.5 rounded-md border text-[11px] font-semibold transition hover:scale-105 cursor-pointer"
                                            title="Alternar En Sitio"
                                        >
                                            🍽️ En Sitio
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <button
                                        type="button"
                                        @click="alternarEstadoSucursal(sucursal)"
                                        :class="sucursal.activa ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-rose-100 text-rose-800 border-rose-300'"
                                        class="px-2.5 py-1 rounded-full font-bold text-[11px] border transition hover:scale-105 cursor-pointer"
                                        title="Clic para activar o desactivar esta sucursal"
                                    >
                                        {{ sucursal.activa ? '● Activa' : '○ Desactivada' }}
                                    </button>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <button
                                            type="button"
                                            @click="seleccionarSedeParaGestion(sucursal.id)"
                                            :class="sucursalActiva?.id === sucursal.id ? 'bg-amber-600 text-white' : 'bg-stone-100 hover:bg-stone-200 text-stone-700'"
                                            class="px-2.5 py-1.5 rounded-lg font-bold text-xs transition cursor-pointer"
                                        >
                                            ⚙️ Cajas / Cocina
                                        </button>
                                        <button
                                            type="button"
                                            @click="abrirModalEditarSucursal(sucursal)"
                                            class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs border border-blue-200 transition cursor-pointer"
                                        >
                                            ✏️ Editar
                                        </button>
                                        <button
                                            type="button"
                                            @click="alternarEstadoSucursal(sucursal)"
                                            :class="sucursal.activa ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200'"
                                            class="px-2.5 py-1.5 rounded-lg font-bold text-xs border transition cursor-pointer"
                                        >
                                            {{ sucursal.activa ? 'Desactivar' : 'Activar' }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="eliminar(sucursal.id)"
                                            class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200 transition cursor-pointer"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Panel Operativo de la Sede Activa: Cajas, Cocina, Usuarios y Bases de Efectivo -->
            <div v-if="sucursalActiva" class="bg-white rounded-3xl border border-stone-200 shadow-xs overflow-hidden">
                <!-- Banner de Sede Activa -->
                <div class="bg-stone-900 text-white px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🏢</span>
                            <h2 class="text-base font-black">
                                Configuración Operativa de Sede Activa:
                                <span class="text-amber-400">{{ sucursalActiva.nombre }}</span>
                            </h2>
                        </div>
                        <p class="text-xs text-stone-400 mt-0.5">
                            Todos los registros de cajas, monitores de cocina, usuarios y bases de efectivo que crees aquí quedan enlazados a <strong class="text-white">{{ sucursalActiva.nombre }}</strong>.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-stone-300">Cambiar Sede Activa:</label>
                        <select
                            :value="sucursalActiva.id"
                            @change="seleccionarSedeParaGestion(Number($event.target.value))"
                            class="rounded-xl bg-stone-800 border border-stone-700 text-amber-300 px-3 py-1.5 text-xs font-bold"
                        >
                            <option v-for="s in todasSucursales" :key="s.id" :value="s.id">
                                {{ s.nombre }} {{ s.activa ? '' : '(Inactiva)' }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Navegación de Pestañas de la Sede -->
                <div class="px-6 pt-4 border-b border-stone-200 bg-stone-50 flex flex-wrap gap-2 text-xs font-bold">
                    <button
                        type="button"
                        @click="pestanaSede = 'bases'"
                        :class="pestanaSede === 'bases' ? 'bg-white text-amber-700 border-stone-200 border-b-white -mb-px' : 'text-stone-600 hover:text-stone-900 border-transparent'"
                        class="px-4 py-2.5 rounded-t-xl border transition cursor-pointer"
                    >
                        💵 Base en Efectivo por Caja y Día ({{ asignacionesBase.length }})
                    </button>
                    <button
                        type="button"
                        @click="pestanaSede = 'cajas'"
                        :class="pestanaSede === 'cajas' ? 'bg-white text-amber-700 border-stone-200 border-b-white -mb-px' : 'text-stone-600 hover:text-stone-900 border-transparent'"
                        class="px-4 py-2.5 rounded-t-xl border transition cursor-pointer"
                    >
                        🖥️ Cajas y Usuarios Cajeros ({{ cajasSede.length }} / {{ usuariosCajeros.length }})
                    </button>
                    <button
                        type="button"
                        @click="pestanaSede = 'cocina'"
                        :class="pestanaSede === 'cocina' ? 'bg-white text-amber-700 border-stone-200 border-b-white -mb-px' : 'text-stone-600 hover:text-stone-900 border-transparent'"
                        class="px-4 py-2.5 rounded-t-xl border transition cursor-pointer"
                    >
                        👨‍🍳 Monitores y Usuarios de Cocina ({{ estacionesCocina.length }} / {{ usuariosCocina.length }})
                    </button>
                    <button
                        type="button"
                        @click="pestanaSede = 'bitacora'"
                        :class="pestanaSede === 'bitacora' ? 'bg-white text-amber-700 border-stone-200 border-b-white -mb-px' : 'text-stone-600 hover:text-stone-900 border-transparent'"
                        class="px-4 py-2.5 rounded-t-xl border transition cursor-pointer"
                    >
                        📜 Bitácora Rápida de Sede ({{ bitacoraSede.length }})
                    </button>
                </div>

                <!-- PESTAÑA 1: ASIGNACIÓN DIARIA DE BASE EN EFECTIVO POR CAJA -->
                <div v-if="pestanaSede === 'bases'" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Formulario para asignar base -->
                        <div class="bg-stone-50 rounded-2xl border border-stone-200 p-5 space-y-4">
                            <div>
                                <h3 class="text-sm font-black text-stone-900">Asignar Base en Efectivo</h3>
                                <p class="text-[11px] text-stone-500">
                                    Selecciona el día, la caja disponible (con su cajero asignado) y cuánto efectivo se le entrega para iniciar operaciones.
                                </p>
                            </div>

                            <form @submit.prevent="guardarBaseCaja" class="space-y-3 text-xs">
                                <div>
                                    <label class="block font-bold text-stone-700 mb-1">Día de Asignación *</label>
                                    <input
                                        type="date"
                                        v-model="formBase.fecha"
                                        class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 font-mono font-bold"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block font-bold text-stone-700 mb-1">Caja Disponible en {{ sucursalActiva.nombre }} *</label>
                                    <select
                                        v-model="formBase.caja_id"
                                        class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2"
                                        required
                                    >
                                        <option :value="null" disabled>Selecciona una caja...</option>
                                        <option v-for="c in cajasSede" :key="c.id" :value="c.id">
                                            {{ c.nombre }} — {{ c.cajero ? `Cajero: ${c.cajero.nombre}` : 'Sin cajero asignado' }} {{ c.activa ? '' : '(Inactiva)' }}
                                        </option>
                                    </select>
                                    <p v-if="cajasSede.length === 0" class="text-[11px] text-amber-700 mt-1">
                                        ⚠️ Aún no hay cajas en esta sede. Créalas en la pestaña "Cajas y Usuarios Cajeros".
                                    </p>
                                </div>

                                <div>
                                    <label class="block font-bold text-stone-700 mb-1">Monto Base Asignado ($) *</label>
                                    <input
                                        type="number"
                                        step="1000"
                                        min="0"
                                        v-model.number="formBase.monto"
                                        class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 font-mono font-bold text-sm"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block font-bold text-stone-700 mb-1">Observaciones (Opcional)</label>
                                    <input
                                        type="text"
                                        v-model="formBase.observaciones"
                                        placeholder="Ej: Sencillo en monedas y billetes de $2.000..."
                                        class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    :disabled="formBase.processing || !formBase.caja_id"
                                    class="w-full py-2.5 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-bold rounded-xl shadow-xs transition cursor-pointer"
                                >
                                    {{ formBase.processing ? 'Guardando...' : '💾 Guardar Asignación de Base' }}
                                </button>
                            </form>
                        </div>

                        <!-- Tabla de registros diarios de base de caja -->
                        <div class="lg:col-span-2 bg-white rounded-2xl border border-stone-200 overflow-hidden">
                            <div class="px-5 py-3.5 bg-stone-50 border-b border-stone-200 flex items-center justify-between">
                                <h3 class="text-xs font-black uppercase tracking-wider text-stone-700">
                                    Historial de Bases Asignadas y Ediciones ({{ sucursalActiva.nombre }})
                                </h3>
                                <span class="text-[11px] text-stone-500">Se guarda quién asignó y quién editó cada monto</span>
                            </div>

                            <div class="overflow-x-auto max-h-96">
                                <table v-if="asignacionesBase.length > 0" class="min-w-full divide-y divide-stone-100 text-xs text-left">
                                    <thead class="bg-stone-50 text-stone-500 font-bold uppercase">
                                        <tr>
                                            <th class="px-4 py-2.5">Fecha</th>
                                            <th class="px-4 py-2.5">Caja / Cajero</th>
                                            <th class="px-4 py-2.5 text-right">Monto Base</th>
                                            <th class="px-4 py-2.5">Asignado / Editado Por</th>
                                            <th class="px-4 py-2.5 text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-stone-100">
                                        <tr v-for="asig in asignacionesBase" :key="asig.id" class="hover:bg-stone-50">
                                            <td class="px-4 py-3 font-mono font-bold text-stone-800">
                                                {{ asig.fecha_formateada }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-stone-900">{{ asig.caja_nombre }}</div>
                                                <div class="text-[11px] text-stone-500">👤 {{ asig.cajero_nombre }}</div>
                                                <div v-if="asig.observaciones" class="text-[10px] text-stone-400 italic">{{ asig.observaciones }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-black text-emerald-700 text-sm">
                                                ${{ Number(asig.monto).toLocaleString('es-CO') }}
                                            </td>
                                            <td class="px-4 py-3 text-[11px]">
                                                <div>Asignó: <strong class="text-stone-800">{{ asig.asignado_por }}</strong></div>
                                                <div v-if="asig.editado_por" class="text-amber-700 font-semibold">
                                                    ✏️ Editado por: {{ asig.editado_por }} ({{ asig.updated_at }})
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    @click="abrirModalEditarBase(asig)"
                                                    class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold border border-amber-200 transition cursor-pointer"
                                                >
                                                    ✏️ Editar Monto
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div v-else class="p-8 text-center text-stone-400 text-xs">
                                    No hay registros de base en efectivo aún para esta sede.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA 2: GESTIÓN DE CAJAS Y USUARIOS CAJEROS -->
                <div v-if="pestanaSede === 'cajas'" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Sección Cajas -->
                        <div class="border border-stone-200 rounded-2xl p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black text-stone-900">🖥️ Cajas de {{ sucursalActiva.nombre }}</h3>
                                    <p class="text-[11px] text-stone-500">Crea cajas, habilítalas/deshabilítalas y asígnales un usuario cajero.</p>
                                </div>
                            </div>

                            <form @submit.prevent="guardarCaja" class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs bg-stone-50 p-3 rounded-xl border border-stone-200">
                                <input
                                    v-model="formCaja.nombre"
                                    type="text"
                                    placeholder="Nombre (ej: Caja Principal 1)"
                                    required
                                    class="rounded-lg border border-stone-300 bg-white px-3 py-2"
                                />
                                <select v-model="formCaja.user_id" class="rounded-lg border border-stone-300 bg-white px-2.5 py-2">
                                    <option :value="null">-- Sin cajero fijo --</option>
                                    <option v-for="u in usuariosCajeros" :key="u.id" :value="u.id">
                                        👤 {{ u.nombre }}
                                    </option>
                                </select>
                                <button type="submit" :disabled="formCaja.processing" class="bg-stone-900 hover:bg-stone-800 text-white font-bold rounded-lg px-3 py-2">
                                    + Crear Caja
                                </button>
                            </form>

                            <div class="divide-y divide-stone-100 text-xs">
                                <div v-for="c in cajasSede" :key="c.id" class="py-3 flex items-center justify-between gap-3">
                                    <div>
                                        <span class="font-bold text-stone-900">{{ c.nombre }}</span>
                                        <span
                                            :class="c.activa ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                            class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        >
                                            {{ c.activa ? 'Habilitada' : 'Deshabilitada' }}
                                        </span>
                                        <div class="text-[11px] text-stone-500 mt-0.5">
                                            Usuario asignado: <strong>{{ c.cajero ? `${c.cajero.nombre} (${c.cajero.email})` : 'Ninguno' }}</strong>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <select
                                            :value="c.user_id || ''"
                                            @change="actualizarCaja(c, { user_id: $event.target.value ? Number($event.target.value) : null })"
                                            class="rounded-lg border border-stone-300 px-2 py-1 text-[11px]"
                                            title="Asignar cajero"
                                        >
                                            <option value="">Sin asignar</option>
                                            <option v-for="u in usuariosCajeros" :key="u.id" :value="u.id">
                                                {{ u.nombre }}
                                            </option>
                                        </select>
                                        <button
                                            type="button"
                                            @click="actualizarCaja(c, { activa: !c.activa })"
                                            :class="c.activa ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                                            class="px-2.5 py-1 rounded-lg border font-bold text-[11px]"
                                        >
                                            {{ c.activa ? 'Deshabilitar' : 'Habilitar' }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="cajasSede.length === 0" class="py-6 text-center text-stone-400">
                                    No hay cajas registradas en esta sede.
                                </div>
                            </div>
                        </div>

                        <!-- Sección Usuarios Cajeros -->
                        <div class="border border-stone-200 rounded-2xl p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black text-stone-900">👤 Usuarios Cajeros ({{ sucursalActiva.nombre }})</h3>
                                    <p class="text-[11px] text-stone-500">Crea credenciales de acceso a <code>/cajero</code> enlazadas a esta sede.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="abrirModalUsuario('cajero')"
                                    class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold"
                                >
                                    + Nuevo Cajero
                                </button>
                            </div>

                            <div class="divide-y divide-stone-100 text-xs">
                                <div v-for="u in usuariosCajeros" :key="u.id" class="py-3 flex items-center justify-between gap-3">
                                    <div>
                                        <span class="font-bold text-stone-900">{{ u.nombre }}</span>
                                        <span
                                            :class="u.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                            class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        >
                                            {{ u.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                        <div class="text-[11px] text-stone-500 font-mono">{{ u.email }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="abrirModalEditarUsuario(u, 'cajero')"
                                            class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 font-bold text-[11px]"
                                        >
                                            ✏️ Editar / Clave
                                        </button>
                                    </div>
                                </div>
                                <div v-if="usuariosCajeros.length === 0" class="py-6 text-center text-stone-400">
                                    No hay usuarios cajeros enlazados a esta sede.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA 3: GESTIÓN DE MONITORES Y USUARIOS DE COCINA -->
                <div v-if="pestanaSede === 'cocina'" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Sección Monitores / Vistas de Cocina -->
                        <div class="border border-stone-200 rounded-2xl p-5 space-y-4">
                            <div>
                                <h3 class="text-sm font-black text-stone-900">👨‍🍳 Vistas / Monitores de Cocina ({{ sucursalActiva.nombre }})</h3>
                                <p class="text-[11px] text-stone-500">Configura uno o varios monitores KDS para mostrar pedidos en cocina y asígnales su usuario.</p>
                            </div>

                            <form @submit.prevent="guardarEstacionCocina" class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs bg-stone-50 p-3 rounded-xl border border-stone-200">
                                <input
                                    v-model="formCocina.nombre"
                                    type="text"
                                    placeholder="Ej: Monitor Cocina Principal"
                                    required
                                    class="rounded-lg border border-stone-300 bg-white px-3 py-2"
                                />
                                <select v-model="formCocina.user_id" class="rounded-lg border border-stone-300 bg-white px-2.5 py-2">
                                    <option :value="null">-- Sin usuario fijo --</option>
                                    <option v-for="u in usuariosCocina" :key="u.id" :value="u.id">
                                        👨‍🍳 {{ u.nombre }}
                                    </option>
                                </select>
                                <button type="submit" :disabled="formCocina.processing" class="bg-stone-900 hover:bg-stone-800 text-white font-bold rounded-lg px-3 py-2">
                                    + Crear Monitor
                                </button>
                            </form>

                            <div class="divide-y divide-stone-100 text-xs">
                                <div v-for="est in estacionesCocina" :key="est.id" class="py-3 flex items-center justify-between gap-3">
                                    <div>
                                        <span class="font-bold text-stone-900">{{ est.nombre }}</span>
                                        <span
                                            :class="est.activa ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                            class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        >
                                            {{ est.activa ? 'Habilitado' : 'Deshabilitado' }}
                                        </span>
                                        <div class="text-[11px] text-stone-500 mt-0.5">
                                            Usuario asignado: <strong>{{ est.cocinero ? `${est.cocinero.nombre} (${est.cocinero.email})` : 'Ninguno' }}</strong>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <select
                                            :value="est.user_id || ''"
                                            @change="actualizarEstacionCocina(est, { user_id: $event.target.value ? Number($event.target.value) : null })"
                                            class="rounded-lg border border-stone-300 px-2 py-1 text-[11px]"
                                        >
                                            <option value="">Sin asignar</option>
                                            <option v-for="u in usuariosCocina" :key="u.id" :value="u.id">
                                                {{ u.nombre }}
                                            </option>
                                        </select>
                                        <button
                                            type="button"
                                            @click="actualizarEstacionCocina(est, { activa: !est.activa })"
                                            :class="est.activa ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                                            class="px-2.5 py-1 rounded-lg border font-bold text-[11px]"
                                        >
                                            {{ est.activa ? 'Deshabilitar' : 'Habilitar' }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="estacionesCocina.length === 0" class="py-6 text-center text-stone-400">
                                    No hay monitores de cocina registrados en esta sede.
                                </div>
                            </div>
                        </div>

                        <!-- Sección Usuarios de Cocina -->
                        <div class="border border-stone-200 rounded-2xl p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black text-stone-900">👨‍🍳 Usuarios de Cocina ({{ sucursalActiva.nombre }})</h3>
                                    <p class="text-[11px] text-stone-500">Crea credenciales de acceso a <code>/cocina</code> enlazadas a esta sede.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="abrirModalUsuario('cocinero')"
                                    class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold"
                                >
                                    + Nuevo Usuario Cocina
                                </button>
                            </div>

                            <div class="divide-y divide-stone-100 text-xs">
                                <div v-for="u in usuariosCocina" :key="u.id" class="py-3 flex items-center justify-between gap-3">
                                    <div>
                                        <span class="font-bold text-stone-900">{{ u.nombre }}</span>
                                        <span
                                            :class="u.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                            class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        >
                                            {{ u.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                        <div class="text-[11px] text-stone-500 font-mono">{{ u.email }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="abrirModalEditarUsuario(u, 'cocinero')"
                                            class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 font-bold text-[11px]"
                                        >
                                            ✏️ Editar / Clave
                                        </button>
                                    </div>
                                </div>
                                <div v-if="usuariosCocina.length === 0" class="py-6 text-center text-stone-400">
                                    No hay usuarios de cocina enlazados a esta sede.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA 4: BITÁCORA RÁPIDA DE MOVIMIENTOS -->
                <div v-if="pestanaSede === 'bitacora'" class="p-6">
                    <div class="overflow-x-auto">
                        <table v-if="bitacoraSede.length > 0" class="min-w-full divide-y divide-stone-100 text-xs text-left">
                            <thead class="bg-stone-50 text-stone-500 font-bold uppercase">
                                <tr>
                                    <th class="px-4 py-2.5">Fecha y Hora</th>
                                    <th class="px-4 py-2.5">Módulo</th>
                                    <th class="px-4 py-2.5">Detalle del Movimiento</th>
                                    <th class="px-4 py-2.5">Realizado Por</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                <tr v-for="b in bitacoraSede" :key="b.id" class="hover:bg-stone-50">
                                    <td class="px-4 py-2.5 font-mono text-stone-500">{{ b.fecha }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="px-2 py-0.5 rounded-md bg-stone-100 text-stone-700 font-bold uppercase text-[10px]">
                                            {{ b.modulo }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 font-medium text-stone-800">{{ b.descripcion }}</td>
                                    <td class="px-4 py-2.5 text-stone-600 font-semibold">{{ b.usuario }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="p-8 text-center text-stone-400 text-xs">
                            No hay movimientos registrados recientemente en la bitácora de esta sede.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Crear / Editar Sucursal -->
            <div v-if="mostrarModal" class="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-stone-200">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-base font-black text-stone-900">
                            {{ editandoSucursalId ? 'Editar Información de Sucursal' : 'Registrar Nueva Sucursal' }}
                        </h3>
                        <button type="button" @click="mostrarModal = false" class="text-stone-400 hover:text-stone-700">✕</button>
                    </div>
                    <form @submit.prevent="guardarSucursal" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-stone-700">Nombre de la Sede *</label>
                            <input v-model="form.nombre" required class="w-full mt-1 border border-stone-300 rounded-xl px-3 py-2" placeholder="Sede Poblado" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700">Dirección *</label>
                            <input v-model="form.direccion" required class="w-full mt-1 border border-stone-300 rounded-xl px-3 py-2" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700">Teléfono *</label>
                            <input v-model="form.telefono" required class="w-full mt-1 border border-stone-300 rounded-xl px-3 py-2" placeholder="+573001234567" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-stone-700">Zona Horaria *</label>
                                <input v-model="form.zona_horaria" required class="w-full mt-1 border border-stone-300 rounded-xl px-3 py-2" />
                            </div>
                            <div>
                                <label class="block font-bold text-stone-700">Moneda *</label>
                                <input v-model="form.moneda" required class="w-full mt-1 border border-stone-300 rounded-xl px-3 py-2" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Modalidades de Pedido Habilitadas</label>
                            <div class="flex items-center gap-4 text-xs text-stone-700">
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" v-model="form.acepta_domicilios" class="rounded text-amber-600 focus:ring-amber-500" />
                                    🛵 Domicilios
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" v-model="form.acepta_para_llevar" class="rounded text-blue-600 focus:ring-blue-500" />
                                    🥡 Para Llevar
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" v-model="form.acepta_en_sitio" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                    🍽️ En Sitio
                                </label>
                            </div>
                        </div>

                        <div v-if="editandoSucursalId" class="pt-1">
                            <label class="inline-flex items-center gap-2 font-bold text-stone-800 cursor-pointer">
                                <input type="checkbox" v-model="form.activa" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span>Sucursal Activa y Operativa</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-2 pt-3 border-t">
                            <button type="button" @click="mostrarModal = false" class="px-4 py-2 border rounded-xl text-xs font-semibold">Cancelar</button>
                            <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold">
                                {{ editandoSucursalId ? 'Actualizar Sucursal' : 'Guardar Sucursal' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Crear / Editar Usuario Operativo (Cajero o Cocinero) -->
            <div v-if="modalUsuarioAbierto" class="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-stone-200">
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <h3 class="text-base font-black text-stone-900">
                                {{ editandoUsuarioId ? 'Editar Usuario Operativo' : `Nuevo Usuario (${formUsuario.rol === 'cajero' ? 'Caja' : 'Cocina'})` }}
                            </h3>
                            <p class="text-[11px] text-stone-500">Enlazado a la sede activa: <strong>{{ sucursalActiva?.nombre }}</strong></p>
                        </div>
                        <button type="button" @click="modalUsuarioAbierto = false" class="text-stone-400 hover:text-stone-700">✕</button>
                    </div>

                    <form @submit.prevent="guardarUsuarioOperativo" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Nombre Completo *</label>
                            <input v-model="formUsuario.nombre" type="text" required class="w-full rounded-xl border border-stone-300 px-3 py-2" placeholder="Ej: Laura Gómez" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Correo Electrónico (Usuario de acceso) *</label>
                            <input v-model="formUsuario.email" type="email" required class="w-full rounded-xl border border-stone-300 px-3 py-2 font-mono" placeholder="cajero.sede@sigr.test" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Teléfono (Opcional)</label>
                            <input v-model="formUsuario.telefono" type="text" class="w-full rounded-xl border border-stone-300 px-3 py-2" placeholder="+573001234567" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">
                                Contraseña {{ editandoUsuarioId ? '(Dejar en blanco para conservar la actual)' : '*' }}
                            </label>
                            <input
                                v-model="formUsuario.password"
                                type="password"
                                :required="!editandoUsuarioId"
                                minlength="6"
                                class="w-full rounded-xl border border-stone-300 px-3 py-2"
                                placeholder="Mínimo 6 caracteres"
                            />
                        </div>

                        <div v-if="!editandoUsuarioId && formUsuario.rol === 'cajero' && cajasSede.length > 0">
                            <label class="block font-bold text-stone-700 mb-1">Asignar directamente a una Caja (Opcional)</label>
                            <select v-model="formUsuario.caja_id" class="w-full rounded-xl border border-stone-300 px-3 py-2">
                                <option :value="null">-- No asignar aún --</option>
                                <option v-for="c in cajasSede" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                            </select>
                        </div>

                        <div v-if="!editandoUsuarioId && formUsuario.rol === 'cocinero' && estacionesCocina.length > 0">
                            <label class="block font-bold text-stone-700 mb-1">Asignar directamente a un Monitor de Cocina (Opcional)</label>
                            <select v-model="formUsuario.estacion_cocina_id" class="w-full rounded-xl border border-stone-300 px-3 py-2">
                                <option :value="null">-- No asignar aún --</option>
                                <option v-for="e in estacionesCocina" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                            </select>
                        </div>

                        <div v-if="editandoUsuarioId" class="pt-1">
                            <label class="inline-flex items-center gap-2 font-bold text-stone-800 cursor-pointer">
                                <input type="checkbox" v-model="formUsuario.activo" class="rounded text-emerald-600" />
                                <span>Usuario habilitado para iniciar sesión</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t">
                            <button type="button" @click="modalUsuarioAbierto = false" class="px-4 py-2 border rounded-xl">Cancelar</button>
                            <button type="submit" :disabled="formUsuario.processing" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl">
                                {{ editandoUsuarioId ? 'Guardar Cambios' : 'Crear Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Editar Monto de Base Asignada -->
            <div v-if="modalEditarBaseAbierto" class="fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-sm w-full p-6 space-y-4 shadow-2xl border border-stone-200">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-base font-black text-stone-900">Editar Monto de Base en Efectivo</h3>
                        <button type="button" @click="modalEditarBaseAbierto = false" class="text-stone-400 hover:text-stone-700">✕</button>
                    </div>
                    <form @submit.prevent="confirmarEdicionBase" class="space-y-3 text-xs">
                        <p class="text-stone-600">
                            Caja: <strong>{{ baseEditando?.caja_nombre }}</strong> &bull; Fecha: <strong>{{ baseEditando?.fecha_formateada }}</strong>
                        </p>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Nuevo Monto Asignado ($) *</label>
                            <input
                                type="number"
                                step="1000"
                                min="0"
                                v-model.number="formEditarBase.monto"
                                class="w-full rounded-xl border border-stone-300 px-3 py-2 font-mono font-bold text-sm"
                                required
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Motivo / Observación de la Edición</label>
                            <input
                                type="text"
                                v-model="formEditarBase.observaciones"
                                placeholder="Ej: Ajuste de sencillo al iniciar turno..."
                                class="w-full rounded-xl border border-stone-300 px-3 py-2"
                            />
                        </div>
                        <div class="flex justify-end gap-2 pt-3 border-t">
                            <button type="button" @click="modalEditarBaseAbierto = false" class="px-4 py-2 border rounded-xl">Cancelar</button>
                            <button type="submit" :disabled="formEditarBase.processing" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl">
                                Guardar Edición
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    sucursales: { type: Object, required: true },
    todasSucursales: { type: Array, default: () => [] },
    sucursalActiva: { type: Object, default: null },
    cajasSede: { type: Array, default: () => [] },
    estacionesCocina: { type: Array, default: () => [] },
    usuariosCajeros: { type: Array, default: () => [] },
    usuariosCocina: { type: Array, default: () => [] },
    asignacionesBase: { type: Array, default: () => [] },
    bitacoraSede: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
});

const pestanaSede = ref('bases');
const terminoBuscar = ref(props.filtros?.buscar || '');
const mostrarModal = ref(false);
const editandoSucursalId = ref(null);

const form = useForm({
    nombre: '',
    slug: '',
    direccion: '',
    telefono: '',
    zona_horaria: 'America/Bogota',
    moneda: 'COP',
    idioma: 'es',
    activa: true,
    acepta_domicilios: true,
    acepta_para_llevar: true,
    acepta_en_sitio: true,
});

function filtrarSucursales() {
    router.get('/superadmin/sucursales', { buscar: terminoBuscar.value }, { preserveState: true });
}

function seleccionarSedeParaGestion(sucursalId) {
    router.post('/admin/seleccionar-sucursal', { sucursal_id: sucursalId }, {
        preserveScroll: true,
        onSuccess: () => {
            router.get('/superadmin/sucursales', { sucursal_id: sucursalId }, { preserveScroll: true });
        },
    });
}

function abrirModalCrearSucursal() {
    editandoSucursalId.value = null;
    form.reset();
    form.zona_horaria = 'America/Bogota';
    form.moneda = 'COP';
    form.idioma = 'es';
    form.activa = true;
    form.acepta_domicilios = true;
    form.acepta_para_llevar = true;
    form.acepta_en_sitio = true;
    mostrarModal.value = true;
}

function abrirModalEditarSucursal(sucursal) {
    editandoSucursalId.value = sucursal.id;
    form.nombre = sucursal.nombre;
    form.slug = sucursal.slug;
    form.direccion = sucursal.direccion;
    form.telefono = sucursal.telefono;
    form.zona_horaria = sucursal.zona_horaria || 'America/Bogota';
    form.moneda = sucursal.moneda || 'COP';
    form.idioma = sucursal.idioma || 'es';
    form.activa = Boolean(sucursal.activa);
    form.acepta_domicilios = sucursal.acepta_domicilios !== false;
    form.acepta_para_llevar = sucursal.acepta_para_llevar !== false;
    form.acepta_en_sitio = sucursal.acepta_en_sitio !== false;
    mostrarModal.value = true;
}

function guardarSucursal() {
    if (editandoSucursalId.value) {
        form.put(`/superadmin/sucursales/${editandoSucursalId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                mostrarModal.value = false;
                editandoSucursalId.value = null;
            },
        });
    } else {
        form.post('/superadmin/sucursales', {
            preserveScroll: true,
            onSuccess: () => {
                mostrarModal.value = false;
                form.reset();
            },
        });
    }
}

function alternarEstadoSucursal(sucursal) {
    router.post(`/superadmin/sucursales/${sucursal.id}/estado`, {}, {
        preserveScroll: true,
    });
}

function alternarModalidad(sucursalId, modalidad) {
    router.post(`/superadmin/sucursales/${sucursalId}/modalidad`, { modalidad }, {
        preserveScroll: true,
    });
}

function eliminar(id) {
    if (confirm('¿Deseas eliminar lógicamente esta sucursal?')) {
        router.delete(`/superadmin/sucursales/${id}`, { preserveScroll: true });
    }
}

// Gestión de Base en Efectivo por Caja y Fecha
const hoyIso = new Date().toISOString().slice(0, 10);
const formBase = useForm({
    sucursal_id: props.sucursalActiva?.id || null,
    caja_id: props.cajasSede[0]?.id || null,
    fecha: hoyIso,
    monto: 100000,
    observaciones: '',
});

function guardarBaseCaja() {
    formBase.sucursal_id = props.sucursalActiva?.id;
    formBase.post('/superadmin/sucursales/bases-caja', {
        preserveScroll: true,
        onSuccess: () => {
            formBase.observaciones = '';
        },
    });
}

const modalEditarBaseAbierto = ref(false);
const baseEditando = ref(null);
const formEditarBase = useForm({
    monto: 0,
    observaciones: '',
});

function abrirModalEditarBase(asig) {
    baseEditando.value = asig;
    formEditarBase.monto = asig.monto;
    formEditarBase.observaciones = asig.observaciones || '';
    modalEditarBaseAbierto.value = true;
}

function confirmarEdicionBase() {
    if (!baseEditando.value) return;
    formEditarBase.put(`/superadmin/sucursales/bases-caja/${baseEditando.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            modalEditarBaseAbierto.value = false;
            baseEditando.value = null;
        },
    });
}

// Gestión de Cajas
const formCaja = useForm({
    sucursal_id: props.sucursalActiva?.id || null,
    nombre: '',
    user_id: null,
    activa: true,
});

function guardarCaja() {
    formCaja.sucursal_id = props.sucursalActiva?.id;
    formCaja.post('/superadmin/sucursales/cajas', {
        preserveScroll: true,
        onSuccess: () => formCaja.reset('nombre', 'user_id'),
    });
}

function actualizarCaja(caja, cambios) {
    router.put(`/superadmin/sucursales/cajas/${caja.id}`, {
        nombre: cambios.nombre ?? caja.nombre,
        user_id: cambios.user_id !== undefined ? cambios.user_id : caja.user_id,
        activa: cambios.activa !== undefined ? cambios.activa : caja.activa,
    }, { preserveScroll: true });
}

// Gestión de Estaciones / Monitores de Cocina
const formCocina = useForm({
    sucursal_id: props.sucursalActiva?.id || null,
    nombre: '',
    user_id: null,
    activa: true,
});

function guardarEstacionCocina() {
    formCocina.sucursal_id = props.sucursalActiva?.id;
    formCocina.post('/superadmin/sucursales/estaciones-cocina', {
        preserveScroll: true,
        onSuccess: () => formCocina.reset('nombre', 'user_id'),
    });
}

function actualizarEstacionCocina(est, cambios) {
    router.put(`/superadmin/sucursales/estaciones-cocina/${est.id}`, {
        nombre: cambios.nombre ?? est.nombre,
        codigo: est.codigo,
        user_id: cambios.user_id !== undefined ? cambios.user_id : est.user_id,
        activa: cambios.activa !== undefined ? cambios.activa : est.activa,
    }, { preserveScroll: true });
}

// Gestión de Usuarios Operativos (Cajero / Cocinero)
const modalUsuarioAbierto = ref(false);
const editandoUsuarioId = ref(null);
const formUsuario = useForm({
    sucursal_id: props.sucursalActiva?.id || null,
    rol: 'cajero',
    nombre: '',
    email: '',
    telefono: '',
    password: '',
    activo: true,
    caja_id: null,
    estacion_cocina_id: null,
});

function abrirModalUsuario(rol) {
    editandoUsuarioId.value = null;
    formUsuario.reset();
    formUsuario.sucursal_id = props.sucursalActiva?.id;
    formUsuario.rol = rol;
    formUsuario.activo = true;
    modalUsuarioAbierto.value = true;
}

function abrirModalEditarUsuario(usuario, rol) {
    editandoUsuarioId.value = usuario.id;
    formUsuario.sucursal_id = props.sucursalActiva?.id;
    formUsuario.rol = rol;
    formUsuario.nombre = usuario.nombre;
    formUsuario.email = usuario.email;
    formUsuario.telefono = usuario.telefono || '';
    formUsuario.password = '';
    formUsuario.activo = Boolean(usuario.activo);
    modalUsuarioAbierto.value = true;
}

function guardarUsuarioOperativo() {
    formUsuario.sucursal_id = props.sucursalActiva?.id;
    if (editandoUsuarioId.value) {
        formUsuario.put(`/superadmin/sucursales/usuarios-operativos/${editandoUsuarioId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                modalUsuarioAbierto.value = false;
                editandoUsuarioId.value = null;
            },
        });
    } else {
        formUsuario.post('/superadmin/sucursales/usuarios-operativos', {
            preserveScroll: true,
            onSuccess: () => {
                modalUsuarioAbierto.value = false;
                formUsuario.reset();
            },
        });
    }
}
</script>
