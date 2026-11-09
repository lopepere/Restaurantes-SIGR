<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>🧾</span>
                        <span>Comprobantes de Venta (FAC)</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Control inmutable de comprobantes, código CUV, hash criptográfico SHA-256 y reenvíos auditados (SC-03, RF-FAC-01..06, CA-31).
                    </p>
                </div>

                <!-- Selector de Sucursal -->
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
            </div>

            <!-- Filtros de Búsqueda -->
            <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-xs flex flex-wrap gap-3 items-center justify-between">
                <div class="flex flex-wrap gap-2 items-center flex-1">
                    <input
                        type="text"
                        v-model="filtroNumero"
                        @keyup.enter="aplicarFiltros"
                        placeholder="Buscar por número (ej. FAC-1-0001)..."
                        class="rounded-xl border border-stone-300 px-3.5 py-1.5 text-xs text-stone-700 w-full sm:w-56 focus:ring-1 focus:ring-orange-500"
                    />

                    <input
                        type="text"
                        v-model="filtroCliente"
                        @keyup.enter="aplicarFiltros"
                        placeholder="Buscar por cliente o email..."
                        class="rounded-xl border border-stone-300 px-3.5 py-1.5 text-xs text-stone-700 w-full sm:w-56 focus:ring-1 focus:ring-orange-500"
                    />

                    <input
                        type="date"
                        v-model="filtroFecha"
                        @change="aplicarFiltros"
                        class="rounded-xl border border-stone-300 px-3 py-1.5 text-xs text-stone-700 focus:ring-1 focus:ring-orange-500"
                    />

                    <button
                        type="button"
                        @click="aplicarFiltros"
                        class="px-3.5 py-1.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold shadow-xs transition"
                    >
                        Filtrar
                    </button>

                    <button
                        v-if="filtroNumero || filtroCliente || filtroFecha"
                        type="button"
                        @click="limpiarFiltros"
                        class="px-3 py-1.5 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium transition"
                    >
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla de Comprobantes -->
            <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-stone-600">
                        <thead class="bg-stone-50 border-b border-stone-200 text-stone-800 font-bold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="px-4 py-3">Número</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Pedido</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Método</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                                <th class="px-4 py-3 text-right">Propina</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3">Hash SHA-256</th>
                                <th class="px-4 py-3 text-center">Envíos</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="comp in comprobantes.data" :key="comp.id" class="hover:bg-stone-50/70 transition">
                                <td class="px-4 py-3 font-bold text-stone-900">
                                    {{ comp.numero }}
                                </td>
                                <td class="px-4 py-3 text-stone-500 whitespace-nowrap">
                                    {{ comp.fecha }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-stone-700">
                                    {{ comp.pedido_numero ? '#' + comp.pedido_numero : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-stone-900">{{ comp.cliente }}</div>
                                    <div class="text-[10px] text-stone-400">{{ comp.email || 'Sin correo' }}</div>
                                </td>
                                <td class="px-4 py-3 capitalize">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-700">
                                        {{ comp.metodo_pago }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono">
                                    ${{ formatearDinero(comp.subtotal) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-600 font-semibold">
                                    ${{ formatearDinero(comp.propina) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-stone-900">
                                    ${{ formatearDinero(comp.total) }}
                                </td>
                                <td class="px-4 py-3 font-mono text-[10px] text-stone-400" :title="comp.hash_pdf">
                                    {{ comp.hash_pdf ? comp.hash_pdf.substring(0, 10) + '...' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700">
                                        ✉️ {{ comp.envios_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <!-- Verificación Pública CUV -->
                                        <a
                                            :href="'/comprobantes/' + comp.codigo_verificacion"
                                            target="_blank"
                                            class="p-1 text-stone-500 hover:text-stone-800 hover:bg-stone-100 rounded-lg transition"
                                            title="Verificar en línea (CUV)"
                                        >
                                            👁️
                                        </a>

                                        <!-- Tirilla térmica 80mm -->
                                        <a
                                            :href="'/comprobantes/' + comp.codigo_verificacion + '/tirilla'"
                                            target="_blank"
                                            class="p-1 text-stone-500 hover:text-stone-800 hover:bg-stone-100 rounded-lg transition"
                                            title="Imprimir tirilla térmica 80 mm"
                                        >
                                            🖨️
                                        </a>

                                        <!-- Reenviar Correo -->
                                        <button
                                            type="button"
                                            @click="abrirModalReenvio(comp)"
                                            class="p-1 text-stone-500 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition"
                                            title="Reenviar por correo"
                                        >
                                            📤
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!comprobantes.data || comprobantes.data.length === 0">
                                <td colspan="11" class="px-4 py-8 text-center text-stone-400">
                                    No se encontraron comprobantes de venta registrados con los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="comprobantes.links && comprobantes.links.length > 3" class="p-3 border-t border-stone-100 flex justify-center space-x-1">
                    <Link
                        v-for="(link, idx) in comprobantes.links"
                        :key="idx"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1 rounded-lg text-xs font-medium transition',
                            link.active ? 'bg-orange-600 text-white font-bold' : 'text-stone-600 hover:bg-stone-100',
                            !link.url ? 'opacity-40 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>

            <!-- Modal Reenviar Comprobante -->
            <div v-if="mostrarModalReenvio" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-4 backdrop-blur-xs">
                <div class="bg-white rounded-2xl border border-stone-200 shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="px-6 py-4 border-b border-stone-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-stone-900">Reenviar Comprobante</h3>
                            <p class="text-xs text-stone-500">Comprobante {{ comprobanteSeleccionado?.numero }}</p>
                        </div>
                        <button @click="cerrarModalReenvio" class="text-stone-400 hover:text-stone-600 text-lg leading-none">&times;</button>
                    </div>

                    <form @submit.prevent="ejecutarReenvio" class="p-6 space-y-4">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-stone-700">
                                Destinatarios (hasta 3 correos)
                            </label>
                            <div v-for="(correo, index) in formReenvio.correos" :key="index" class="flex items-center space-x-2">
                                <input
                                    type="email"
                                    v-model="formReenvio.correos[index]"
                                    placeholder="correo@ejemplo.com"
                                    required
                                    class="w-full rounded-xl border border-stone-300 px-3 py-1.5 text-xs focus:ring-1 focus:ring-orange-500"
                                />
                                <button
                                    v-if="formReenvio.correos.length > 1"
                                    type="button"
                                    @click="quitarCorreo(index)"
                                    class="p-1.5 text-stone-400 hover:text-rose-600 rounded-lg"
                                >
                                    ✕
                                </button>
                            </div>

                            <button
                                v-if="formReenvio.correos.length < 3"
                                type="button"
                                @click="agregarCorreo"
                                class="text-xs text-orange-600 hover:text-orange-700 font-semibold flex items-center space-x-1"
                            >
                                <span>+</span>
                                <span>Añadir otro destinatario</span>
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-stone-700 mb-1">
                                Mensaje personalizado (opcional)
                            </label>
                            <textarea
                                v-model="formReenvio.mensaje"
                                rows="3"
                                maxlength="300"
                                placeholder="Estimado cliente, adjuntamos copia de su comprobante..."
                                class="w-full rounded-xl border border-stone-300 px-3 py-2 text-xs focus:ring-1 focus:ring-orange-500"
                            ></textarea>
                            <p class="text-[10px] text-stone-400 text-right">{{ 300 - (formReenvio.mensaje?.length || 0) }} caracteres restantes</p>
                        </div>

                        <div class="flex items-center justify-end space-x-2 pt-2">
                            <button
                                type="button"
                                @click="cerrarModalReenvio"
                                class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 text-xs font-semibold hover:bg-stone-50"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="formReenvio.processing"
                                class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold shadow-xs disabled:opacity-50"
                            >
                                {{ formReenvio.processing ? 'Enviando...' : 'Reenviar Comprobante' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    comprobantes: Object,
    filtros: Object,
    sucursales: Array,
    sucursal_id: Number,
});

const sucursalSeleccionada = ref(props.sucursal_id);
const filtroNumero = ref(props.filtros?.numero || '');
const filtroCliente = ref(props.filtros?.cliente || '');
const filtroFecha = ref(props.filtros?.fecha || '');

const mostrarModalReenvio = ref(false);
const comprobanteSeleccionado = ref(null);

const formReenvio = useForm({
    correos: [''],
    mensaje: '',
});

function formatearDinero(valor) {
    return Number(valor || 0).toLocaleString('es-CO');
}

function cambiarSucursal() {
    router.get(route('admin.comprobantes.index'), {
        sucursal_id: sucursalSeleccionada.value,
        numero: filtroNumero.value || undefined,
        cliente: filtroCliente.value || undefined,
        fecha: filtroFecha.value || undefined,
    }, { preserveState: true });
}

function aplicarFiltros() {
    router.get(route('admin.comprobantes.index'), {
        sucursal_id: sucursalSeleccionada.value,
        numero: filtroNumero.value || undefined,
        cliente: filtroCliente.value || undefined,
        fecha: filtroFecha.value || undefined,
    }, { preserveState: true });
}

function limpiarFiltros() {
    filtroNumero.value = '';
    filtroCliente.value = '';
    filtroFecha.value = '';
    aplicarFiltros();
}

function abrirModalReenvio(comprobante) {
    comprobanteSeleccionado.value = comprobante;
    formReenvio.correos = comprobante.email ? [comprobante.email] : [''];
    formReenvio.mensaje = '';
    mostrarModalReenvio.value = true;
}

function cerrarModalReenvio() {
    mostrarModalReenvio.value = false;
    comprobanteSeleccionado.value = null;
    formReenvio.reset();
}

function agregarCorreo() {
    if (formReenvio.correos.length < 3) {
        formReenvio.correos.push('');
    }
}

function quitarCorreo(index) {
    if (formReenvio.correos.length > 1) {
        formReenvio.correos.splice(index, 1);
    }
}

function ejecutarReenvio() {
    formReenvio.post(route('admin.comprobantes.reenviar', comprobanteSeleccionado.value.id), {
        onSuccess: () => {
            cerrarModalReenvio();
        },
    });
}
</script>

