<template>
    <ClienteLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Barra superior de navegación y acciones -->
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-stone-200 pb-5">
                <div class="flex items-center gap-3">
                    <a
                        href="/cliente/pedidos"
                        class="p-2 text-stone-500 hover:text-stone-900 rounded-xl hover:bg-stone-100 transition"
                    >
                        ← Volver
                    </a>
                    <div>
                        <h1 class="text-2xl font-black text-stone-900 tracking-tight flex items-center gap-2">
                            <span>Pedido {{ pedidoData.numero }}</span>
                        </h1>
                        <p class="text-xs text-stone-500">Sucursal: {{ pedidoData.sucursal_nombre }} • {{ pedidoData.fecha }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Botón para editar notas dentro de la ventana de 5 min (CA-10) -->
                    <button
                        v-if="pedidoData.puede_editar_notas"
                        type="button"
                        @click="mostrarModalNotas = true"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                        ✏️ Editar notas (5 min)
                    </button>

                    <!-- Botón para solicitar cancelación (CA-08) -->
                    <button
                        v-if="pedidoData.esta_activo && !pedidoData.solicito_cancelacion"
                        type="button"
                        @click="mostrarModalCancelar = true"
                        class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 transition cursor-pointer"
                    >
                        Solicitar cancelación
                    </button>
                </div>
            </div>

            <!-- Banner de estado en tiempo real -->
            <div class="bg-white rounded-3xl border border-stone-200 p-6 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-2xl font-black">
                        🛎️
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-stone-500 uppercase tracking-wider">Estado de la orden</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <h3 class="text-xl font-black text-stone-900">{{ pedidoData.estado_etiqueta }}</h3>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-orange-50 text-orange-700 border border-orange-200">
                                {{ pedidoData.etiqueta_cliente }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="pedidoData.solicito_cancelacion" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs font-medium">
                    ⚠️ Has solicitado la cancelación de este pedido. Administración está revisando tu solicitud.
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna Izquierda: Ítems y detalles -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-3xl border border-stone-200 p-6 shadow-xs">
                        <h3 class="text-sm font-bold text-stone-900 uppercase tracking-wider mb-4">Productos ordenados</h3>
                        <div class="divide-y divide-stone-100">
                            <div
                                v-for="item in pedidoData.items"
                                :key="item.id"
                                class="py-3 flex items-center justify-between gap-4"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="w-7 h-7 flex items-center justify-center bg-stone-100 text-stone-800 font-bold text-xs rounded-lg">
                                        {{ item.cantidad }}x
                                    </span>
                                    <div>
                                        <p class="font-bold text-stone-900 text-sm">{{ item.nombre }}</p>
                                        <p v-if="item.notas" class="text-xs text-stone-500 italic">Nota: {{ item.notas }}</p>
                                    </div>
                                </div>
                                <span class="font-bold text-stone-900 text-sm">
                                    {{ formatearMoneda(item.total) }}
                                </span>
                            </div>
                        </div>

                        <div v-if="pedidoData.notas" class="mt-4 p-3 bg-stone-50 rounded-xl border border-stone-200 text-xs text-stone-700">
                            <span class="font-bold">Notas generales:</span> {{ pedidoData.notas }}
                        </div>
                    </div>

                    <!-- Timeline de eventos -->
                    <div class="bg-white rounded-3xl border border-stone-200 p-6 shadow-xs">
                        <h3 class="text-sm font-bold text-stone-900 uppercase tracking-wider mb-4">Línea de tiempo del pedido</h3>
                        <div class="space-y-4">
                            <div
                                v-for="(hist, idx) in pedidoData.estados"
                                :key="idx"
                                class="flex items-start gap-3 text-xs"
                            >
                                <span class="w-2.5 h-2.5 rounded-full bg-orange-500 mt-1"></span>
                                <div>
                                    <p class="font-bold text-stone-900">{{ hist.estado }} <span class="font-normal text-stone-500">({{ hist.hora }})</span></p>
                                    <p class="text-stone-600">{{ hist.motivo }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Resumen Financiero y Entrega -->
                <div class="space-y-6">
                    <div class="bg-white rounded-3xl border border-stone-200 p-6 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-stone-900 uppercase tracking-wider">Resumen de cuenta</h3>
                        <div class="space-y-2 text-xs text-stone-600">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span>{{ formatearMoneda(pedidoData.subtotal) }}</span>
                            </div>
                            <div v-if="pedidoData.descuento > 0" class="flex justify-between text-emerald-600 font-semibold">
                                <span>Descuento:</span>
                                <span>- {{ formatearMoneda(pedidoData.descuento) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Impuesto (8% INC):</span>
                                <span>{{ formatearMoneda(pedidoData.impuestos) }}</span>
                            </div>
                            <div v-if="pedidoData.propina > 0" class="flex justify-between text-emerald-700 font-semibold">
                                <span>Propina voluntaria:</span>
                                <span>+ {{ formatearMoneda(pedidoData.propina) }}</span>
                            </div>
                            <div class="pt-3 border-t border-stone-200 flex justify-between text-sm font-black text-stone-900">
                                <span>Total:</span>
                                <span class="text-lg text-orange-600">{{ formatearMoneda(pedidoData.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="pedidoData.direccion_entrega" class="bg-stone-50 rounded-3xl border border-stone-200 p-6 text-xs text-stone-700">
                        <span class="font-bold uppercase tracking-wider text-stone-500 block mb-1">Dirección de entrega</span>
                        <p class="font-bold text-stone-900">{{ pedidoData.direccion_entrega }}</p>
                    </div>
                </div>
            </div>

            <!-- Modal para Editar Notas (Ventana de 5 min) -->
            <div v-if="mostrarModalNotas" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-base font-bold text-stone-900 mb-2">Editar notas de preparación</h3>
                    <p class="text-xs text-stone-500 mb-4">Puedes ajustar instrucciones para cocina mientras el pedido permanezca en estado pendiente.</p>
                    <textarea
                        v-model="nuevasNotas"
                        rows="3"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-orange-500"
                    ></textarea>
                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="mostrarModalNotas = false"
                            class="px-4 py-2 text-xs font-bold text-stone-600 hover:bg-stone-100 rounded-xl"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="guardarNotas"
                            class="px-4 py-2 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-xl"
                        >
                            Guardar notas
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal para Solicitar Cancelación -->
            <div v-if="mostrarModalCancelar" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-base font-bold text-stone-900 mb-2">Solicitar cancelación de pedido</h3>
                    <p class="text-xs text-stone-500 mb-4">Indica a administración el motivo por el cual deseas cancelar tu orden.</p>
                    <textarea
                        v-model="motivoCancelacion"
                        rows="3"
                        placeholder="Ej: Cambio de planes, tiempo de espera imprevisto..."
                        class="w-full px-3 py-2 text-sm rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-rose-500"
                    ></textarea>
                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="mostrarModalCancelar = false"
                            class="px-4 py-2 text-xs font-bold text-stone-600 hover:bg-stone-100 rounded-xl"
                        >
                            Volver
                        </button>
                        <button
                            type="button"
                            @click="enviarCancelacion"
                            class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl"
                        >
                            Enviar solicitud
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ClienteLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import ClienteLayout from '@/Layouts/ClienteLayout.vue';

const props = defineProps({
    pedido: {
        type: Object,
        required: true,
    },
});

const pedidoData = ref({ ...props.pedido });
const mostrarModalNotas = ref(false);
const mostrarModalCancelar = ref(false);
const nuevasNotas = ref(props.pedido.notas || '');
const motivoCancelacion = ref('');

function formatearMoneda(valor) {
    return '$ ' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function guardarNotas() {
    router.put(`/cliente/pedidos/${pedidoData.value.id}/notas`, {
        notas: nuevasNotas.value,
    }, {
        onSuccess: () => {
            mostrarModalNotas.value = false;
            pedidoData.value.notas = nuevasNotas.value;
        },
    });
}

function enviarCancelacion() {
    if (!motivoCancelacion.value || motivoCancelacion.value.trim().length < 5) {
        alert('Por favor ingresa un motivo detallado (mínimo 5 caracteres).');
        return;
    }

    router.post(`/cliente/pedidos/${pedidoData.value.id}/cancelar`, {
        motivo: motivoCancelacion.value,
    }, {
        onSuccess: () => {
            mostrarModalCancelar.value = false;
            pedidoData.value.solicito_cancelacion = true;
        },
    });
}

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel(`menu.${pedidoData.value.sucursal_id}`)
            .listen('.PedidoActualizado', (e) => {
                if (e.pedidoData && e.pedidoData.id === pedidoData.value.id) {
                    pedidoData.value.estado = e.pedidoData.estado;
                    pedidoData.value.estado_etiqueta = e.pedidoData.estado_etiqueta;
                    pedidoData.value.etiqueta_cliente = e.pedidoData.etiqueta_cliente;
                    pedidoData.value.solicito_cancelacion = e.pedidoData.solicito_cancelacion;
                }
            });
    }
});
</script>
