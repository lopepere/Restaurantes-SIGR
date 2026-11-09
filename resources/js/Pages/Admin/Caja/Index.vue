<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>💵</span>
                        <span>Caja y Turnos</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Control de turnos, arqueo con cálculo de diferencias, cobro de pedidos y propina opcional (RF-CAJ-01..08, CA-29, CA-30).
                    </p>
                </div>

                <!-- Acciones Superiores -->
                <div class="flex flex-wrap items-center gap-2">
                    <div v-if="sucursales && sucursales.length > 1">
                        <select
                            v-model="sucursalSeleccionada"
                            @change="cambiarSucursal"
                            class="rounded-xl border border-stone-300 bg-white px-3 py-2 text-xs font-semibold text-stone-700 shadow-xs focus:ring-1 focus:ring-orange-500 cursor-pointer"
                        >
                            <option v-for="s in sucursales" :key="s.id" :value="s.id">
                                🏢 {{ s.nombre }}
                            </option>
                        </select>
                    </div>

                    <!-- Botón Configurar Base de Caja (Item 6) -->
                    <button
                        type="button"
                        @click="modalConfigurarBaseAbierto = true"
                        class="px-3.5 py-2 rounded-xl border border-stone-300 bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold shadow-xs transition flex items-center gap-1.5"
                        title="Configurar base inicial en efectivo por defecto"
                    >
                        <span>⚙️ Base:</span>
                        <span class="font-mono">${{ base_efectivo_inicial.toLocaleString() }}</span>
                    </button>

                    <button
                        v-if="!sesionActiva"
                        type="button"
                        @click="abrirModalApertura"
                        class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        🔓 Abrir Turno de Caja
                    </button>

                    <button
                        v-else
                        type="button"
                        @click="abrirModalCierre"
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        🔒 Cerrar Turno y Arqueo
                    </button>
                </div>
            </div>

            <!-- Tarjeta de Estado del Turno Activo -->
            <div v-if="sesionActiva" class="bg-white rounded-3xl border border-stone-200 shadow-xs p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-stone-100 pb-4">
                    <div>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200 mb-1">
                            Turno Abierto — {{ sesionActiva.caja_nombre }}
                        </span>
                        <h2 class="text-lg font-bold text-stone-900">
                            Cajero: {{ sesionActiva.cajero }}
                        </h2>
                        <span class="text-xs text-stone-400">Apertura: {{ sesionActiva.fecha_apertura }}</span>
                    </div>

                    <div class="text-right">
                        <span class="text-xs text-stone-500 font-medium block">Efectivo Físico Esperado en Cajón:</span>
                        <span class="text-2xl font-black font-mono text-emerald-600">
                            ${{ sesionActiva.efectivo_en_caja.toLocaleString() }}
                        </span>
                    </div>
                </div>

                <!-- Resumen de Métricas de la Sesión -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="p-3 bg-stone-50 rounded-xl">
                        <span class="text-stone-500 block">Base de Apertura:</span>
                        <strong class="text-sm font-mono text-stone-900">${{ sesionActiva.monto_apertura.toLocaleString() }}</strong>
                    </div>
                    <div class="p-3 bg-stone-50 rounded-xl">
                        <span class="text-stone-500 block">Ventas en Efectivo:</span>
                        <strong class="text-sm font-mono text-stone-900">${{ sesionActiva.ventas_efectivo.toLocaleString() }}</strong>
                    </div>
                    <div class="p-3 bg-stone-50 rounded-xl">
                        <span class="text-stone-500 block">Ventas Electrónicas:</span>
                        <strong class="text-sm font-mono text-stone-900">${{ sesionActiva.ventas_electronicas.toLocaleString() }}</strong>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-xl">
                        <span class="text-orange-700 block font-semibold">Total Ventas Turno:</span>
                        <strong class="text-sm font-mono text-orange-900">${{ sesionActiva.total_ventas.toLocaleString() }}</strong>
                    </div>
                </div>
            </div>

            <!-- Alerta si no hay turno abierto -->
            <div v-else class="bg-amber-50 border border-amber-200 rounded-3xl p-6 text-center text-xs text-amber-900">
                <span class="text-3xl block mb-2">🔒</span>
                <h3 class="font-bold text-sm">No hay un turno de caja abierto en esta sucursal</h3>
                <p class="mt-1 text-stone-600">
                    Abre un turno con la base inicial (${{ base_efectivo_inicial.toLocaleString() }}) para poder registrar cobros y pagos de comandas.
                </p>
                <button
                    type="button"
                    @click="abrirModalApertura"
                    class="mt-3 px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-bold transition shadow-xs"
                >
                    + Abrir Turno Ahora
                </button>
            </div>

            <!-- Grilla: Pedidos por Cobrar con Acordeón y Pagos Recientes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Columna Izquierda: Pedidos Pendientes de Pago con Acordeón (Item 11) -->
                <div class="bg-white rounded-3xl border border-stone-200 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-stone-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-sm text-stone-900 flex items-center gap-1.5">
                                <span>🛎️</span>
                                <span>Comandas Pendientes de Pago</span>
                            </h3>
                            <p class="text-[11px] text-stone-400">Toca la flecha para desplegar y verificar o anular ítems.</p>
                        </div>
                        <span class="text-xs text-stone-500 font-mono font-bold">{{ pedidosPorCobrar.length }} pendiente(s)</span>
                    </div>

                    <div class="divide-y divide-stone-100 max-h-[500px] overflow-y-auto">
                        <div
                            v-for="ped in pedidosPorCobrar"
                            :key="ped.id"
                            class="p-4 hover:bg-stone-50/70 transition space-y-2"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="toggleAcordeon(ped.id)"
                                        class="w-6 h-6 rounded-md bg-stone-100 hover:bg-stone-200 flex items-center justify-center text-xs font-bold text-stone-600"
                                        title="Ver platos"
                                    >
                                        <span :class="{'rotate-180': acordeonesAbiertos[ped.id]}" class="transition-transform duration-200">▼</span>
                                    </button>
                                    <div>
                                        <span class="font-mono font-bold text-xs text-orange-600 block">
                                            {{ ped.numero }}
                                        </span>
                                        <span class="text-xs font-semibold text-stone-800">
                                            {{ ped.mesa ? `🪑 ${ped.mesa}` : ped.tipo }} &bull; {{ ped.cliente }}
                                        </span>
                                    </div>
                                </div>

                                <div class="text-right space-y-1">
                                    <span class="text-sm font-black font-mono text-stone-900 block">
                                        ${{ ped.total.toLocaleString() }}
                                    </span>
                                    <button
                                        type="button"
                                        :disabled="!sesionActiva"
                                        @click="abrirModalPago(ped)"
                                        class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-lg text-xs font-bold shadow-xs transition"
                                    >
                                        Cobrar 💳
                                    </button>
                                </div>
                            </div>

                            <!-- Acordeón de Ítems de la Comanda -->
                            <div v-if="acordeonesAbiertos[ped.id]" class="mt-2 pt-2 border-t border-stone-200/80 bg-stone-50/80 rounded-xl p-3 space-y-1.5 text-xs">
                                <div
                                    v-for="it in ped.items"
                                    :key="it.id"
                                    class="bg-white p-2 rounded-lg border border-stone-200 flex items-center justify-between gap-2"
                                >
                                    <div class="flex-1 truncate">
                                        <span class="font-bold text-stone-800">{{ it.nombre }}</span>
                                        <span class="text-[11px] text-stone-500 ml-1">({{ it.cantidad }}x)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold">${{ it.total.toLocaleString() }}</span>
                                        <button
                                            type="button"
                                            @click="abrirModalAnularItem(ped, it)"
                                            class="text-rose-600 hover:text-rose-800 font-bold text-[11px] px-1.5 py-0.5 rounded-md bg-rose-50"
                                            title="Anular ítem mal facturado"
                                        >
                                            Anular
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="pedidosPorCobrar.length === 0" class="p-8 text-center text-stone-400 text-xs">
                            No hay pedidos pendientes de cobro en esta sucursal.
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Cobros Registrados en el Turno -->
                <div class="bg-white rounded-3xl border border-stone-200 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-stone-100 flex items-center justify-between">
                        <h3 class="font-bold text-sm text-stone-900 flex items-center gap-1.5">
                            <span>🧾</span>
                            <span>Cobros Realizados en este Turno</span>
                        </h3>
                        <span class="text-xs text-stone-400 font-mono">{{ pagosSesion.length }} pago(s)</span>
                    </div>

                    <div class="divide-y divide-stone-100 max-h-[500px] overflow-y-auto">
                        <div
                            v-for="pago in pagosSesion"
                            :key="pago.id"
                            class="p-3.5 flex items-center justify-between text-xs hover:bg-stone-50/50"
                        >
                            <div>
                                <span class="font-mono font-bold text-stone-800">#{{ pago.pedido_numero }}</span>
                                <span class="text-stone-400 text-[11px] ml-2 font-mono">{{ pago.hora }}</span>
                                <span class="block text-stone-500 text-[11px]">
                                    {{ pago.metodo }}
                                    <span v-if="pago.referencia_transaccion" class="font-mono font-semibold text-stone-700">
                                        &bull; Ref: {{ pago.referencia_transaccion }}
                                    </span>
                                </span>
                                <span v-if="pago.nota_adicional" class="block text-[10px] text-stone-400 italic">
                                    Nota: {{ pago.nota_adicional }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold font-mono text-stone-900 block">${{ pago.monto.toLocaleString() }}</span>
                                <span v-if="pago.cambio > 0" class="text-[10px] text-stone-400 font-mono">
                                    Devuelta: ${{ pago.cambio.toLocaleString() }}
                                </span>
                            </div>
                        </div>

                        <div v-if="pagosSesion.length === 0" class="p-8 text-center text-stone-400 text-xs">
                            Aún no se han registrado pagos en la sesión actual.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widget en Tiempo Real: Facturas Anuladas y Devoluciones de Compras (Item 11) -->
            <div class="bg-white rounded-3xl border border-stone-200 shadow-xs p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-stone-100 pb-4">
                    <div>
                        <h3 class="font-black text-base text-stone-900 flex items-center gap-2">
                            <span>🛡️</span>
                            <span>Auditoría en Tiempo Real: Facturas Anuladas y Devoluciones de Compras</span>
                        </h3>
                        <p class="text-xs text-stone-500">Registro en vivo de notas crédito, anulaciones fiscales y devoluciones a proveedores.</p>
                    </div>

                    <!-- Selector de Pestaña -->
                    <div class="flex items-center gap-1 bg-stone-100 p-1 rounded-xl text-xs font-bold">
                        <button
                            type="button"
                            @click="pestanaAuditoria = 'facturas'"
                            :class="['px-3 py-1.5 rounded-lg transition', pestanaAuditoria === 'facturas' ? 'bg-white text-stone-900 shadow-xs' : 'text-stone-500 hover:text-stone-800']"
                        >
                            Facturas Anuladas ({{ facturasAnuladasRecientes.length }})
                        </button>
                        <button
                            type="button"
                            @click="pestanaAuditoria = 'compras'"
                            :class="['px-3 py-1.5 rounded-lg transition', pestanaAuditoria === 'compras' ? 'bg-white text-stone-900 shadow-xs' : 'text-stone-500 hover:text-stone-800']"
                        >
                            Devoluciones Compras ({{ devolucionesRecientes.length }})
                        </button>
                    </div>
                </div>

                <!-- Tabla Facturas Anuladas -->
                <div v-if="pestanaAuditoria === 'facturas'" class="overflow-x-auto">
                    <table v-if="facturasAnuladasRecientes.length > 0" class="w-full text-left text-xs">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Número FAC</th>
                                <th class="p-3">Cliente</th>
                                <th class="p-3">Fecha Anulación</th>
                                <th class="p-3">Motivo Registrado</th>
                                <th class="p-3">Anulado Por</th>
                                <th class="p-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="fac in facturasAnuladasRecientes" :key="fac.id" class="hover:bg-stone-50">
                                <td class="p-3 font-mono font-bold text-rose-700">{{ fac.numero }}</td>
                                <td class="p-3 font-semibold">{{ fac.cliente }}</td>
                                <td class="p-3 font-mono text-stone-500">{{ fac.anulado_at }}</td>
                                <td class="p-3 text-stone-600 max-w-xs truncate">{{ fac.motivo }}</td>
                                <td class="p-3 text-stone-600">{{ fac.anulado_por }}</td>
                                <td class="p-3 font-mono font-bold text-right text-stone-900">${{ fac.total.toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="text-center py-6 text-stone-400 text-xs">
                        No hay facturas anuladas recientes en esta sucursal.
                    </div>
                </div>

                <!-- Tabla Devoluciones de Compras -->
                <div v-if="pestanaAuditoria === 'compras'" class="overflow-x-auto">
                    <table v-if="devolucionesRecientes.length > 0" class="w-full text-left text-xs">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Factura Compra</th>
                                <th class="p-3">Modalidad</th>
                                <th class="p-3">Fecha</th>
                                <th class="p-3">Motivo Devolución</th>
                                <th class="p-3">Registrado Por</th>
                                <th class="p-3 text-right">Total Devuelto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="dev in devolucionesRecientes" :key="dev.id" class="hover:bg-stone-50">
                                <td class="p-3 font-mono font-bold text-amber-700">{{ dev.factura_compra }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 uppercase">
                                        {{ dev.tipo }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-stone-500">{{ dev.fecha }}</td>
                                <td class="p-3 text-stone-600 max-w-xs truncate">{{ dev.motivo }}</td>
                                <td class="p-3 text-stone-600">{{ dev.usuario }}</td>
                                <td class="p-3 font-mono font-bold text-right text-stone-900">${{ dev.total_devuelto.toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="text-center py-6 text-stone-400 text-xs">
                        No hay devoluciones de compras registradas en esta sucursal.
                    </div>
                </div>
            </div>

            <!-- Historial de Cierres de Turno -->
            <div class="bg-white rounded-3xl border border-stone-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-sm text-stone-900">Historial de Turnos Cerrados Recientemente</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-stone-50 text-stone-500 uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Caja</th>
                                <th class="p-3">Cajero</th>
                                <th class="p-3">Apertura</th>
                                <th class="p-3">Cierre</th>
                                <th class="p-3 text-right">Base</th>
                                <th class="p-3 text-right">Declarado</th>
                                <th class="p-3 text-right">Calculado</th>
                                <th class="p-3 text-right">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="cierre in cierresRecientes" :key="cierre.id" class="hover:bg-stone-50">
                                <td class="p-3 font-bold">{{ cierre.caja }}</td>
                                <td class="p-3">{{ cierre.cajero }}</td>
                                <td class="p-3 font-mono text-stone-500">{{ cierre.apertura }}</td>
                                <td class="p-3 font-mono text-stone-500">{{ cierre.cierre }}</td>
                                <td class="p-3 font-mono text-right">${{ cierre.monto_apertura.toLocaleString() }}</td>
                                <td class="p-3 font-mono text-right">${{ cierre.monto_declarado.toLocaleString() }}</td>
                                <td class="p-3 font-mono text-right">${{ cierre.monto_calculado.toLocaleString() }}</td>
                                <td :class="['p-3 font-mono font-bold text-right', cierre.diferencia === 0 ? 'text-stone-400' : (cierre.diferencia > 0 ? 'text-emerald-600' : 'text-rose-600')]">
                                    ${{ cierre.diferencia.toLocaleString() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Apertura de Turno -->
        <div v-if="modalAperturaAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">Apertura de Turno de Caja</h3>
                    <button @click="modalAperturaAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarApertura" class="space-y-4 text-xs">
                    <div>
                        <label for="caja_apertura" class="block font-semibold text-stone-700 mb-1">Caja Física Asignada *</label>
                        <select id="caja_apertura" v-model="formApertura.caja_id" class="w-full rounded-xl border border-stone-300 p-2.5 text-xs" required>
                            <option v-for="caja in cajas" :key="caja.id" :value="caja.id">
                                {{ caja.nombre }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="monto_apertura" class="block font-semibold text-stone-700 mb-1">Monto Base de Apertura ($) *</label>
                        <input id="monto_apertura" type="number" step="100" min="0" v-model.number="formApertura.monto_apertura" class="w-full rounded-xl border border-stone-300 p-2.5 text-xs font-mono font-bold" required />
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalAperturaAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">Cancelar</button>
                        <button type="submit" :disabled="formApertura.processing" class="px-5 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold transition">
                            {{ formApertura.processing ? 'Abriendo...' : 'Abrir Turno' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Configurar Base Inicial (Item 6) -->
        <div v-if="modalConfigurarBaseAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-black text-base text-stone-900">Configurar Base Inicial</h3>
                    <button @click="modalConfigurarBaseAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="guardarBaseInicial" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Monto Base por Defecto ($) *</label>
                        <input
                            type="number"
                            step="1000"
                            min="0"
                            v-model.number="formBase.base_efectivo_inicial"
                            class="w-full rounded-xl border border-stone-300 p-2.5 font-mono font-bold text-sm"
                            required
                        />
                        <p class="text-[11px] text-stone-400 mt-1">Este monto se preseleccionará al abrir cada turno en esta sucursal.</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="modalConfigurarBaseAbierto = false" class="px-4 py-2 border rounded-xl text-stone-600">Cancelar</button>
                        <button type="submit" :disabled="formBase.processing" class="px-5 py-2 bg-stone-900 text-white font-bold rounded-xl transition">
                            Guardar Base
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Cobro / Pago -->
        <div v-if="modalPagoAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <div>
                        <h3 class="font-bold text-base text-stone-900">
                            Cobro de Pedido #{{ pedidoSeleccionado?.numero }}
                        </h3>
                        <span class="text-xs text-stone-400">{{ pedidoSeleccionado?.cliente }}</span>
                    </div>
                    <button @click="modalPagoAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-stone-700 mb-1">Método de Pago</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="metodoSeleccionado = 'efectivo'"
                                :class="['py-2 rounded-xl border font-bold text-xs', metodoSeleccionado === 'efectivo' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200']"
                            >
                                Efectivo
                            </button>
                            <button
                                type="button"
                                @click="metodoSeleccionado = 'tarjeta_debito'"
                                :class="['py-2 rounded-xl border font-bold text-xs', metodoSeleccionado === 'tarjeta_debito' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200']"
                            >
                                Tarjeta
                            </button>
                            <button
                                type="button"
                                @click="metodoSeleccionado = 'transferencia'"
                                :class="['py-2 rounded-xl border font-bold text-xs', metodoSeleccionado === 'transferencia' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200']"
                            >
                                Transferencia
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-stone-700 mb-1">Propina Voluntaria (Opcional)</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button type="button" @click="establecerPropina(0)" class="py-1.5 rounded-lg border text-stone-700 bg-stone-50 font-bold">0%</button>
                            <button type="button" @click="establecerPropina(5)" class="py-1.5 rounded-lg border text-stone-700 bg-stone-50 font-bold">5%</button>
                            <button type="button" @click="establecerPropina(10)" class="py-1.5 rounded-lg border text-stone-700 bg-stone-50 font-bold">10%</button>
                            <button type="button" @click="establecerPropina(15)" class="py-1.5 rounded-lg border text-stone-700 bg-stone-50 font-bold">15%</button>
                        </div>
                    </div>

                    <div v-if="metodoSeleccionado === 'efectivo'">
                        <label class="block font-semibold text-stone-700 mb-1">Efectivo Recibido ($)</label>
                        <input type="number" step="100" min="0" v-model.number="montoRecibidoInput" class="w-full rounded-xl border border-stone-300 p-2 text-xs font-mono font-bold" />
                        <span class="text-[11px] text-stone-400 mt-1 block">
                            Cambio a Devolver: ${{ Math.max(0, montoRecibidoInput - calcularTotalCobro()).toLocaleString() }}
                        </span>
                    </div>

                    <div v-else class="p-3 bg-stone-50 rounded-xl border border-stone-200 space-y-2.5">
                        <div>
                            <label class="block font-semibold text-stone-700 mb-1">
                                Número de la Transacción / Referencia <span class="text-rose-600">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="referenciaTransaccionInput"
                                placeholder="Ej: 849201, APROB-1029, Nequi-99381..."
                                class="w-full rounded-xl border border-stone-300 p-2 text-xs font-mono font-bold"
                                required
                            />
                            <p v-if="errorReferenciaPago" class="text-[11px] text-rose-600 font-semibold mt-1">
                                {{ errorReferenciaPago }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-semibold text-stone-700 mb-1">
                                Nota Adicional <span class="text-stone-400 font-normal">(Opcional)</span>
                            </label>
                            <input
                                type="text"
                                v-model="notaAdicionalInput"
                                placeholder="Ej: Datáfono Redeban, transferencia Bancolombia..."
                                class="w-full rounded-xl border border-stone-300 p-2 text-xs"
                            />
                        </div>
                    </div>

                    <div class="p-3 bg-stone-50 rounded-xl space-y-1 font-mono text-xs">
                        <div class="flex justify-between">
                            <span>Subtotal + Imp:</span>
                            <span>${{ (pedidoSeleccionado?.subtotal + pedidoSeleccionado?.impuestos).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Propina:</span>
                            <span>${{ formPago.propina_valor.toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-sm text-stone-900 pt-1 border-t border-stone-200">
                            <span>Total Cobro:</span>
                            <span>${{ calcularTotalCobro().toLocaleString() }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalPagoAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">Cancelar</button>
                        <button type="button" @click="ejecutarCobro" :disabled="formPago.processing" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition">
                            {{ formPago.processing ? 'Cobrando...' : 'Confirmar Cobro' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Anular Ítem (Item 11) -->
        <div v-if="modalAnularItemAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-black text-base text-stone-900">Anular Ítem del Pedido</h3>
                    <button @click="modalAnularItemAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <div class="space-y-3 text-xs">
                    <p class="text-stone-600">
                        Vas a anular <strong>{{ itemParaAnular?.nombre }}</strong> de la comanda <strong>#{{ pedidoDeItemParaAnular?.numero }}</strong>.
                        Se ajustará la cuenta y se registrará en la bitácora.
                    </p>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Motivo de Anulación *</label>
                        <input
                            type="text"
                            v-model="motivoAnulacionItem"
                            placeholder="Ej: Error al digitar comanda, plato cancelado por cliente..."
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800"
                            required
                        />
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="modalAnularItemAbierto = false" class="px-4 py-2 border rounded-xl text-stone-600">Cancelar</button>
                        <button
                            type="button"
                            @click="confirmarAnulacionItem"
                            :disabled="!motivoAnulacionItem"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-bold rounded-xl transition"
                        >
                            Confirmar Anulación
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Cierre de Turno -->
        <div v-if="modalCierreAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">Cierre de Turno y Arqueo de Caja</h3>
                    <button @click="modalCierreAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarCierre" class="space-y-4 text-xs">
                    <div>
                        <label for="efectivo_declarado" class="block font-semibold text-stone-700 mb-1">Efectivo Físico Contado en Arqueo ($) *</label>
                        <input id="efectivo_declarado" type="number" step="100" min="0" v-model.number="formCierre.monto_cierre_declarado" class="w-full rounded-xl border border-stone-300 p-2.5 text-xs font-mono font-bold" required />
                    </div>
                    <div>
                        <label for="obs_cierre" class="block font-semibold text-stone-700 mb-1">Observaciones / Justificación de descuadre</label>
                        <textarea id="obs_cierre" v-model="formCierre.observaciones" rows="2" placeholder="Notas sobre el cierre o novedades del turno..." class="w-full rounded-xl border border-stone-300 p-2 text-xs"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalCierreAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">Cancelar</button>
                        <button type="submit" :disabled="formCierre.processing" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold transition">
                            {{ formCierre.processing ? 'Cerrando...' : 'Cerrar Turno' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    cajas: { type: Array, default: () => [] },
    sesionActiva: { type: Object, default: null },
    pagosSesion: { type: Array, default: () => [] },
    pedidosPorCobrar: { type: Array, default: () => [] },
    cierresRecientes: { type: Array, default: () => [] },
    facturasAnuladasRecientes: { type: Array, default: () => [] },
    devolucionesRecientes: { type: Array, default: () => [] },
    base_efectivo_inicial: { type: Number, default: 100000 },
    sucursales: { type: Array, default: () => [] },
    sucursal_id: { type: Number, required: true },
});

const sucursalSeleccionada = ref(props.sucursal_id);
const pestanaAuditoria = ref('facturas');
const acordeonesAbiertos = ref({});

function toggleAcordeon(id) {
    acordeonesAbiertos.value[id] = !acordeonesAbiertos.value[id];
}

const cambiarSucursal = () => {
    router.post('/admin/seleccionar-sucursal', { sucursal_id: sucursalSeleccionada.value }, {
        onSuccess: () => {
            router.get('/admin/caja', { sucursal_id: sucursalSeleccionada.value });
        },
    });
};

// Modal Configurar Base Inicial (Item 6)
const modalConfigurarBaseAbierto = ref(false);
const formBase = useForm({
    base_efectivo_inicial: props.base_efectivo_inicial || 100000,
    sucursal_id: props.sucursal_id,
});

function guardarBaseInicial() {
    formBase.post('/admin/caja/configurar-base', {
        preserveScroll: true,
        onSuccess: () => { modalConfigurarBaseAbierto.value = false; },
    });
}

// Modal Apertura
const modalAperturaAbierto = ref(false);
const formApertura = useForm({
    caja_id: props.cajas[0]?.id || null,
    monto_apertura: props.base_efectivo_inicial || 100000,
});

const abrirModalApertura = () => {
    formApertura.caja_id = props.cajas[0]?.id || null;
    formApertura.monto_apertura = props.base_efectivo_inicial || 100000;
    modalAperturaAbierto.value = true;
};

const ejecutarApertura = () => {
    formApertura.post('/admin/caja/apertura', {
        preserveScroll: true,
        onSuccess: () => { modalAperturaAbierto.value = false; },
    });
};

// Modal Cobro / Pago
const modalPagoAbierto = ref(false);
const pedidoSeleccionado = ref(null);
const metodoSeleccionado = ref('efectivo');
const montoRecibidoInput = ref(0);
const referenciaTransaccionInput = ref('');
const notaAdicionalInput = ref('');
const errorReferenciaPago = ref('');

const formPago = useForm({
    propina_valor: 0,
    pagos: [],
});

const abrirModalPago = (pedido) => {
    pedidoSeleccionado.value = pedido;
    formPago.propina_valor = 0;
    metodoSeleccionado.value = 'efectivo';
    referenciaTransaccionInput.value = '';
    notaAdicionalInput.value = '';
    errorReferenciaPago.value = '';
    montoRecibidoInput.value = (pedido.subtotal + pedido.impuestos);
    modalPagoAbierto.value = true;
};

const establecerPropina = (porcentaje) => {
    if (!pedidoSeleccionado.value) return;
    const base = pedidoSeleccionado.value.subtotal;
    formPago.propina_valor = Math.round((base * porcentaje) / 100);
};

const calcularTotalCobro = () => {
    if (!pedidoSeleccionado.value) return 0;
    return (pedidoSeleccionado.value.subtotal + pedidoSeleccionado.value.impuestos) + formPago.propina_valor;
};

const ejecutarCobro = () => {
    if (!pedidoSeleccionado.value) return;

    if (metodoSeleccionado.value !== 'efectivo' && !referenciaTransaccionInput.value.trim()) {
        errorReferenciaPago.value = 'Debes ingresar el número de la transacción para pagos con tarjeta o transferencia.';
        return;
    }
    errorReferenciaPago.value = '';

    const total = calcularTotalCobro();
    formPago.pagos = [
        {
            metodo_pago: metodoSeleccionado.value,
            monto: total,
            monto_recibido: metodoSeleccionado.value === 'efectivo' ? montoRecibidoInput.value : total,
            referencia_transaccion: metodoSeleccionado.value !== 'efectivo' ? referenciaTransaccionInput.value.trim() : null,
            nota_adicional: metodoSeleccionado.value !== 'efectivo' && notaAdicionalInput.value.trim() ? notaAdicionalInput.value.trim() : null,
        },
    ];

    formPago.post(`/admin/caja/pagos/${pedidoSeleccionado.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { modalPagoAbierto.value = false; },
    });
};

// Modal Anular Ítem (Item 11)
const modalAnularItemAbierto = ref(false);
const itemParaAnular = ref(null);
const pedidoDeItemParaAnular = ref(null);
const motivoAnulacionItem = ref('');

function abrirModalAnularItem(pedido, item) {
    pedidoDeItemParaAnular.value = pedido;
    itemParaAnular.value = item;
    motivoAnulacionItem.value = '';
    modalAnularItemAbierto.value = true;
}

function confirmarAnulacionItem() {
    if (!pedidoDeItemParaAnular.value || !itemParaAnular.value || !motivoAnulacionItem.value) return;

    router.post(
        `/admin/caja/pedidos/${pedidoDeItemParaAnular.value.id}/items/${itemParaAnular.value.id}/anular`,
        { motivo: motivoAnulacionItem.value },
        {
            preserveScroll: true,
            onSuccess: () => { modalAnularItemAbierto.value = false; },
        }
    );
}

// Modal Cierre
const modalCierreAbierto = ref(false);
const formCierre = useForm({
    monto_cierre_declarado: 0,
    observaciones: '',
});

const abrirModalCierre = () => {
    formCierre.monto_cierre_declarado = props.sesionActiva?.efectivo_en_caja || 0;
    formCierre.observaciones = '';
    modalCierreAbierto.value = true;
};

const ejecutarCierre = () => {
    if (!props.sesionActiva) return;
    formCierre.post(`/admin/caja/cierre/${props.sesionActiva.id}`, {
        preserveScroll: true,
        onSuccess: () => { modalCierreAbierto.value = false; },
    });
};
</script>
