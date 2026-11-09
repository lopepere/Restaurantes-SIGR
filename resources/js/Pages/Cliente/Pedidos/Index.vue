<template>
    <ClienteLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-stone-200 pb-5">
                <div>
                    <h1 class="text-2xl font-black text-stone-900 tracking-tight">Mis Pedidos</h1>
                    <p class="text-sm text-stone-600 mt-1">Historial y seguimiento en tiempo real de tus órdenes gastronómicas.</p>
                </div>
                <a
                    href="/"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm rounded-xl shadow-sm transition"
                >
                    <span>🛎️ Hacer un Nuevo Pedido</span>
                </a>
            </div>

            <!-- Listado de Pedidos -->
            <div v-if="pedidos.data.length === 0" class="text-center py-16 bg-white rounded-3xl border border-stone-200 p-8">
                <div class="w-16 h-16 mx-auto mb-4 bg-orange-50 text-orange-600 rounded-full flex items-center justify-center text-3xl">
                    🛍️
                </div>
                <h3 class="text-lg font-bold text-stone-900">Aún no tienes pedidos registrados</h3>
                <p class="text-stone-500 text-sm max-w-sm mx-auto mt-1 mb-6">Explora nuestra carta, añade tus platos favoritos al carrito y disfruta del sabor en minutos.</p>
                <a
                    href="/"
                    class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm rounded-xl transition"
                >
                    Ver Menú del Restaurante
                </a>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="pedido in pedidos.data"
                    :key="pedido.id"
                    class="bg-white rounded-2xl border border-stone-200 shadow-xs hover:border-orange-300 transition p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-3">
                            <span class="text-base font-black text-stone-900 tracking-wide">{{ pedido.numero }}</span>
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold', matchBadgeClass(pedido.estado)]">
                                {{ pedido.etiqueta_cliente }}
                            </span>
                            <span v-if="pedido.solicito_cancelacion" class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                Cancelación en revisión
                            </span>
                        </div>
                        <p class="text-xs text-stone-500">
                            Sucursal: <span class="font-semibold text-stone-700">{{ pedido.sucursal }}</span> •
                            Modalidad: <span class="font-semibold text-stone-700">{{ pedido.tipo_etiqueta }}</span> •
                            Fecha: {{ pedido.created_at }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4 sm:text-right">
                        <div>
                            <span class="text-xs text-stone-500">{{ pedido.items_count }} producto(s)</span>
                            <p class="text-lg font-black text-orange-600">{{ formatearMoneda(pedido.total) }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="repetirPedido(pedido.id)"
                                :disabled="cargandoRepetir === pedido.id"
                                class="px-3.5 py-2 bg-stone-100 hover:bg-stone-200 text-stone-800 text-xs font-bold rounded-xl transition cursor-pointer"
                                title="Volver a pedir estos mismos platos"
                            >
                                <span v-if="cargandoRepetir === pedido.id">⏳</span>
                                <span v-else>🔄 Repetir</span>
                            </button>
                            <a
                                :href="`/cliente/pedidos/${pedido.id}`"
                                class="px-4 py-2 bg-stone-900 hover:bg-black text-white text-xs font-bold rounded-xl transition"
                            >
                                Ver Detalle
                            </a>
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
        </div>
    </ClienteLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ClienteLayout from '@/Layouts/ClienteLayout.vue';

const props = defineProps({
    pedidos: {
        type: Object,
        required: true,
    },
});

const cargandoRepetir = ref(null);

function matchBadgeClass(estado) {
    switch (estado) {
        case 'pendiente':
        case 'confirmado':
        case 'en_preparacion':
        case 'listo':
            return 'bg-orange-100 text-orange-800 border border-orange-200';
        case 'entregado':
        case 'pagado':
            return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
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

async function repetirPedido(pedidoId) {
    cargandoRepetir.value = pedidoId;
    try {
        const res = await fetch(`/cliente/pedidos/${pedidoId}/repetir`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.items_disponibles && data.items_disponibles.length > 0) {
            // Guardar en sessionStorage para abrir el carrito en la página de inicio
            sessionStorage.setItem('sigr_carrito_repetido', JSON.stringify(data.items_disponibles));
            alert(`¡Se han cargado ${data.items_disponibles.length} productos disponibles a tu carrito!`);
            window.location.href = '/';
        } else {
            alert('Los productos de este pedido ya no se encuentran disponibles actualmente.');
        }
    } catch (e) {
        alert('Ocurrió un error al intentar repetir el pedido.');
    } finally {
        cargandoRepetir.value = null;
    }
}
</script>
