<template>
    <component :is="layoutActual">
        <div class="p-6 space-y-6">
            <!-- Encabezado y Filtros -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>📊</span>
                        <span>Tablero de Informes Ejecutivos</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Métricas de ventas, compras, recaudación de propinas y arqueo en tiempo real (RF-INF, CA-38..40).
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Selector de Sucursal -->
                    <div v-if="sucursales && sucursales.length > 1">
                        <select
                            v-model="sucursalSeleccionada"
                            @change="cambiarFiltros"
                            class="rounded-xl border border-stone-300 bg-white px-3 py-2 text-xs font-semibold text-stone-700 shadow-xs focus:ring-1 focus:ring-orange-500"
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
                                'px-3 py-1.5 rounded-lg transition',
                                periodoActivo === 'hoy'
                                    ? 'bg-white text-orange-600 shadow-xs font-bold'
                                    : 'text-stone-600 hover:text-stone-900'
                            ]"
                        >
                            Hoy
                        </button>
                        <button
                            type="button"
                            @click="seleccionarPeriodo('semana')"
                            :class="[
                                'px-3 py-1.5 rounded-lg transition',
                                periodoActivo === 'semana'
                                    ? 'bg-white text-orange-600 shadow-xs font-bold'
                                    : 'text-stone-600 hover:text-stone-900'
                            ]"
                        >
                            7 días
                        </button>
                        <button
                            type="button"
                            @click="seleccionarPeriodo('mes')"
                            :class="[
                                'px-3 py-1.5 rounded-lg transition',
                                periodoActivo === 'mes'
                                    ? 'bg-white text-orange-600 shadow-xs font-bold'
                                    : 'text-stone-600 hover:text-stone-900'
                            ]"
                        >
                            30 días
                        </button>
                        <button
                            type="button"
                            @click="seleccionarPeriodo('personalizado')"
                            :class="[
                                'px-3 py-1.5 rounded-lg transition',
                                periodoActivo === 'personalizado'
                                    ? 'bg-white text-orange-600 shadow-xs font-bold'
                                    : 'text-stone-600 hover:text-stone-900'
                            ]"
                        >
                            Rango 📅
                        </button>
                    </div>

                    <!-- Botones de Exportación Real (Item 2) -->
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="exportar('excel')"
                            class="px-3.5 py-2 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold shadow-xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer"
                            title="Descargar informe consolidado en formato Excel / CSV"
                        >
                            <span>📊</span>
                            <span>Excel</span>
                        </button>

                        <button
                            type="button"
                            @click="exportar('pdf')"
                            class="px-3.5 py-2 rounded-xl border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold shadow-xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer"
                            title="Descargar informe oficial en formato PDF"
                        >
                            <span>📄</span>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Rango de Fechas Personalizado -->
            <div
                v-if="periodoActivo === 'personalizado'"
                class="bg-white rounded-2xl border border-stone-200 p-4 flex flex-wrap items-center gap-4 text-xs shadow-xs"
            >
                <div class="flex items-center gap-2">
                    <label class="font-bold text-stone-700">Desde:</label>
                    <input
                        type="date"
                        v-model="fechaInicioInput"
                        class="border border-stone-300 rounded-lg px-2.5 py-1.5 text-stone-900 font-mono"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label class="font-bold text-stone-700">Hasta:</label>
                    <input
                        type="date"
                        v-model="fechaFinInput"
                        class="border border-stone-300 rounded-lg px-2.5 py-1.5 text-stone-900 font-mono"
                    />
                </div>
                <button
                    type="button"
                    @click="cambiarFiltros"
                    class="px-4 py-1.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-lg transition"
                >
                    Aplicar Rango
                </button>
            </div>

            <!-- Resumen de Métricas Principales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Ventas Totales -->
                <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-xs flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl font-bold">
                        💰
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Ventas Totales</span>
                        <div class="text-xl font-black text-stone-900 font-mono">
                            ${{ formatearDinero(metricas.totalVentas) }}
                        </div>
                        <span class="text-[11px] text-stone-500 font-medium">
                            Subtotal: ${{ formatearDinero(metricas.totalSubtotal) }}
                        </span>
                    </div>
                </div>

                <!-- Impuestos INC / IVA -->
                <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-xs flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl font-bold">
                        ⚖️
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Impuestos Recaudados</span>
                        <div class="text-xl font-black text-amber-700 font-mono">
                            ${{ formatearDinero(metricas.totalImpuestos) }}
                        </div>
                        <span class="text-[11px] text-stone-500 font-medium">
                            Tarifa INC 8% / IVA 19%
                        </span>
                    </div>
                </div>

                <!-- Propinas Voluntarias -->
                <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-xs flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl font-bold">
                        🤝
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Propinas Recaudadas</span>
                        <div class="text-xl font-black text-blue-700 font-mono">
                            ${{ formatearDinero(metricas.totalPropinas) }}
                        </div>
                        <span class="text-[11px] text-stone-500 font-medium">
                            Fondo acumulado de servicio
                        </span>
                    </div>
                </div>

                <!-- Total de Pedidos y Ticket Promedio -->
                <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-xs flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold">
                        🧾
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Comandas Pagadas</span>
                        <div class="text-xl font-black text-emerald-700 font-mono">
                            {{ metricas.totalPedidos }} órdenes
                        </div>
                        <span class="text-[11px] text-stone-500 font-medium">
                            Promedio: ${{ formatearDinero(ticketPromedio) }} / ticket
                        </span>
                    </div>
                </div>
            </div>

            <!-- Segunda Fila de Métricas Operativas -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Compras a Proveedores -->
                <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Compras Registradas</span>
                        <span class="text-base">🛒</span>
                    </div>
                    <div class="text-xl font-black text-stone-900 font-mono mt-2">
                        ${{ formatearDinero(metricas.totalCompras) }}
                    </div>
                    <p class="text-[11px] text-stone-500 mt-1">Costos de inventario ingresados en el período.</p>
                </div>

                <!-- Descuadre de Cajas -->
                <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Descuadre en Cierres</span>
                        <span class="text-base">💵</span>
                    </div>
                    <div
                        :class="[
                            'text-xl font-black font-mono mt-2',
                            metricas.descuadreCajas < 0 ? 'text-rose-600' : 'text-emerald-600'
                        ]"
                    >
                        ${{ formatearDinero(metricas.descuadreCajas) }}
                    </div>
                    <p class="text-[11px] text-stone-500 mt-1">Diferencia neta física contra cálculo de arqueos.</p>
                </div>

                <!-- Margen Bruto Operativo -->
                <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Margen Operativo Bruto</span>
                        <span class="text-base">📈</span>
                    </div>
                    <div class="text-xl font-black text-stone-900 font-mono mt-2">
                        ${{ formatearDinero(margenOperativo) }}
                    </div>
                    <p class="text-[11px] text-stone-500 mt-1">Ventas netas menos compras a proveedores.</p>
                </div>
            </div>

            <!-- Tablas de Detalle: Modalidades y Top Platos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Ventas por Modalidad -->
                <div class="bg-white rounded-3xl border border-stone-200 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-stone-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-stone-900 flex items-center space-x-2">
                            <span>🛵</span>
                            <span>Ventas por Modalidad</span>
                        </h2>
                        <span class="text-[11px] text-stone-400 font-medium">Distribución de ingresos</span>
                    </div>

                    <div class="p-5 space-y-4">
                        <div v-if="ventasPorModalidad.length === 0" class="text-center py-6 text-stone-400 text-xs">
                            No hay ventas registradas en el período.
                        </div>

                        <div v-for="mod in ventasPorModalidad" :key="mod.tipo" class="space-y-1.5">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-stone-800">{{ mod.etiqueta }} ({{ mod.cantidad }} pedidos)</span>
                                <span class="font-mono text-stone-900">${{ formatearDinero(mod.total) }}</span>
                            </div>
                            <div class="w-full bg-stone-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="bg-orange-500 h-2 rounded-full transition-all duration-500"
                                    :style="{ width: `${calcularPorcentaje(mod.total, metricas.totalVentas)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Platos Más Vendidos -->
                <div class="bg-white rounded-3xl border border-stone-200 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-stone-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-stone-900 flex items-center space-x-2">
                            <span>🏆</span>
                            <span>Top Platos Más Vendidos</span>
                        </h2>
                        <span class="text-[11px] text-stone-400 font-medium">Volumen de unidades</span>
                    </div>

                    <div class="divide-y divide-stone-100 text-xs">
                        <div v-if="topProductos.length === 0" class="text-center py-8 text-stone-400">
                            No hay productos registrados en el período.
                        </div>

                        <div
                            v-for="(prod, idx) in topProductos"
                            :key="prod.nombre"
                            class="p-4 flex items-center justify-between hover:bg-stone-50 transition"
                        >
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 rounded-full bg-stone-100 text-stone-600 font-bold flex items-center justify-center text-[10px]">
                                    {{ idx + 1 }}
                                </span>
                                <div>
                                    <h3 class="font-bold text-stone-900">{{ prod.nombre }}</h3>
                                    <span class="text-stone-400 text-[11px]">{{ prod.cantidad }} unidades preparadas</span>
                                </div>
                            </div>
                            <span class="font-mono font-bold text-stone-900">
                                ${{ formatearDinero(prod.total) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const page = usePage();
const esSuperAdmin = computed(() => page.props.auth?.user?.roles?.includes('super_admin'));
const layoutActual = computed(() => esSuperAdmin.value ? SuperAdminLayout : AdminLayout);

const props = defineProps({
    metricas: Object,
    ventasPorModalidad: Array,
    topProductos: Array,
    periodo: String,
    fecha_inicio: String,
    fecha_fin: String,
    sucursales: Array,
    sucursal_id: Number,
});

const sucursalSeleccionada = ref(props.sucursal_id);
const periodoActivo = ref(props.periodo || 'hoy');
const fechaInicioInput = ref(props.fecha_inicio || '');
const fechaFinInput = ref(props.fecha_fin || '');

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
    if (p !== 'personalizado') {
        cambiarFiltros();
    }
}

function cambiarFiltros() {
    const params = {
        sucursal_id: sucursalSeleccionada.value,
        periodo: periodoActivo.value,
    };

    if (periodoActivo.value === 'personalizado') {
        params.fecha_inicio = fechaInicioInput.value;
        params.fecha_fin = fechaFinInput.value;
    }

    const ruta = esSuperAdmin.value ? '/superadmin/informes' : '/admin/informes';
    router.get(ruta, params, { preserveState: true });
}

function exportar(formato) {
    const params = new URLSearchParams({
        sucursal_id: String(sucursalSeleccionada.value),
        periodo: periodoActivo.value,
    });

    if (periodoActivo.value === 'personalizado') {
        if (fechaInicioInput.value) params.append('fecha_inicio', fechaInicioInput.value);
        if (fechaFinInput.value) params.append('fecha_fin', fechaFinInput.value);
    }

    const baseRuta = esSuperAdmin.value ? '/superadmin/informes/exportar' : '/admin/informes/exportar';
    window.location.href = `${baseRuta}/${formato}?${params.toString()}`;
}
</script>
