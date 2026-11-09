<template>
    <div class="min-h-screen bg-stone-100 flex flex-col font-sans text-stone-800">
        <!-- Barra Superior del Cajero -->
        <header class="bg-stone-900 text-white px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-md border-b border-stone-800">
            <div class="flex items-center space-x-3">
                <span class="text-3xl">💵</span>
                <div>
                    <h1 class="text-lg font-black tracking-tight flex items-center gap-2">
                        <span>Terminal de Caja</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            {{ sucursal?.nombre }}
                        </span>
                    </h1>
                    <p class="text-xs text-stone-400">
                        Cajero activo: <strong class="text-white">{{ $page.props.auth?.user?.name }}</strong>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs">
                <Link
                    href="/"
                    class="px-3 py-1.5 rounded-xl bg-stone-800 hover:bg-stone-700 text-stone-300 font-semibold border border-stone-700 transition"
                >
                    🏠 Menú
                </Link>

                <Link
                    v-if="esAdmin"
                    href="/admin"
                    class="px-3 py-1.5 rounded-xl bg-orange-600/20 hover:bg-orange-600/30 text-orange-300 font-bold border border-orange-500/30 transition"
                >
                    👨‍💼 Panel Admin
                </Link>

                <button
                    type="button"
                    @click="logout"
                    class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold transition cursor-pointer"
                >
                    Salir 🚪
                </button>
            </div>
        </header>

        <!-- Contenedor Principal -->
        <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 space-y-6">
            <!-- Estado del Turno de Caja -->
            <div class="bg-white rounded-3xl border border-stone-200 shadow-xs p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-stone-100 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider',
                                    sesionActiva
                                        ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                                        : 'bg-amber-100 text-amber-800 border border-amber-200'
                                ]"
                            >
                                {{ sesionActiva ? `● Turno Abierto — ${sesionActiva.caja_nombre}` : '○ Caja Cerrada' }}
                            </span>
                            <span v-if="sesionActiva" class="text-xs text-stone-500">
                                Abierto desde {{ sesionActiva.fecha_apertura }}
                            </span>
                        </div>
                        <h2 class="text-xl font-black text-stone-900 mt-1">
                            {{ sesionActiva ? `Atendido por ${sesionActiva.cajero}` : 'Inicia turno para registrar cobros' }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="!sesionActiva"
                            type="button"
                            @click="abrirModalApertura"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition"
                        >
                            🔓 Abrir Turno de Caja
                        </button>
                        <button
                            v-else
                            type="button"
                            @click="abrirModalCierre"
                            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition"
                        >
                            🔒 Cerrar Turno y Arqueo
                        </button>
                    </div>
                </div>

                <!-- Métricas de Efectivo si hay turno abierto -->
                <div v-if="sesionActiva" class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="p-3 bg-stone-50 rounded-2xl border border-stone-100">
                        <span class="text-stone-500 block">Base de Apertura:</span>
                        <strong class="text-sm font-mono font-black text-stone-900">${{ formatearDinero(sesionActiva.monto_apertura) }}</strong>
                    </div>
                    <div class="p-3 bg-stone-50 rounded-2xl border border-stone-100">
                        <span class="text-stone-500 block">Cobros en Efectivo:</span>
                        <strong class="text-sm font-mono font-black text-stone-900">${{ formatearDinero(sesionActiva.ventas_efectivo) }}</strong>
                    </div>
                    <div class="p-3 bg-stone-50 rounded-2xl border border-stone-100">
                        <span class="text-stone-500 block">Cobros Electrónicos:</span>
                        <strong class="text-sm font-mono font-black text-stone-900">${{ formatearDinero(sesionActiva.ventas_electronicas) }}</strong>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200">
                        <span class="text-emerald-800 block font-bold">Efectivo en Cajón:</span>
                        <strong class="text-base font-mono font-black text-emerald-900">${{ formatearDinero(sesionActiva.efectivo_en_caja) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Panel de Comandas por Cobrar con Acordeón (Item 6 & 11) -->
            <div class="bg-white rounded-3xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="p-6 border-b border-stone-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-base font-black text-stone-900 flex items-center gap-2">
                            <span>🛎️</span>
                            <span>Comandas Pendientes de Pago (En Espera de Cobro)</span>
                        </h2>
                        <p class="text-xs text-stone-500">Expande cada orden para revisar productos o anular ítems antes del cobro formal.</p>
                    </div>
                    <span class="text-xs font-bold font-mono px-3 py-1 rounded-full bg-stone-100 text-stone-700">
                        {{ pedidosPorCobrar.length }} por cobrar
                    </span>
                </div>

                <div v-if="pedidosPorCobrar.length === 0" class="text-center py-12 text-stone-400 text-xs">
                    <span class="text-3xl block mb-2">✅</span>
                    <p class="font-bold text-stone-700">No hay comandas pendientes de cobro</p>
                    <p class="text-stone-400 mt-0.5">Todas las órdenes registradas en sala y para llevar están al día.</p>
                </div>

                <div v-else class="divide-y divide-stone-100">
                    <div
                        v-for="pedido in pedidosPorCobrar"
                        :key="pedido.id"
                        class="p-5 hover:bg-stone-50/50 transition"
                    >
                        <!-- Fila Principal de la Comanda -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-start sm:items-center gap-3">
                                <button
                                    type="button"
                                    @click="toggleAcordeon(pedido.id)"
                                    class="w-7 h-7 rounded-lg bg-stone-100 hover:bg-stone-200 flex items-center justify-center text-xs font-bold text-stone-600 transition"
                                    title="Desplegar detalle de productos"
                                >
                                    <span :class="{'rotate-180': acordeonesAbiertos[pedido.id]}" class="transition-transform duration-200">▼</span>
                                </button>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-mono font-black text-base text-stone-900">{{ pedido.numero }}</span>
                                        <span v-if="pedido.mesa" class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200">
                                            📍 {{ pedido.mesa }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-700">
                                            {{ pedido.tipo }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-stone-500 mt-0.5">
                                        Cliente: <strong class="text-stone-800">{{ pedido.cliente }}</strong> &bull;
                                        {{ pedido.items.length }} producto(s)
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 sm:text-right">
                                <div>
                                    <span class="text-[10px] text-stone-400 font-semibold block uppercase">Total a Cobrar</span>
                                    <span class="text-lg font-mono font-black text-stone-900">${{ formatearDinero(pedido.total) }}</span>
                                </div>

                                <button
                                    type="button"
                                    @click="abrirModalPago(pedido)"
                                    :disabled="!sesionActiva"
                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:opacity-40 text-white font-bold text-xs rounded-xl shadow-xs transition active:scale-95"
                                >
                                    💳 Cobrar
                                </button>
                            </div>
                        </div>

                        <!-- Acordeón Desplegable de Ítems (Item 6 & 11) -->
                        <div v-if="acordeonesAbiertos[pedido.id]" class="mt-4 pt-3 border-t border-stone-200/80 bg-stone-50/70 rounded-2xl p-4 space-y-2">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-stone-500 mb-1">
                                Detalle de Platos y Consumos
                            </div>

                            <div
                                v-for="it in pedido.items"
                                :key="it.id"
                                class="bg-white p-2.5 rounded-xl border border-stone-200 flex items-center justify-between text-xs gap-3"
                            >
                                <div class="flex-1 min-w-0">
                                    <span class="font-bold text-stone-800 block truncate">{{ it.nombre }}</span>
                                    <span class="text-[11px] text-stone-500">
                                        {{ it.cantidad }} x ${{ formatearDinero(it.precio_unitario) }}
                                        <span v-if="it.notas" class="italic text-stone-400 font-normal">({{ it.notas }})</span>
                                    </span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-bold text-stone-900">${{ formatearDinero(it.total) }}</span>

                                    <!-- Botón Anular Ítem mal facturado -->
                                    <button
                                        type="button"
                                        @click="abrirModalAnularItem(pedido, it)"
                                        class="px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-bold border border-rose-200 transition"
                                        title="Anular este ítem mal facturado (queda registrado en bitácora)"
                                    >
                                        Anular
                                    </button>
                                </div>
                            </div>

                            <div class="pt-2 flex justify-between text-xs text-stone-600 font-semibold border-t border-stone-200/60">
                                <span>Subtotal: ${{ formatearDinero(pedido.subtotal) }} &bull; Impuestos: ${{ formatearDinero(pedido.impuestos) }}</span>
                                <span class="font-bold text-stone-900">Total: ${{ formatearDinero(pedido.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Búsqueda y Reimpresión de Facturas (Item 6) -->
            <div class="bg-white rounded-3xl border border-stone-200 shadow-xs p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-base font-black text-stone-900 flex items-center gap-2">
                            <span>🔍</span>
                            <span>Buscar Comprobante FAC para Reimpresión o Reenvío</span>
                        </h2>
                        <p class="text-xs text-stone-500">Ingresa número de comprobante, nombre de cliente o teléfono.</p>
                    </div>
                </div>

                <div class="relative">
                    <input
                        type="text"
                        v-model="busquedaFactura"
                        @input="buscarFacturas"
                        placeholder="Escribe número de factura o cliente (ej: FAC-001, Carlos, 300123...)"
                        class="w-full rounded-2xl border border-stone-300 px-4 py-2.5 text-xs text-stone-900 focus:ring-2 focus:ring-orange-500"
                    />
                </div>

                <!-- Resultados de búsqueda -->
                <div v-if="facturasResultados.length > 0" class="divide-y divide-stone-100 border border-stone-200 rounded-2xl overflow-hidden text-xs">
                    <div
                        v-for="fac in facturasResultados"
                        :key="fac.id"
                        class="p-3.5 bg-stone-50 flex items-center justify-between gap-3 hover:bg-stone-100/60 transition"
                    >
                        <div>
                            <span class="font-mono font-bold text-stone-900">{{ fac.numero }}</span>
                            <span class="text-stone-500 ml-2">&bull; {{ fac.cliente_nombre }} &bull; ${{ formatearDinero(fac.total) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a
                                :href="`/comprobante/${fac.codigo_verificacion}/tirilla`"
                                target="_blank"
                                class="px-3 py-1.5 rounded-xl bg-white border border-stone-300 font-bold hover:bg-stone-50 text-stone-700 shadow-xs transition"
                            >
                                🖨️ Reimprimir
                            </a>
                            <button
                                type="button"
                                @click="reenviarFactura(fac.id)"
                                class="px-3 py-1.5 rounded-xl bg-orange-600 text-white font-bold hover:bg-orange-700 shadow-xs transition"
                            >
                                ✉️ Reenviar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modal Apertura Turno -->
        <div v-if="modalAperturaAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-black text-base text-stone-900">Apertura de Turno de Caja</h3>
                    <button @click="modalAperturaAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarApertura" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Caja Física Asignada *</label>
                        <select v-model="formApertura.caja_id" class="w-full rounded-xl border border-stone-300 p-2.5" required>
                            <option v-for="c in cajas" :key="c.id" :value="c.id">
                                🖥️ {{ c.nombre }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Base en Efectivo Inicial ($) *</label>
                        <input
                            type="number"
                            step="100"
                            min="0"
                            v-model.number="formApertura.monto_apertura"
                            class="w-full rounded-xl border border-stone-300 p-2.5 font-mono font-bold"
                            required
                        />
                        <p class="text-[11px] text-stone-400 mt-1">
                            Saldo inicial en monedas y billetes entregado al cajero para dar cambio.
                        </p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="modalAperturaAbierto = false" class="px-4 py-2 border rounded-xl text-stone-600">Cancelar</button>
                        <button type="submit" :disabled="formApertura.processing" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition">
                            {{ formApertura.processing ? 'Abriendo...' : 'Abrir Turno' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Cierre de Turno y Arqueo -->
        <div v-if="modalCierreAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-black text-base text-stone-900">Cierre de Turno y Arqueo Físico</h3>
                    <button @click="modalCierreAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarCierre" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Efectivo Físico Contado en Cajón ($) *</label>
                        <input
                            type="number"
                            step="100"
                            min="0"
                            v-model.number="formCierre.monto_cierre_declarado"
                            class="w-full rounded-xl border border-stone-300 p-2.5 font-mono font-bold"
                            required
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Observaciones / Novedades</label>
                        <textarea
                            v-model="formCierre.observaciones"
                            rows="2"
                            placeholder="Notas sobre el cierre o justificación de diferencias..."
                            class="w-full rounded-xl border border-stone-300 p-2"
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="modalCierreAbierto = false" class="px-4 py-2 border rounded-xl text-stone-600">Cancelar</button>
                        <button type="submit" :disabled="formCierre.processing" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition">
                            {{ formCierre.processing ? 'Cerrando...' : 'Cerrar Turno' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Cobro / Pago -->
        <div v-if="modalPagoAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <div>
                        <h3 class="font-black text-base text-stone-900">
                            Registrar Cobro — Pedido #{{ pedidoSeleccionado?.numero }}
                        </h3>
                        <p class="text-[11px] text-stone-500">Cliente: {{ pedidoSeleccionado?.cliente }}</p>
                    </div>
                    <button @click="modalPagoAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Método de Pago -->
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Método de Pago:</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="metodoSeleccionado = 'efectivo'"
                                :class="[
                                    'py-2 px-3 rounded-xl border font-bold text-xs text-center transition',
                                    metodoSeleccionado === 'efectivo'
                                        ? 'bg-orange-600 text-white border-orange-600'
                                        : 'bg-stone-50 text-stone-700 border-stone-200'
                                ]"
                            >
                                💵 Efectivo
                            </button>
                            <button
                                type="button"
                                @click="metodoSeleccionado = 'tarjeta_debito'"
                                :class="[
                                    'py-2 px-3 rounded-xl border font-bold text-xs text-center transition',
                                    metodoSeleccionado === 'tarjeta_debito'
                                        ? 'bg-orange-600 text-white border-orange-600'
                                        : 'bg-stone-50 text-stone-700 border-stone-200'
                                ]"
                            >
                                💳 Tarjeta
                            </button>
                            <button
                                type="button"
                                @click="metodoSeleccionado = 'transferencia'"
                                :class="[
                                    'py-2 px-3 rounded-xl border font-bold text-xs text-center transition',
                                    metodoSeleccionado === 'transferencia'
                                        ? 'bg-orange-600 text-white border-orange-600'
                                        : 'bg-stone-50 text-stone-700 border-stone-200'
                                ]"
                            >
                                📱 Transferencia
                            </button>
                        </div>
                    </div>

                    <!-- Propina sugerida -->
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Propina Voluntaria (Opcional):</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button
                                type="button"
                                @click="establecerPropina(0)"
                                :class="['py-1.5 rounded-lg border font-bold text-xs', propinaPorcentaje === 0 ? 'bg-stone-900 text-white' : 'bg-stone-50 text-stone-600']"
                            >
                                Sin propina
                            </button>
                            <button
                                type="button"
                                @click="establecerPropina(5)"
                                :class="['py-1.5 rounded-lg border font-bold text-xs', propinaPorcentaje === 5 ? 'bg-orange-600 text-white' : 'bg-stone-50 text-stone-600']"
                            >
                                5%
                            </button>
                            <button
                                type="button"
                                @click="establecerPropina(10)"
                                :class="['py-1.5 rounded-lg border font-bold text-xs', propinaPorcentaje === 10 ? 'bg-orange-600 text-white' : 'bg-stone-50 text-stone-600']"
                            >
                                10%
                            </button>
                            <button
                                type="button"
                                @click="establecerPropina(15)"
                                :class="['py-1.5 rounded-lg border font-bold text-xs', propinaPorcentaje === 15 ? 'bg-orange-600 text-white' : 'bg-stone-50 text-stone-600']"
                            >
                                15%
                            </button>
                        </div>
                    </div>

                    <!-- Si es efectivo: Monto Recibido y Cambio -->
                    <div v-if="metodoSeleccionado === 'efectivo'" class="p-3 bg-stone-50 rounded-2xl border border-stone-200 space-y-2">
                        <label class="block font-bold text-stone-700">Monto Recibido del Cliente ($):</label>
                        <input
                            type="number"
                            step="100"
                            v-model.number="montoRecibidoInput"
                            class="w-full rounded-xl border border-stone-300 p-2 font-mono font-bold text-base"
                        />
                        <div class="flex justify-between items-center text-xs font-bold pt-1">
                            <span class="text-stone-500">Cambio a Devolver:</span>
                            <span class="font-mono text-emerald-600 text-sm">
                                ${{ formatearDinero(Math.max(0, montoRecibidoInput - calcularTotalCobro())) }}
                            </span>
                        </div>
                    </div>

                    <!-- Si es Tarjeta o Transferencia: Número de transacción (obligatorio) y Nota adicional (opcional) -->
                    <div v-else class="p-3 bg-stone-50 rounded-2xl border border-stone-200 space-y-3">
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">
                                Número de la Transacción / Referencia <span class="text-rose-600">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="referenciaTransaccionInput"
                                placeholder="Ej: 849201, APROB-1029, Nequi-99381..."
                                class="w-full rounded-xl border border-stone-300 p-2.5 text-xs font-mono font-bold text-stone-900"
                                required
                            />
                            <p v-if="errorReferenciaPago" class="text-[11px] text-rose-600 font-semibold mt-1">
                                {{ errorReferenciaPago }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-bold text-stone-700 mb-1">
                                Nota Adicional <span class="text-stone-400 font-normal">(Opcional)</span>
                            </label>
                            <input
                                type="text"
                                v-model="notaAdicionalInput"
                                placeholder="Ej: Datáfono Redeban, cuenta ahorros Bancolombia..."
                                class="w-full rounded-xl border border-stone-300 p-2 text-xs text-stone-800"
                            />
                        </div>
                    </div>

                    <!-- Resumen de Totales -->
                    <div class="p-3 bg-orange-50/60 rounded-2xl border border-orange-200 space-y-1 font-semibold text-xs">
                        <div class="flex justify-between">
                            <span class="text-stone-600">Consumo Platos:</span>
                            <span class="font-mono text-stone-900">${{ formatearDinero(pedidoSeleccionado?.subtotal + pedidoSeleccionado?.impuestos) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-600">Propina ({{ propinaPorcentaje }}%):</span>
                            <span class="font-mono text-stone-900">${{ formatearDinero(formPago.propina_valor) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-black text-orange-950 pt-1 border-t border-orange-200">
                            <span>Total a Cobrar:</span>
                            <span class="font-mono">${{ formatearDinero(calcularTotalCobro()) }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="modalPagoAbierto = false" class="px-4 py-2 border rounded-xl text-stone-600">Cancelar</button>
                        <button
                            type="button"
                            @click="ejecutarCobro"
                            :disabled="formPago.processing"
                            class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl transition shadow-xs"
                        >
                            {{ formPago.processing ? 'Registrando...' : 'Confirmar Cobro' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Anular Ítem de Pedido (Item 11) -->
        <div v-if="modalAnularItemAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-black text-base text-stone-900">Anular Ítem del Pedido</h3>
                    <button @click="modalAnularItemAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <p class="text-stone-600">
                        Vas a anular <strong>{{ itemParaAnular?.nombre }}</strong> del Pedido <strong>#{{ pedidoDeItemParaAnular?.numero }}</strong>.
                        Se restará del cobro y quedará constancia en la bitácora de auditoría.
                    </p>

                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Motivo de la anulación *</label>
                        <input
                            type="text"
                            v-model="motivoAnulacionItem"
                            placeholder="Ej: Plato mal ordenado, error del mesero, cambio de plato..."
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
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const esAdmin = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    return roles.includes('admin') || roles.includes('super_admin');
});

const props = defineProps({
    sucursal: Object,
    cajas: Array,
    asignacionesHoy: { type: Object, default: () => ({}) },
    sesionActiva: Object,
    pagosSesion: Array,
    pedidosPorCobrar: Array,
    base_efectivo_inicial: Number,
});

// Acordeones reactivos de pedidos por cobrar
const acordeonesAbiertos = ref({});
function toggleAcordeon(id) {
    acordeonesAbiertos.value[id] = !acordeonesAbiertos.value[id];
}

function formatearDinero(valor) {
    return Number(valor || 0).toLocaleString('es-CO');
}

// Modal Apertura
const modalAperturaAbierto = ref(false);
const formApertura = useForm({
    caja_id: props.cajas[0]?.id || null,
    monto_apertura: props.base_efectivo_inicial || 100000,
});

function abrirModalApertura() {
    const primeraCajaId = props.cajas[0]?.id || null;
    formApertura.caja_id = primeraCajaId;
    formApertura.monto_apertura = (primeraCajaId && props.asignacionesHoy?.[primeraCajaId] !== undefined)
        ? props.asignacionesHoy[primeraCajaId]
        : (props.base_efectivo_inicial || 100000);
    modalAperturaAbierto.value = true;
}

function ejecutarApertura() {
    formApertura.post('/cajero/apertura', {
        preserveScroll: true,
        onSuccess: () => { modalAperturaAbierto.value = false; },
    });
}

// Modal Cierre
const modalCierreAbierto = ref(false);
const formCierre = useForm({
    monto_cierre_declarado: 0,
    observaciones: '',
});

function abrirModalCierre() {
    formCierre.monto_cierre_declarado = props.sesionActiva?.efectivo_en_caja || 0;
    formCierre.observaciones = '';
    modalCierreAbierto.value = true;
}

function ejecutarCierre() {
    if (!props.sesionActiva) return;
    formCierre.post(`/cajero/cierre/${props.sesionActiva.id}`, {
        preserveScroll: true,
        onSuccess: () => { modalCierreAbierto.value = false; },
    });
}

// Modal Cobro / Pago
const modalPagoAbierto = ref(false);
const pedidoSeleccionado = ref(null);
const metodoSeleccionado = ref('efectivo');
const propinaPorcentaje = ref(0);
const montoRecibidoInput = ref(0);
const referenciaTransaccionInput = ref('');
const notaAdicionalInput = ref('');
const errorReferenciaPago = ref('');

const formPago = useForm({
    propina_valor: 0,
    pagos: [],
});

function abrirModalPago(pedido) {
    pedidoSeleccionado.value = pedido;
    propinaPorcentaje.value = 0;
    formPago.propina_valor = 0;
    metodoSeleccionado.value = 'efectivo';
    referenciaTransaccionInput.value = '';
    notaAdicionalInput.value = '';
    errorReferenciaPago.value = '';
    const totalConsumo = pedido.subtotal + pedido.impuestos;
    montoRecibidoInput.value = totalConsumo;
    modalPagoAbierto.value = true;
}

function establecerPropina(porcentaje) {
    propinaPorcentaje.value = porcentaje;
    if (!pedidoSeleccionado.value) return;
    const base = pedidoSeleccionado.value.subtotal;
    formPago.propina_valor = Math.round((base * porcentaje) / 100);
}

function calcularTotalCobro() {
    if (!pedidoSeleccionado.value) return 0;
    return (pedidoSeleccionado.value.subtotal + pedidoSeleccionado.value.impuestos) + Math.max(0, Number(formPago.propina_valor || 0));
}

function ejecutarCobro() {
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

    formPago.post(`/cajero/pagos/${pedidoSeleccionado.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { modalPagoAbierto.value = false; },
    });
}

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
        `/cajero/pedidos/${pedidoDeItemParaAnular.value.id}/items/${itemParaAnular.value.id}/anular`,
        { motivo: motivoAnulacionItem.value },
        {
            preserveScroll: true,
            onSuccess: () => { modalAnularItemAbierto.value = false; },
        }
    );
}

// Búsqueda de facturas
const busquedaFactura = ref('');
const facturasResultados = ref([]);

async function buscarFacturas() {
    if (busquedaFactura.value.length < 2) {
        facturasResultados.value = [];
        return;
    }
    try {
        const res = await fetch(`/cajero/buscar-factura?q=${encodeURIComponent(busquedaFactura.value)}`);
        if (res.ok) {
            facturasResultados.value = await res.json();
        }
    } catch {
        facturasResultados.value = [];
    }
}

function reenviarFactura(comprobanteId) {
    router.post(`/cajero/comprobantes/${comprobanteId}/reenviar`, {}, {
        preserveScroll: true,
    });
}

function logout() {
    if (typeof window !== 'undefined') {
        sessionStorage.removeItem('sigr_auth_active');
        sessionStorage.removeItem('sigr_dashboard');
    }
    router.post('/logout');
}
</script>

