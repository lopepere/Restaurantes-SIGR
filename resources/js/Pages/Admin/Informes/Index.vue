<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado y Filtros -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>📊</span>
                        <span>Tablero de Informes Ejecutivos</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Métricas de ventas, compras, recaudación de propinas y arqueo en tiempo real (RF-INF, CA-38..40).
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Selector de Sucursal -->
                    <div v-if="sucursales && sucursales.length > 1">
                        <select
                            v-model="sucursalSeleccionada"
                            @change="cambiarFiltros"
                            class="rounded-xl border border-stone-300 bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 shadow-xs focus:ring-1 focus:ring-orange-500"
                        >
                            <option v-for="s in sucursales" :key="s.id" :value="s.id">
                                🏢 {{ s.nombre }}
                            </option>
                        </select>
                    </div>

                    <!-- Selector de Período -->
                    <div class="inline-flex rounded-xl p-1 bg-stone-100 border border-stone-200 text-xs font-semibold">
                        <button
                            type="button"
                            @click="seleccionarPeriodo('hoy')"
                            :class="[
                                'px-3 py-1 rounded-lg transition',
                                periodoActivo === 'hoy'
                                    ? 'bg-white text-orange-600 shadow-xs'
                                    : 'text-stone-600 hover:text-stone-900'
                            ]"
                        >
                            Hoy
                        </button>
                        <button
                            type="button"
                            @click="seleccionarPeriodo('semana')"
                            :class="[
                                'px-3 py-1 rounded-lg transition',
                                periodoActivo === 'semana'
                                    ? 'bg-white text-orange-600 shadow-xs'
                                    : 'text-stone-600 hover:text-stone-900'
                            ]"
                        >
                            Últimos 7 días
                        </button>
                        <button
                            type="button"
                            @click="seleccionarPeriodo('mes')"
                            :class="[
                                'px-3 py-1 rounded-lg transition',
                                periodoActivo === 'mes'
                                    ? 'bg-white text-orange-600 shadow-xs'
                                    : 'text-stone-600 hover:text-stone-900'
                            ]"
                        >
                            Últimos 30 días
                        </button>
                    </div>

                    <!-- Botón Imprimir / PDF -->
                    <button
                        type="button"
                        @click="imprimirInforme"
                        class="px-3 py-1.5 rounded-xl border border-stone-300 bg-white hover:bg-stone-50 text-stone-700 text-xs font-semibold shadow-xs transition"
                        title="Imprimir informe ejecutivo"
                    >
                        🖨️ Imprimir
                    </button>
                </div>
            </div>

            <!-- Tarjetas de Métricas Clave (KPIs) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Ventas -->
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-stone-500">Ventas Totales</span>
                        <span class="p-2 bg-emerald-50 text-emerald-600 rounded-xl text-base">💰</span>
                    </div>
                    <p class="text-2xl font-black text-stone-900 mt-2 font-mono">
                        ${{ formatearDinero(metricas.totalVentas) }}
                    </p>
                    <div class="mt-2 text-[11px] text-stone-400 flex items-center justify-between">
                        <span>Subtotal: ${{ formatearDinero(metricas.totalSubtotal) }}</span>
                        <span>Imp: ${{ formatearDinero(metricas.totalImpuestos) }}</span>
                    </div>
                </div>

                <!-- Total Propinas (SC-02) -->
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-stone-500">Propinas Voluntarias</span>
                        <span class="p-2 bg-blue-50 text-blue-600 rounded-xl text-base">🤝</span>
                    </div>
                    <p class="text-2xl font-black text-blue-700 mt-2 font-mono">
                        ${{ formatearDinero(metricas.totalPropinas) }}
                    </p>
                    <p class="mt-2 text-[11px] text-stone-400">
                        Destinadas a distribución entre personal de servicio
                    </p>
                </div>

                <!-- Pedidos Atendidos -->
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-stone-500">Pedidos Pagados</span>
                        <span class="p-2 bg-orange-50 text-orange-600 rounded-xl text-base">🧾</span>
                    </div>
                    <p class="text-2xl font-black text-stone-900 mt-2">
                        {{ metricas.totalPedidos }}
                    </p>
                    <p class="mt-2 text-[11px] text-stone-400">
                        Ticket promedio: ${{ formatearDinero(ticketPromedio) }}
                    </p>
                </div>

                <!-- Compras a Proveedores -->
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-stone-500">Compras Registradas</span>
                        <span class="p-2 bg-purple-50 text-purple-600 rounded-xl text-base">🚛</span>
                    </div>
                    <p class="text-2xl font-black text-purple-800 mt-2 font-mono">
                        ${{ formatearDinero(metricas.totalCompras) }}
                    </p>
                    <p class="mt-2 text-[11px] text-stone-400">
                        Inversión en insumos y catálogo
                    </p>
                </div>
            </div>

            <!-- Fila Central: Modalidades y Top Platos -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Ventas por Modalidad (5 columnas) -->
                <div class="lg:col-span-5 bg-white p-6 rounded-2xl border border-stone-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-stone-900 flex items-center space-x-2">
                        <span>🍽️</span>
                        <span>Ventas por Modalidad</span>
                    </h2>

                    <div v-if="ventasPorModalidad.length > 0" class="space-y-4">
                        <div v-for="item in ventasPorModalidad" :key="item.tipo" class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-stone-700 capitalize">{{ item.etiqueta }}</span>
                                <span class="font-mono font-bold text-stone-900">
                                    ${{ formatearDinero(item.total) }} ({{ item.cantidad }} pedidos)
                                </span>
                            </div>
                            <div class="w-full h-2.5 bg-stone-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-orange-500 rounded-full transition-all duration-500"
                                    :style="{ width: calcularPorcentaje(item.total, metricas.totalVentas) + '%' }"
                                ></div>
                            </div>
                            <div class="text-[10px] text-stone-400 text-right">
                                {{ calcularPorcentaje(item.total, metricas.totalVentas) }}% del volumen total
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-8 text-center text-xs text-stone-400">
                        Sin pedidos pagados en el período seleccionado.
                    </div>
                </div>

                <!-- Top 5 Platos más Vendidos (7 columnas) -->
                <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-stone-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-stone-900 flex items-center space-x-2">
                        <span>🏆</span>
                        <span>Top 5 Platos y Bebidas Más Vendidos</span>
                    </h2>

                    <div v-if="topProductos.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="text-stone-400 border-b border-stone-100 text-[11px] font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="py-2">#</th>
                                    <th class="py-2">Producto</th>
                                    <th class="py-2 text-right">Unidades</th>
                                    <th class="py-2 text-right">Facturación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                <tr v-for="(p, index) in topProductos" :key="index" class="hover:bg-stone-50/70">
                                    <td class="py-2.5 font-bold text-stone-400 w-8">
                                        <span :class="[
                                            'inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-black',
                                            index === 0 ? 'bg-amber-100 text-amber-800' :
                                            index === 1 ? 'bg-stone-200 text-stone-700' :
                                            index === 2 ? 'bg-orange-100 text-orange-800' : 'bg-stone-100 text-stone-600'
                                        ]">
                                            {{ index + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 font-bold text-stone-900">{{ p.nombre }}</td>
                                    <td class="py-2.5 text-right font-mono text-stone-600 font-semibold">{{ p.cantidad }}</td>
                                    <td class="py-2.5 text-right font-mono font-bold text-stone-900">
                                        ${{ formatearDinero(p.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-8 text-center text-xs text-stone-400">
                        No hay ventas registradas en el período seleccionado.
                    </div>
                </div>
            </div>

            <!-- Fila Inferior: Balance y Arqueo de Caja -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Margen Operativo Bruto -->
                <div class="bg-white p-6 rounded-2xl border border-stone-200 shadow-xs space-y-3">
                    <h2 class="text-sm font-bold text-stone-900 flex items-center space-x-2">
                        <span>⚖️</span>
                        <span>Balance Comercial Operativo</span>
                    </h2>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-stone-100">
                            <span class="text-stone-600">Ingresos brutos por ventas</span>
                            <span class="font-mono font-bold text-emerald-600">+ ${{ formatearDinero(metricas.totalVentas) }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-stone-100">
                            <span class="text-stone-600">Egresos por compras registradas</span>
                            <span class="font-mono font-bold text-rose-600">- ${{ formatearDinero(metricas.totalCompras) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 text-sm font-black">
                            <span class="text-stone-900">Margen bruto estimado</span>
                            <span :class="['font-mono', margenOperativo >= 0 ? 'text-emerald-700' : 'text-rose-700']">
                                ${{ formatearDinero(margenOperativo) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Arqueo y Descuadre en Cierres de Caja -->
                <div class="bg-white p-6 rounded-2xl border border-stone-200 shadow-xs space-y-3">
                    <h2 class="text-sm font-bold text-stone-900 flex items-center space-x-2">
                        <span>🪙</span>
                        <span>Auditoría de Cajas (Descuadre Acumulado)</span>
                    </h2>
                    <p class="text-xs text-stone-500">
                        Suma de diferencias entre el dinero declarado por los cajeros y el calculado por el sistema en los arqueos del período.
                    </p>
                    <div class="p-4 rounded-xl border flex items-center justify-between" :class="[
                        metricas.descuadreCajas === 0 ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900' :
                        metricas.descuadreCajas > 0 ? 'bg-blue-50/70 border-blue-200 text-blue-900' :
                        'bg-rose-50/70 border-rose-200 text-rose-900'
                    ]">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider block">
                                {{ metricas.descuadreCajas === 0 ? 'Cajas Cuadradas' : (metricas.descuadreCajas > 0 ? 'Sobrante en Arqueos' : 'Faltante en Arqueos') }}
                            </span>
                            <span class="text-xs text-stone-600">
                                {{ metricas.descuadreCajas === 0 ? 'Diferencia neta $0' : 'Diferencia neta detectada' }}
                            </span>
                        </div>
                        <span class="text-xl font-mono font-black">
                            {{ metricas.descuadreCajas > 0 ? '+' : '' }}${{ formatearDinero(metricas.descuadreCajas) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    metricas: Object,
    ventasPorModalidad: Array,
    topProductos: Array,
    periodo: String,
    sucursales: Array,
    sucursal_id: Number,
});

const sucursalSeleccionada = ref(props.sucursal_id);
const periodoActivo = ref(props.periodo || 'hoy');

const ticketPromedio = computed(() => {
    if (!props.metricas?.totalPedidos || props.metricas.totalPedidos === 0) return 0;
    return Math.round(props.metricas.totalVentas / props.metricas.totalPedidos);
});

const margenOperativo = computed(() => {
    return (props.metricas?.totalVentas || 0) - (props.metricas?.totalCompras || 0);
});

function formatearDinero(valor) {
    return Number(valor || 0).toLocaleString('es-CO');
}

function calcularPorcentaje(parcial, total) {
    if (!total || total === 0) return 0;
    return Math.round((parcial / total) * 100);
}

function seleccionarPeriodo(p) {
    periodoActivo.value = p;
    cambiarFiltros();
}

function cambiarFiltros() {
    router.get(route('admin.informes.index'), {
        sucursal_id: sucursalSeleccionada.value,
        periodo: periodoActivo.value,
    }, { preserveState: true });
}

function imprimirInforme() {
    window.print();
}
</script>
