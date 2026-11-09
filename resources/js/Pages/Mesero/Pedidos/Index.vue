<template>
    <MeseroLayout>
        <div class="space-y-6">
            <!-- Encabezado de Gestión de Comandas -->
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-stone-200 pb-5">
                <div>
                    <h1 class="text-2xl font-black text-stone-900 tracking-tight">Comandas y Pedidos</h1>
                    <p class="text-sm text-stone-600 mt-1">Gestión operativa en sala: toma de comandas, seguimiento y control de mesas.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        href="/mesero/pedidos/crear"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm rounded-xl shadow-xs transition"
                    >
                        <span>📝 Nueva Comanda</span>
                    </a>
                </div>
            </div>

            <!-- Listado de Comandas -->
            <div v-if="pedidos.data.length === 0" class="text-center py-16 bg-white rounded-3xl border border-stone-200 p-8">
                <div class="text-4xl mb-3">🍽️</div>
                <h3 class="text-lg font-bold text-stone-900">No hay comandas activas en la sucursal</h3>
                <p class="text-stone-500 text-sm max-w-sm mx-auto mt-1 mb-6">Inicia el servicio en mesa tomando la primera orden del turno.</p>
                <a
                    href="/mesero/pedidos/crear"
                    class="px-5 py-2.5 bg-orange-600 text-white font-bold text-sm rounded-xl shadow-xs hover:bg-orange-700 transition"
                >
                    Tomar Comanda
                </a>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="pedido in pedidos.data"
                    :key="pedido.id"
                    :class="[
                        'rounded-2xl border p-5 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs',
                        pedido.es_propio ? 'bg-orange-50/40 border-orange-300 ring-1 ring-orange-200' : 'bg-white border-stone-200'
                    ]"
                >
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-black text-base text-stone-900">{{ pedido.numero }}</span>
                            <span v-if="pedido.es_propio" class="px-2 py-0.5 rounded-full text-xs font-black bg-orange-600 text-white">
                                Mi comanda
                            </span>
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold', matchBadgeClass(pedido.estado)]">
                                {{ pedido.estado_etiqueta }}
                            </span>
                            <span v-if="pedido.mesa" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-stone-100 text-stone-800 border border-stone-300">
                                📍 {{ pedido.mesa }}
                            </span>
                            <span v-else class="px-2 py-0.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-600">
                                {{ pedido.tipo_etiqueta }}
                            </span>
                            <span v-if="pedido.solicito_cancelacion" class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                Cancelación solicitada
                            </span>
                        </div>
                        <p class="text-xs text-stone-500">
                            Cliente: <span class="font-semibold text-stone-800">{{ pedido.cliente }}</span> •
                            {{ pedido.items_count }} producto(s) • {{ pedido.created_at }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4 sm:text-right">
                        <div>
                            <span class="text-xs text-stone-500">Total</span>
                            <p class="text-lg font-black text-stone-900">{{ formatearMoneda(pedido.total) }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Botón de cancelación de comanda si está activa -->
                            <button
                                v-if="!pedido.solicito_cancelacion && pedido.estado !== 'pagado' && pedido.estado !== 'cancelado'"
                                type="button"
                                @click="abrirCancelacion(pedido)"
                                class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 transition cursor-pointer"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Paginación -->
                <div v-if="pedidos.links.length > 3" class="flex justify-center gap-2 pt-4">
                    <template v-for="(link, i) in pedidos.links" :key="i">
                        <a
                            v-if="link.url"
                            :href="link.url"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', link.active ? 'bg-orange-600 text-white' : 'bg-white border border-stone-200 text-stone-700 hover:bg-stone-50']"
                            v-html="link.label"
                        ></a>
                    </template>
                </div>
            </div>

            <!-- Modal de Solicitud de Cancelación para Mesero -->
            <div v-if="mostrarModalCancelar" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-base font-bold text-stone-900 mb-2">Solicitar cancelación de comanda</h3>
                    <p class="text-xs text-stone-500 mb-4">Ingresa el motivo que se enviará al administrador para autorizar la cancelación de {{ pedidoACancelar?.numero }}.</p>
                    <textarea
                        v-model="motivoCancelacion"
                        rows="3"
                        placeholder="Ej: El cliente se retiró de la mesa / duplicado..."
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
                            Confirmar Solicitud
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MeseroLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import MeseroLayout from '@/Layouts/MeseroLayout.vue';

const props = defineProps({
    pedidos: {
        type: Object,
        required: true,
    },
    sucursal_id: {
        type: Number,
        required: true,
    },
});

const mostrarModalCancelar = ref(false);
const pedidoACancelar = ref(null);
const motivoCancelacion = ref('');

function matchBadgeClass(estado) {
    switch (estado) {
        case 'pendiente':
            return 'bg-amber-100 text-amber-800 border border-amber-200';
        case 'confirmado':
            return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'en_preparacion':
            return 'bg-orange-100 text-orange-800 border border-orange-200';
        case 'listo':
            return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        case 'entregado':
        case 'pagado':
            return 'bg-stone-800 text-white';
        case 'cancelacion_solicitada':
        case 'cancelado':
            return 'bg-rose-100 text-rose-800 border border-rose-200';
        default:
            return 'bg-stone-100 text-stone-800';
    }
}

function formatearMoneda(valor) {
    return '$ ' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function abrirCancelacion(pedido) {
    pedidoACancelar.value = pedido;
    motivoCancelacion.value = '';
    mostrarModalCancelar.value = true;
}

function enviarCancelacion() {
    if (!motivoCancelacion.value || motivoCancelacion.value.trim().length < 5) {
        alert('Por favor describe el motivo de la cancelación.');
        return;
    }

    router.post(`/mesero/pedidos/${pedidoACancelar.value.id}/cancelar`, {
        motivo: motivoCancelacion.value,
    }, {
        onSuccess: () => {
            mostrarModalCancelar.value = false;
        },
    });
}
</script>
