<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>📦</span>
                        <span>Inventario y Kárdex</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Control de existencias, ajustes manuales justificados y kárdex inmutable (RF-INV-01..09, CA-27, CA-28).
                    </p>
                </div>

                <!-- Selector de Sucursal y Cambio de Modo -->
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
                        v-if="esSuperAdmin"
                        type="button"
                        @click="abrirModalModo"
                        class="px-3.5 py-2 rounded-xl border border-stone-300 bg-white hover:bg-stone-50 text-stone-700 text-xs font-semibold shadow-xs transition"
                        title="Modo de inventario (solo superadmin)"
                    >
                        ⚙️ Modo: {{ modoInventario === 'autonomo' ? 'Autónomo' : 'Dependiente' }}
                    </button>
                </div>
            </div>

            <!-- Resumen de Métricas -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs text-stone-500 font-medium">Productos en Catálogo</span>
                        <p class="text-2xl font-black text-stone-900">{{ items.total ?? items.data?.length ?? 0 }}</p>
                    </div>
                    <span class="p-3 bg-stone-100 rounded-xl text-xl">🍽️</span>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs text-stone-500 font-medium">Alertas de Stock Mínimo</span>
                        <p :class="['text-2xl font-black', alertasCount > 0 ? 'text-rose-600 animate-pulse' : 'text-emerald-600']">
                            {{ alertasCount }}
                        </p>
                    </div>
                    <span :class="['p-3 rounded-xl text-xl', alertasCount > 0 ? 'bg-rose-100' : 'bg-emerald-100']">
                        {{ alertasCount > 0 ? '⚠️' : '✓' }}
                    </span>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs text-stone-500 font-medium">Régimen Operativo</span>
                        <p class="text-xs font-bold text-orange-600 mt-1 capitalize">{{ modoInventarioEtiqueta }}</p>
                    </div>
                    <span class="p-3 bg-orange-50 rounded-xl text-xl">🔄</span>
                </div>
            </div>

            <!-- Filtros de Búsqueda -->
            <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-xs flex flex-wrap gap-3 items-center justify-between">
                <div class="flex flex-wrap gap-2 items-center flex-1">
                    <input
                        type="text"
                        v-model="filtroBusqueda"
                        @keyup.enter="aplicarFiltros"
                        placeholder="Buscar producto por nombre..."
                        class="rounded-xl border border-stone-300 px-3.5 py-1.5 text-xs text-stone-700 w-full sm:w-64 focus:ring-1 focus:ring-orange-500"
                    />

                    <select
                        v-model="filtroCategoria"
                        @change="aplicarFiltros"
                        class="rounded-xl border border-stone-300 px-3 py-1.5 text-xs text-stone-700 focus:ring-1 focus:ring-orange-500"
                    >
                        <option :value="null">Todas las categorías</option>
                        <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                            {{ cat.nombre }}
                        </option>
                    </select>

                    <label class="flex items-center space-x-1.5 text-xs text-stone-600 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            v-model="filtroBajoStock"
                            @change="aplicarFiltros"
                            class="rounded text-orange-600 focus:ring-orange-500"
                        />
                        <span>Solo bajo stock</span>
                    </label>

                    <button
                        type="button"
                        @click="aplicarFiltros"
                        class="px-3.5 py-1.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-semibold transition"
                    >
                        Filtrar
                    </button>
                </div>
            </div>

            <!-- Tabla de Existencias Actuales -->
            <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-stone-100 flex items-center justify-between">
                    <h2 class="font-bold text-sm text-stone-900">Existencias Actuales</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 uppercase tracking-wider font-semibold">
                                <th class="p-3.5">Producto</th>
                                <th class="p-3.5">Categoría</th>
                                <th class="p-3.5">Precio Venta</th>
                                <th class="p-3.5">Existencia Actual</th>
                                <th class="p-3.5">Stock Mínimo</th>
                                <th class="p-3.5">Estado</th>
                                <th class="p-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr
                                v-for="item in items.data"
                                :key="item.id"
                                :class="[
                                    'hover:bg-stone-50/70 transition',
                                    item.bajo_stock ? 'bg-rose-50/20' : ''
                                ]"
                            >
                                <td class="p-3.5 font-bold text-stone-900">
                                    {{ item.nombre }}
                                </td>
                                <td class="p-3.5 text-stone-600">
                                    {{ item.categoria }}
                                </td>
                                <td class="p-3.5 font-mono text-stone-700 font-semibold">
                                    ${{ item.precio.toLocaleString() }}
                                </td>
                                <td class="p-3.5 font-mono font-black text-sm text-stone-900">
                                    {{ item.existencia }}
                                </td>
                                <td class="p-3.5 font-mono text-stone-500">
                                    {{ item.existencia_minima }}
                                </td>
                                <td class="p-3.5">
                                    <span
                                        v-if="item.bajo_stock"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200"
                                    >
                                        Stock Crítico
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        Normal
                                    </span>
                                </td>
                                <td class="p-3.5 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="abrirModalAjustar(item)"
                                        class="px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold text-[11px] shadow-xs transition"
                                    >
                                        Ajustar Stock
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!items.data || items.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-stone-400">
                                    No se encontraron productos con los filtros aplicados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="items.links && items.links.length > 3" class="p-4 border-t border-stone-100 flex justify-center space-x-1">
                    <template v-for="(link, i) in items.links" :key="i">
                        <a
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-medium border transition',
                                link.active ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-stone-700 border-stone-200 hover:bg-stone-50'
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border border-stone-200 text-stone-300 opacity-50"
                        />
                    </template>
                </div>
            </div>

            <!-- Movimientos Recientes del Kárdex Inmutable -->
            <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-stone-100">
                    <h2 class="font-bold text-sm text-stone-900">Movimientos Recientes del Kárdex</h2>
                    <p class="text-[11px] text-stone-400">Bitácora inmutable de entradas, salidas y ajustes.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 uppercase tracking-wider font-semibold">
                                <th class="p-3">Fecha</th>
                                <th class="p-3">Producto</th>
                                <th class="p-3">Tipo Movimiento</th>
                                <th class="p-3">Cantidad</th>
                                <th class="p-3">Saldo Posterior</th>
                                <th class="p-3">Justificación / Motivo</th>
                                <th class="p-3">Usuario</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="m in ultimosMovimientos" :key="m.id" class="hover:bg-stone-50/50">
                                <td class="p-3 font-mono text-stone-500 text-[11px]">{{ m.fecha }}</td>
                                <td class="p-3 font-semibold text-stone-800">{{ m.producto }}</td>
                                <td class="p-3">
                                    <span :class="[
                                        'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                        m.es_ingreso ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'
                                    ]">
                                        {{ m.tipo_etiqueta }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono font-bold">
                                    <span :class="m.es_ingreso ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ m.es_ingreso ? '+' : '-' }}{{ m.cantidad }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-stone-800 font-bold">{{ m.saldo_cantidad }}</td>
                                <td class="p-3 text-stone-500 italic max-w-xs truncate" :title="m.motivo">{{ m.motivo || '-' }}</td>
                                <td class="p-3 text-stone-600">{{ m.usuario || 'Sistema' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de Ajuste de Stock Individual (RF-INV-05) -->
        <div v-if="modalAjustarAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Ajustar Stock: {{ itemSeleccionado?.nombre }}
                    </h3>
                    <button @click="cerrarModalAjustar" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <form @submit.prevent="ejecutarAjuste" class="space-y-4 text-xs">
                    <div>
                        <span class="text-stone-500 block mb-1">Existencia actual en sistema:</span>
                        <span class="text-lg font-black font-mono text-stone-800">{{ itemSeleccionado?.existencia }} unidades</span>
                    </div>

                    <div>
                        <label for="nueva_existencia" class="block font-semibold text-stone-700 mb-1">
                            Nueva Existencia Física Real *
                        </label>
                        <input
                            id="nueva_existencia"
                            type="number"
                            step="0.01"
                            min="0"
                            v-model.number="formAjustar.nueva_existencia"
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:ring-1 focus:ring-orange-500"
                            required
                        />
                        <p v-if="formAjustar.errors.nueva_existencia" class="text-rose-600 mt-1">
                            {{ formAjustar.errors.nueva_existencia }}
                        </p>
                    </div>

                    <div>
                        <label for="motivo_ajuste" class="block font-semibold text-stone-700 mb-1">
                            Motivo / Justificación del Ajuste * (RF-INV-05)
                        </label>
                        <textarea
                            id="motivo_ajuste"
                            v-model="formAjustar.motivo"
                            rows="3"
                            placeholder="Ej. Conteo físico de fin de mes, producto averiado en cocina..."
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:ring-1 focus:ring-orange-500"
                            required
                        ></textarea>
                        <p v-if="formAjustar.errors.motivo" class="text-rose-600 mt-1">
                            {{ formAjustar.errors.motivo }}
                        </p>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="cerrarModalAjustar" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50 font-medium">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formAjustar.processing" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold transition">
                            {{ formAjustar.processing ? 'Guardando...' : 'Aplicar Ajuste' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal de Cambio de Modo (SuperAdmin) -->
        <div v-if="modalModoAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Modo de Inventario (SuperAdmin)
                    </h3>
                    <button @click="modalModoAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarCambioModo" class="space-y-4 text-xs">
                    <p class="text-stone-600">
                        Selecciona el régimen de inventario aplicable para esta sucursal:
                    </p>
                    <div class="space-y-2">
                        <label class="flex items-start space-x-2 p-3 border rounded-xl cursor-pointer hover:bg-stone-50">
                            <input type="radio" value="autonomo" v-model="formModo.modo" class="mt-0.5 text-orange-600" />
                            <div>
                                <span class="font-bold text-stone-900 block">Autónomo</span>
                                <span class="text-stone-500 text-[11px]">Registro y control directo de stock por cada plato o producto.</span>
                            </div>
                        </label>
                        <label class="flex items-start space-x-2 p-3 border rounded-xl cursor-pointer hover:bg-stone-50">
                            <input type="radio" value="dependiente" v-model="formModo.modo" class="mt-0.5 text-orange-600" />
                            <div>
                                <span class="font-bold text-stone-900 block">Dependiente</span>
                                <span class="text-stone-500 text-[11px]">Vinculado directamente a compras a proveedores con factor de conversión.</span>
                            </div>
                        </label>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalModoAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formModo.processing" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold transition">
                            Guardar Modo
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
    items: { type: Object, required: true },
    ultimosMovimientos: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
    modoInventario: { type: String, required: true },
    modoInventarioEtiqueta: { type: String, required: true },
    alertasCount: { type: Number, default: 0 },
    filtros: { type: Object, default: () => ({}) },
    sucursales: { type: Array, default: () => [] },
    sucursal_id: { type: Number, required: true },
    esSuperAdmin: { type: Boolean, default: false },
});

const sucursalSeleccionada = ref(props.sucursal_id);
const filtroBusqueda = ref(props.filtros.q || '');
const filtroCategoria = ref(props.filtros.categoria_id || null);
const filtroBajoStock = ref(Boolean(props.filtros.solo_bajo_stock));

const cambiarSucursal = () => {
    router.get('/admin/inventario', {
        sucursal_id: sucursalSeleccionada.value,
    });
};

const aplicarFiltros = () => {
    router.get('/admin/inventario', {
        sucursal_id: sucursalSeleccionada.value,
        q: filtroBusqueda.value || undefined,
        categoria_id: filtroCategoria.value || undefined,
        solo_bajo_stock: filtroBajoStock.value ? 1 : undefined,
    });
};

// Modal Ajustar Stock
const modalAjustarAbierto = ref(false);
const itemSeleccionado = ref(null);
const formAjustar = useForm({
    nueva_existencia: 0,
    motivo: '',
});

const abrirModalAjustar = (item) => {
    itemSeleccionado.value = item;
    formAjustar.nueva_existencia = item.existencia;
    formAjustar.motivo = '';
    modalAjustarAbierto.value = true;
};

const cerrarModalAjustar = () => {
    modalAjustarAbierto.value = false;
    itemSeleccionado.value = null;
    formAjustar.reset();
};

const ejecutarAjuste = () => {
    if (!itemSeleccionado.value) return;
    formAjustar.nueva_existencia = Math.max(0, Number(formAjustar.nueva_existencia) || 0);
    formAjustar.post(`/admin/inventario/${itemSeleccionado.value.id}/ajustar`, {
        preserveScroll: true,
        onSuccess: () => cerrarModalAjustar(),
    });
};

// Modal Cambiar Modo
const modalModoAbierto = ref(false);
const formModo = useForm({
    sucursal_id: props.sucursal_id,
    modo: props.modoInventario,
});

const abrirModalModo = () => {
    formModo.sucursal_id = props.sucursal_id;
    formModo.modo = props.modoInventario;
    modalModoAbierto.value = true;
};

const ejecutarCambioModo = () => {
    formModo.post('/admin/inventario/modo', {
        preserveScroll: true,
        onSuccess: () => { modalModoAbierto.value = false; },
    });
};
</script>

