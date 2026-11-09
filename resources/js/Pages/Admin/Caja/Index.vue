<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>💵</span>
                        <span>Caja y Turnos</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Control de turnos, arqueo con cálculo de diferencias, cobro de pedidos y propina opcional (RF-CAJ-01..08, CA-29, CA-30).
                    </p>
                </div>

                <!-- Acciones Superiores -->
                <div class="flex items-center space-x-3">
                    <div v-if="sucursales && sucursales.length > 1">
                        <select
                            v-model="sucursalSeleccionada"
                            @change="cambiarSucursal"
                            class="rounded-xl border border-stone-300 bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 shadow-xs focus:ring-1 focus:ring-orange-500"
                        >
                            <option v-for="s in sucursales" :key="s.id" :value="s.id">
                                🏢 {{ s.nombre }}
                            </option>
                        </select>
                    </div>

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
            <div v-if="sesionActiva" class="bg-white rounded-3xl border border-stone-200 shadow-sm p-6 space-y-4">
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
            <div v-else class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center text-xs text-amber-900">
                <span class="text-3xl block mb-2">🔒</span>
                <h3 class="font-bold text-sm">No hay un turno de caja abierto en esta sucursal</h3>
                <p class="mt-1 text-stone-600">
                    Abre un turno con el saldo base inicial para poder registrar cobros y pagos de comandas.
                </p>
                <button
                    type="button"
                    @click="abrirModalApertura"
                    class="mt-3 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-bold transition shadow-xs"
                >
                    + Abrir Turno Ahora
                </button>
            </div>

            <!-- Grilla: Pedidos por Cobrar y Pagos Recientes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Columna Izquierda: Pedidos Pendientes de Pago -->
                <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                    <div class="p-4 border-b border-stone-100 flex items-center justify-between">
                        <h3 class="font-bold text-sm text-stone-900">Comandas Pendientes de Pago</h3>
                        <span class="text-xs text-stone-400 font-mono">{{ pedidosPorCobrar.length }} pendiente(s)</span>
                    </div>

                    <div class="divide-y divide-stone-100 max-h-96 overflow-y-auto">
                        <div
                            v-for="ped in pedidosPorCobrar"
                            :key="ped.id"
                            class="p-4 flex items-center justify-between hover:bg-stone-50/70 transition"
                        >
                            <div>
                                <span class="font-mono font-bold text-xs text-orange-600 block">
                                    {{ ped.numero }}
                                </span>
                                <span class="text-xs font-semibold text-stone-800">
                                    {{ ped.mesa ? `🪑 ${ped.mesa}` : ped.tipo }} — {{ ped.cliente }}
                                </span>
                                <span class="text-[11px] text-stone-500 block font-mono">
                                    Subtotal: ${{ ped.subtotal.toLocaleString() }} + Imp: ${{ ped.impuestos.toLocaleString() }}
                                </span>
                            </div>

                            <div class="text-right space-y-1.5">
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

                        <div v-if="pedidosPorCobrar.length === 0" class="p-8 text-center text-stone-400 text-xs">
                            No hay pedidos pendientes de cobro en esta sucursal.
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Pagos Registrados en la Sesión -->
                <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                    <div class="p-4 border-b border-stone-100">
                        <h3 class="font-bold text-sm text-stone-900">Cobros Registrados en el Turno</h3>
                    </div>

                    <div class="divide-y divide-stone-100 max-h-96 overflow-y-auto">
                        <div
                            v-for="pago in pagosSesion"
                            :key="pago.id"
                            class="p-3.5 flex items-center justify-between text-xs hover:bg-stone-50/50"
                        >
                            <div>
                                <span class="font-mono font-bold text-stone-800">#{{ pago.pedido_numero }}</span>
                                <span class="text-stone-400 text-[11px] ml-2 font-mono">{{ pago.hora }}</span>
                                <span class="block text-stone-500 text-[11px]">{{ pago.metodo }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold font-mono text-stone-900 block">${{ pago.monto.toLocaleString() }}</span>
                                <span v-if="pago.cambio > 0" class="text-[10px] text-stone-400 font-mono">
                                    Devuelta: ${{ pago.cambio.toLocaleString() }}
                                </span>
                            </div>
                        </div>

                        <div v-if="pagosSesion.length === 0" class="p-8 text-center text-stone-400 text-xs">
                            Aún no se han registrado cobros en este turno.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de Cierres Anteriores -->
            <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-stone-100">
                    <h3 class="font-bold text-sm text-stone-900">Historial de Turnos y Arqueos Cerrados</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 uppercase tracking-wider font-semibold">
                                <th class="p-3">Caja</th>
                                <th class="p-3">Cajero</th>
                                <th class="p-3">Apertura</th>
                                <th class="p-3">Cierre</th>
                                <th class="p-3">Base</th>
                                <th class="p-3">Efectivo Calculado</th>
                                <th class="p-3">Efectivo Declarado</th>
                                <th class="p-3">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="cierre in cierresRecientes" :key="cierre.id" class="hover:bg-stone-50/50">
                                <td class="p-3 font-semibold text-stone-800">{{ cierre.caja }}</td>
                                <td class="p-3 text-stone-600">{{ cierre.cajero }}</td>
                                <td class="p-3 font-mono text-stone-500">{{ cierre.apertura }}</td>
                                <td class="p-3 font-mono text-stone-500">{{ cierre.cierre }}</td>
                                <td class="p-3 font-mono text-stone-700">${{ cierre.monto_apertura.toLocaleString() }}</td>
                                <td class="p-3 font-mono text-stone-700">${{ cierre.monto_calculado.toLocaleString() }}</td>
                                <td class="p-3 font-mono font-bold text-stone-900">${{ cierre.monto_declarado.toLocaleString() }}</td>
                                <td class="p-3 font-mono font-bold">
                                    <span :class="cierre.diferencia === 0 ? 'text-emerald-600' : (cierre.diferencia > 0 ? 'text-sky-600' : 'text-rose-600')">
                                        {{ cierre.diferencia === 0 ? '$0 (Cuadrada)' : (cierre.diferencia > 0 ? `+$${cierre.diferencia.toLocaleString()} (Sobrante)` : `-$${Math.abs(cierre.diferencia).toLocaleString()} (Faltante)`) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Apertura de Caja (RF-CAJ-02) -->
        <div v-if="modalAperturaAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">Apertura de Turno de Caja</h3>
                    <button @click="modalAperturaAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarApertura" class="space-y-4 text-xs">
                    <div>
                        <label for="caja_apertura" class="block font-semibold text-stone-700 mb-1">Caja a Abrir *</label>
                        <select id="caja_apertura" v-model="formApertura.caja_id" class="w-full rounded-xl border border-stone-300 p-2 text-xs" required>
                            <option v-for="c in cajas" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="monto_apertura" class="block font-semibold text-stone-700 mb-1">Monto Base Inicial en Efectivo ($) *</label>
                        <input id="monto_apertura" type="number" step="1000" min="0" v-model.number="formApertura.monto_apertura" class="w-full rounded-xl border border-stone-300 p-2.5 text-xs font-mono" required />
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalAperturaAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">Cancelar</button>
                        <button type="submit" :disabled="formApertura.processing" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold transition">
                            Abrir Turno
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Registrar Pago / Cobro (RF-CAJ-04, CA-30) -->
        <div v-if="modalPagoAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Cobro de Pedido {{ pedidoSeleccionado?.numero }}
                    </h3>
                    <button @click="modalPagoAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <form @submit.prevent="ejecutarCobro" class="space-y-4 text-xs">
                    <!-- Propina Opcional (SC-02) -->
                    <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-amber-900">Propina Voluntaria (SC-02):</span>
                            <span class="font-bold text-amber-800 font-mono">${{ (formPago.propina_valor || 0).toLocaleString() }}</span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="establecerPropina(0)" :class="['px-2.5 py-1 rounded-lg text-[11px] font-bold border transition', formPago.propina_valor === 0 ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-stone-700 border-stone-200']">
                                Sin propina (0%)
                            </button>
                            <button type="button" @click="establecerPropina(0.05)" :class="['px-2.5 py-1 rounded-lg text-[11px] font-bold border transition', formPago.propina_valor === Math.round(pedidoSeleccionado?.subtotal * 0.05) ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-stone-700 border-stone-200']">
                                5%
                            </button>
                            <button type="button" @click="establecerPropina(0.10)" :class="['px-2.5 py-1 rounded-lg text-[11px] font-bold border transition', formPago.propina_valor === Math.round(pedidoSeleccionado?.subtotal * 0.10) ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-stone-700 border-stone-200']">
                                10%
                            </button>
                        </div>
                    </div>

                    <!-- Total a Cobrar -->
                    <div class="p-3 bg-stone-100 rounded-xl flex items-center justify-between">
                        <span class="font-bold text-stone-700">Total con propina:</span>
                        <span class="text-xl font-black font-mono text-orange-600">
                            ${{ calcularTotalCobro().toLocaleString() }}
                        </span>
                    </div>

                    <!-- Método de Pago -->
                    <div class="space-y-2">
                        <label class="block font-semibold text-stone-700">Método de Pago *</label>
                        <select v-model="metodoSeleccionado" class="w-full rounded-xl border border-stone-300 p-2 text-xs">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta_debito">Tarjeta Débito</option>
                            <option value="tarjeta_credito">Tarjeta Crédito</option>
                            <option value="transferencia">Transferencia (Nequi / Daviplata / Bancolombia)</option>
                        </select>
                    </div>

                    <!-- Si es Efectivo, Billete Recibido y Devuelta -->
                    <div v-if="metodoSeleccionado === 'efectivo'" class="p-3 bg-stone-50 rounded-xl border border-stone-200 space-y-2">
                        <label for="billete_recibido" class="block font-semibold text-stone-700">Billete / Efectivo Recibido ($)</label>
                        <input
                            id="billete_recibido"
                            type="number"
                            step="1000"
                            :min="calcularTotalCobro()"
                            v-model.number="montoRecibidoInput"
                            class="w-full rounded-xl border border-stone-300 p-2 text-xs font-mono"
                        />
                        <div v-if="montoRecibidoInput >= calcularTotalCobro()" class="flex justify-between items-center pt-1 text-emerald-700 font-bold">
                            <span>Devuelta / Cambio a entregar:</span>
                            <span class="font-mono text-base">${{ (montoRecibidoInput - calcularTotalCobro()).toLocaleString() }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalPagoAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">Cancelar</button>
                        <button type="submit" :disabled="formPago.processing" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition">
                            {{ formPago.processing ? 'Registrando...' : 'Confirmar Cobro y Emitir Comprobante' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Cierre de Turno y Arqueo (RF-CAJ-05) -->
        <div v-if="modalCierreAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">Arqueo y Cierre de Turno</h3>
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
    sucursales: { type: Array, default: () => [] },
    sucursal_id: { type: Number, required: true },
});

const sucursalSeleccionada = ref(props.sucursal_id);

const cambiarSucursal = () => {
    router.get('/admin/caja', { sucursal_id: sucursalSeleccionada.value });
};

// Modal Apertura
const modalAperturaAbierto = ref(false);
const formApertura = useForm({
    caja_id: props.cajas[0]?.id || null,
    monto_apertura: 100000,
});

const abrirModalApertura = () => {
    formApertura.caja_id = props.cajas[0]?.id || null;
    formApertura.monto_apertura = 100000;
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

const formPago = useForm({
    propina_valor: 0,
    pagos: [],
});

const abrirModalPago = (pedido) => {
    pedidoSeleccionado.value = pedido;
    formPago.propina_valor = 0;
    metodoSeleccionado.value = 'efectivo';
    montoRecibidoInput.value = (pedido.subtotal + pedido.impuestos);
    modalPagoAbierto.value = true;
};

const establecerPropina = (porcentaje) => {
    if (!pedidoSeleccionado.value) return;
    const base = pedidoSeleccionado.value.subtotal;
    formPago.propina_valor = Math.round(base * porcentaje);
    montoRecibidoInput.value = calcularTotalCobro();
};

const calcularTotalCobro = () => {
    if (!pedidoSeleccionado.value) return 0;
    return (pedidoSeleccionado.value.subtotal + pedidoSeleccionado.value.impuestos + (formPago.propina_valor || 0));
};

const ejecutarCobro = () => {
    if (!pedidoSeleccionado.value) return;

    const total = calcularTotalCobro();
    formPago.pagos = [
        {
            metodo_pago: metodoSeleccionado.value,
            monto: total,
            monto_recibido: metodoSeleccionado.value === 'efectivo' ? montoRecibidoInput.value : total,
        },
    ];

    formPago.post(`/admin/caja/pagos/${pedidoSeleccionado.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { modalPagoAbierto.value = false; },
    });
};

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

