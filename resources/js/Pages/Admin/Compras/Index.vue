<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>🛒</span>
                        <span>Compras a Proveedores</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Recepción de insumos, costeo y kárdex con factor de conversión (RF-COM-01..08). Inmutables por diseño.
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
                        type="button"
                        @click="abrirModalProveedor"
                        class="px-3 py-2 rounded-xl border border-stone-300 bg-white hover:bg-stone-50 text-stone-700 text-xs font-semibold shadow-xs transition"
                    >
                        + Nuevo Proveedor
                    </button>

                    <button
                        type="button"
                        @click="abrirModalCompra"
                        class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        + Registrar Compra
                    </button>
                </div>
            </div>

            <!-- Tabla de Compras -->
            <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 uppercase tracking-wider font-semibold">
                                <th class="p-3.5">Factura #</th>
                                <th class="p-3.5">Proveedor</th>
                                <th class="p-3.5">Fecha</th>
                                <th class="p-3.5">Ítems</th>
                                <th class="p-3.5">Subtotal</th>
                                <th class="p-3.5">Impuesto</th>
                                <th class="p-3.5">Total Compra</th>
                                <th class="p-3.5">Estado</th>
                                <th class="p-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr
                                v-for="compra in compras.data"
                                :key="compra.id"
                                class="hover:bg-stone-50/70 transition"
                            >
                                <td class="p-3.5 font-mono font-bold text-stone-900 select-all">
                                    {{ compra.numero_factura }}
                                </td>
                                <td class="p-3.5 font-medium text-stone-800">
                                    {{ compra.proveedor }}
                                </td>
                                <td class="p-3.5 font-mono text-stone-600">
                                    {{ compra.fecha_compra }}
                                </td>
                                <td class="p-3.5 text-stone-500">
                                    {{ compra.items_count }} producto(s)
                                </td>
                                <td class="p-3.5 font-mono text-stone-600">
                                    ${{ compra.subtotal.toLocaleString() }}
                                </td>
                                <td class="p-3.5 font-mono text-stone-600">
                                    ${{ compra.impuesto_total.toLocaleString() }}
                                </td>
                                <td class="p-3.5 font-mono font-bold text-stone-900 text-sm">
                                    ${{ compra.total.toLocaleString() }}
                                </td>
                                <td class="p-3.5">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                            badgeEstado(compra.estado)
                                        ]"
                                    >
                                        {{ compra.estado_etiqueta }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                                    <button
                                        v-if="compra.puede_devolver"
                                        type="button"
                                        @click="abrirModalDevolver(compra)"
                                        class="px-2.5 py-1 border border-amber-300 text-amber-700 hover:bg-amber-50 rounded-lg font-semibold text-[11px] transition"
                                        title="Registrar devolución a proveedor"
                                    >
                                        Devolver
                                    </button>
                                    <button
                                        v-if="compra.puede_anular"
                                        type="button"
                                        @click="abrirModalAnular(compra)"
                                        class="px-2.5 py-1 border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg font-semibold text-[11px] transition"
                                        title="Anular compra y revertir stock"
                                    >
                                        Anular
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!compras.data || compras.data.length === 0">
                                <td colspan="9" class="p-8 text-center text-stone-400">
                                    No se encontraron compras registradas en esta sucursal.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="compras.links && compras.links.length > 3" class="p-4 border-t border-stone-100 flex justify-center space-x-1">
                    <template v-for="(link, i) in compras.links" :key="i">
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
        </div>

        <!-- Modal Registrar Compra (RF-COM-02) -->
        <div v-if="modalCompraAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl border border-stone-200 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Registrar Compra a Proveedor
                    </h3>
                    <button @click="modalCompraAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <form @submit.prevent="ejecutarRegistroCompra" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="proveedor_id" class="block font-semibold text-stone-700 mb-1">Proveedor *</label>
                            <select
                                id="proveedor_id"
                                v-model="formCompra.proveedor_id"
                                class="w-full rounded-xl border border-stone-300 p-2 text-xs text-stone-800"
                                required
                            >
                                <option :value="null">Seleccionar proveedor</option>
                                <option v-for="p in proveedores" :key="p.id" :value="p.id">
                                    {{ p.razon_social }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="num_fac" class="block font-semibold text-stone-700 mb-1">Nº Factura Proveedor *</label>
                            <input
                                id="num_fac"
                                type="text"
                                v-model="formCompra.numero_factura"
                                placeholder="FAC-12345"
                                class="w-full rounded-xl border border-stone-300 p-2 text-xs text-stone-800"
                                required
                            />
                        </div>

                        <div>
                            <label for="fecha_compra" class="block font-semibold text-stone-700 mb-1">Fecha de Compra *</label>
                            <input
                                id="fecha_compra"
                                type="date"
                                v-model="formCompra.fecha_compra"
                                class="w-full rounded-xl border border-stone-300 p-2 text-xs text-stone-800"
                                required
                            />
                        </div>
                    </div>

                    <!-- Configuración de Impuestos (Radio buttons por defecto sin impuesto A.6) -->
                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 space-y-2">
                        <span class="font-semibold text-stone-700 block">Impuesto de Compra:</span>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center space-x-1.5 cursor-pointer">
                                <input type="radio" :value="0" v-model.number="formCompra.impuesto_porcentaje" class="text-orange-600" />
                                <span>Sin impuesto (0%)</span>
                            </label>
                            <label class="flex items-center space-x-1.5 cursor-pointer">
                                <input type="radio" :value="19" v-model.number="formCompra.impuesto_porcentaje" class="text-orange-600" />
                                <span>IVA General (19%)</span>
                            </label>
                            <label class="flex items-center space-x-1.5 cursor-pointer">
                                <input type="radio" :value="5" v-model.number="formCompra.impuesto_porcentaje" class="text-orange-600" />
                                <span>IVA Reducido (5%)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Tabla de Líneas de Ítems -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-stone-800">Líneas de la Compra</span>
                            <button
                                type="button"
                                @click="agregarLinea"
                                class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg font-semibold text-[11px]"
                            >
                                + Agregar Producto
                            </button>
                        </div>

                        <div v-for="(linea, idx) in formCompra.items" :key="idx" class="p-3 border rounded-xl grid grid-cols-1 sm:grid-cols-5 gap-2 items-center bg-stone-50/50">
                            <div class="sm:col-span-2">
                                <label class="text-[10px] text-stone-500 block mb-0.5">Vincular a producto de menú</label>
                                <select
                                    v-model="linea.item_menu_id"
                                    @change="onItemSeleccionado(linea)"
                                    class="w-full rounded-lg border border-stone-300 p-1.5 text-xs bg-white"
                                >
                                    <option :value="null">Insumo general / Sin vinculación</option>
                                    <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                        {{ prod.nombre }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-[10px] text-stone-500 block mb-0.5">Cantidad</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    v-model.number="linea.cantidad"
                                    class="w-full rounded-lg border border-stone-300 p-1.5 text-xs bg-white font-mono"
                                    required
                                />
                            </div>

                            <div>
                                <label class="text-[10px] text-stone-500 block mb-0.5">Costo Unitario ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model.number="linea.costo_unitario"
                                    class="w-full rounded-lg border border-stone-300 p-1.5 text-xs bg-white font-mono"
                                    required
                                />
                            </div>

                            <div class="flex items-center space-x-1 pt-3 sm:pt-0">
                                <span class="font-mono font-bold text-stone-800 text-xs flex-1 text-right">
                                    ${{ (linea.cantidad * linea.costo_unitario).toLocaleString() }}
                                </span>
                                <button
                                    v-if="formCompra.items.length > 1"
                                    type="button"
                                    @click="eliminarLinea(idx)"
                                    class="p-1 text-rose-500 hover:text-rose-700"
                                    title="Quitar línea"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Total Estimado -->
                    <div class="p-3 bg-stone-100 rounded-xl text-right font-mono text-xs">
                        <span>Total de la Compra: </span>
                        <strong class="text-sm text-stone-900">${{ calcularTotalCompra().toLocaleString() }}</strong>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalCompraAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50 font-medium">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formCompra.processing" class="px-5 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold transition">
                            {{ formCompra.processing ? 'Registrando...' : 'Guardar Compra' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Anular Compra (RF-COM-06) -->
        <div v-if="modalAnularAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Anular Factura #{{ compraSeleccionada?.numero_factura }}
                    </h3>
                    <button @click="modalAnularAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarAnulacion" class="space-y-4 text-xs">
                    <p class="text-stone-600">
                        Esta operación descontará automáticamente del kárdex las existencias previamente sumadas.
                    </p>
                    <div>
                        <label for="motivo_anular" class="block font-semibold text-stone-700 mb-1">Motivo de Anulación *</label>
                        <textarea
                            id="motivo_anular"
                            v-model="formAnular.motivo"
                            rows="3"
                            placeholder="Ej. Factura duplicada, error en precios..."
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800"
                            required
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalAnularAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formAnular.processing" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold transition">
                            {{ formAnular.processing ? 'Anulando...' : 'Confirmar Anulación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Devolver Compra (RF-COM-07) -->
        <div v-if="modalDevolverAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Devolución Factura #{{ compraSeleccionada?.numero_factura }}
                    </h3>
                    <button @click="modalDevolverAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarDevolucion" class="space-y-4 text-xs">
                    <p class="text-stone-600">
                        Se registrará la devolución formal al proveedor y se restarán las existencias del inventario.
                    </p>
                    <div>
                        <label for="motivo_devolver" class="block font-semibold text-stone-700 mb-1">Motivo de la Devolución *</label>
                        <textarea
                            id="motivo_devolver"
                            v-model="formDevolver.motivo"
                            rows="3"
                            placeholder="Ej. Producto en mal estado, vencimiento próximo..."
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800"
                            required
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalDevolverAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formDevolver.processing" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold transition">
                            {{ formDevolver.processing ? 'Devolviendo...' : 'Confirmar Devolución' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Nuevo Proveedor -->
        <div v-if="modalProveedorAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">Registrar Proveedor</h3>
                    <button @click="modalProveedorAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarRegistroProveedor" class="space-y-3 text-xs">
                    <div>
                        <label for="razon_social" class="block font-semibold text-stone-700 mb-1">Razón Social *</label>
                        <input id="razon_social" type="text" v-model="formProveedor.razon_social" class="w-full rounded-xl border border-stone-300 p-2 text-xs" required />
                    </div>
                    <div>
                        <label for="nit_prov" class="block font-semibold text-stone-700 mb-1">NIT / RUT</label>
                        <input id="nit_prov" type="text" v-model="formProveedor.nit" placeholder="900.123.456-7" class="w-full rounded-xl border border-stone-300 p-2 text-xs" />
                    </div>
                    <div>
                        <label for="tel_prov" class="block font-semibold text-stone-700 mb-1">Teléfono</label>
                        <input id="tel_prov" type="text" v-model="formProveedor.telefono" class="w-full rounded-xl border border-stone-300 p-2 text-xs" />
                    </div>
                    <div>
                        <label for="email_prov" class="block font-semibold text-stone-700 mb-1">Correo Electrónico</label>
                        <input id="email_prov" type="email" v-model="formProveedor.email" class="w-full rounded-xl border border-stone-300 p-2 text-xs" />
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalProveedorAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formProveedor.processing" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold transition">
                            Guardar Proveedor
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
    compras: { type: Object, required: true },
    proveedores: { type: Array, default: () => [] },
    productos: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
    sucursales: { type: Array, default: () => [] },
    sucursal_id: { type: Number, required: true },
});

const sucursalSeleccionada = ref(props.sucursal_id);

const cambiarSucursal = () => {
    router.get('/admin/compras', { sucursal_id: sucursalSeleccionada.value });
};

// Modal Compra
const modalCompraAbierto = ref(false);
const formCompra = useForm({
    sucursal_id: props.sucursal_id,
    proveedor_id: null,
    numero_factura: '',
    fecha_compra: new Date().toISOString().split('T')[0],
    incluye_impuesto: false,
    impuesto_porcentaje: 0,
    items: [
        { item_menu_id: null, descripcion: 'Insumos varios', cantidad: 10, costo_unitario: 5000, factor_conversion: 1.0 },
    ],
});

const abrirModalCompra = () => {
    formCompra.reset();
    formCompra.sucursal_id = props.sucursal_id;
    formCompra.fecha_compra = new Date().toISOString().split('T')[0];
    modalCompraAbierto.value = true;
};

const agregarLinea = () => {
    formCompra.items.push({
        item_menu_id: null,
        descripcion: 'Insumo nuevo',
        cantidad: 1,
        costo_unitario: 0,
        factor_conversion: 1.0,
    });
};

const eliminarLinea = (idx) => {
    formCompra.items.splice(idx, 1);
};

const onItemSeleccionado = (linea) => {
    if (linea.item_menu_id) {
        const prod = props.productos.find(p => p.id === linea.item_menu_id);
        if (prod) {
            linea.descripcion = prod.nombre;
        }
    }
};

const calcularTotalCompra = () => {
    const subtotal = formCompra.items.reduce((acc, it) => acc + (it.cantidad * it.costo_unitario), 0);
    const imp = subtotal * (formCompra.impuesto_porcentaje / 100);
    return subtotal + imp;
};

const ejecutarRegistroCompra = () => {
    formCompra.post('/admin/compras', {
        preserveScroll: true,
        onSuccess: () => { modalCompraAbierto.value = false; },
    });
};

// Modal Anular
const modalAnularAbierto = ref(false);
const compraSeleccionada = ref(null);
const formAnular = useForm({ motivo: '' });

const abrirModalAnular = (compra) => {
    compraSeleccionada.value = compra;
    formAnular.reset();
    modalAnularAbierto.value = true;
};

const ejecutarAnulacion = () => {
    if (!compraSeleccionada.value) return;
    formAnular.post(`/admin/compras/${compraSeleccionada.value.id}/anular`, {
        preserveScroll: true,
        onSuccess: () => { modalAnularAbierto.value = false; },
    });
};

// Modal Devolver
const modalDevolverAbierto = ref(false);
const formDevolver = useForm({ motivo: '' });

const abrirModalDevolver = (compra) => {
    compraSeleccionada.value = compra;
    formDevolver.reset();
    modalDevolverAbierto.value = true;
};

const ejecutarDevolucion = () => {
    if (!compraSeleccionada.value) return;
    formDevolver.post(`/admin/compras/${compraSeleccionada.value.id}/devolver`, {
        preserveScroll: true,
        onSuccess: () => { modalDevolverAbierto.value = false; },
    });
};

// Modal Proveedor
const modalProveedorAbierto = ref(false);
const formProveedor = useForm({
    sucursal_id: props.sucursal_id,
    razon_social: '',
    nit: '',
    telefono: '',
    email: '',
});

const abrirModalProveedor = () => {
    formProveedor.reset();
    formProveedor.sucursal_id = props.sucursal_id;
    modalProveedorAbierto.value = true;
};

const ejecutarRegistroProveedor = () => {
    formProveedor.post('/admin/compras/proveedores', {
        preserveScroll: true,
        onSuccess: () => { modalProveedorAbierto.value = false; },
    });
};

const badgeEstado = (estado) => {
    return {
        'registrada': 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        'anulada': 'bg-rose-100 text-rose-800 border border-rose-200',
        'devuelta': 'bg-amber-100 text-amber-800 border border-amber-200',
    }[estado] || 'bg-stone-100 text-stone-700';
};
</script>

