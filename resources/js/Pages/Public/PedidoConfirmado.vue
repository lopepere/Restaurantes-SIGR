<template>
    <PublicLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <!-- Encabezado de éxito -->
            <div class="bg-white rounded-3xl shadow-sm border border-stone-200 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-orange-600 to-amber-600 px-6 py-8 text-white text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 backdrop-blur-md mb-4 text-3xl">
                        🛎️
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">¡Pedido Recibido con Éxito!</h1>
                    <p class="text-orange-100 text-sm mt-1">
                        Tu orden <span class="font-bold text-white tracking-wider">{{ pedidoData.numero }}</span> está siendo procesada en cocina.
                    </p>
                </div>

                <!-- Estado del Pedido en Vivo -->
                <div class="p-6 sm:p-8 bg-orange-50/50 border-b border-stone-100">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-bold text-stone-500 uppercase tracking-wider">Estado actual</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="relative flex h-3 w-3">
                                    <span v-if="pedidoData.estado !== 'cancelado' && pedidoData.estado !== 'entregado'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                                    <span :class="['relative inline-flex rounded-full h-3 w-3', badgeColorClass]"></span>
                                </span>
                                <h3 class="text-xl font-black text-stone-900">{{ pedidoData.estado_etiqueta }}</h3>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-white border border-stone-200 text-stone-700">
                                    {{ pedidoData.etiqueta_cliente }}
                                </span>
                            </div>
                        </div>

                        <div class="text-sm text-stone-600 sm:text-right">
                            <p class="font-medium">Sucursal: <span class="font-bold text-stone-800">{{ pedidoData.sucursal.nombre }}</span></p>
                            <p class="text-xs text-stone-500">Hora: {{ pedidoData.fecha }}</p>
                        </div>
                    </div>
                </div>

                <!-- Detalles de entrega / servicio -->
                <div class="p-6 sm:p-8 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm bg-stone-50 rounded-2xl p-4 border border-stone-200/60">
                        <div>
                            <span class="text-xs font-semibold text-stone-500 uppercase">Modalidad</span>
                            <p class="font-bold text-stone-900 mt-0.5">{{ pedidoData.tipo_etiqueta }}</p>
                        </div>
                        <div v-if="pedidoData.direccion_entrega">
                            <span class="text-xs font-semibold text-stone-500 uppercase">Dirección de entrega</span>
                            <p class="font-bold text-stone-900 mt-0.5">{{ pedidoData.direccion_entrega }}</p>
                        </div>
                        <div v-if="pedidoData.nombre_contacto">
                            <span class="text-xs font-semibold text-stone-500 uppercase">Destinatario</span>
                            <p class="font-bold text-stone-900 mt-0.5">{{ pedidoData.nombre_contacto }}</p>
                        </div>
                        <div v-if="pedidoData.telefono_contacto">
                            <span class="text-xs font-semibold text-stone-500 uppercase">Teléfono de contacto</span>
                            <p class="font-bold text-stone-900 mt-0.5">{{ pedidoData.telefono_contacto }}</p>
                        </div>
                    </div>

                    <!-- Lista de productos -->
                    <div>
                        <h4 class="text-sm font-bold text-stone-800 uppercase tracking-wider mb-3">Productos solicitados</h4>
                        <div class="divide-y divide-stone-100 border border-stone-200 rounded-2xl overflow-hidden">
                            <div
                                v-for="item in pedidoData.items"
                                :key="item.id"
                                class="p-4 flex items-center justify-between hover:bg-stone-50 transition"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="w-7 h-7 flex items-center justify-center bg-orange-100 text-orange-700 font-bold text-xs rounded-lg">
                                        {{ item.cantidad }}x
                                    </span>
                                    <div>
                                        <p class="font-bold text-stone-900 text-sm">{{ item.nombre }}</p>
                                        <p v-if="item.notas" class="text-xs text-stone-500 italic">Nota: {{ item.notas }}</p>
                                    </div>
                                </div>
                                <span class="font-extrabold text-stone-900 text-sm">
                                    {{ formatearMoneda(item.total) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen financiero -->
                    <div class="space-y-2 pt-2 text-sm text-stone-600">
                        <div class="flex justify-between">
                            <span>Subtotal productos:</span>
                            <span>{{ formatearMoneda(pedidoData.subtotal) }}</span>
                        </div>
                        <div v-if="pedidoData.descuento > 0" class="flex justify-between text-emerald-600 font-semibold">
                            <span>Descuentos promocionales:</span>
                            <span>- {{ formatearMoneda(pedidoData.descuento) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Impuesto incluido (8% INC):</span>
                            <span>{{ formatearMoneda(pedidoData.impuestos) }}</span>
                        </div>
                        <div v-if="pedidoData.propina > 0" class="flex justify-between text-emerald-700 font-semibold">
                            <span>Propina voluntaria:</span>
                            <span>+ {{ formatearMoneda(pedidoData.propina) }}</span>
                        </div>
                        <div class="pt-3 border-t border-stone-200 flex justify-between text-base font-black text-stone-900">
                            <span>Total pagado o por pagar:</span>
                            <span class="text-xl text-orange-600 font-black">{{ formatearMoneda(pedidoData.total) }}</span>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="pt-6 border-t border-stone-100 flex flex-col sm:flex-row gap-3">
                        <a
                            :href="`/sucursal/${pedidoData.sucursal.slug}`"
                            class="flex-1 py-3 px-4 bg-stone-900 hover:bg-black text-white text-sm font-bold rounded-xl text-center transition cursor-pointer"
                        >
                            Volver a la carta del restaurante
                        </a>
                        <a
                            v-if="$page.props.auth?.user"
                            href="/cliente/pedidos"
                            class="py-3 px-6 bg-orange-100 hover:bg-orange-200 text-orange-800 text-sm font-bold rounded-xl text-center transition"
                        >
                            Ver en mis pedidos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    pedido: {
        type: Object,
        required: true,
    },
});

const pedidoData = ref({ ...props.pedido });

const badgeColorClass = computed(() => {
    return matchBadgeColor(pedidoData.value.estado);
});

function matchBadgeColor(estado) {
    switch (estado) {
        case 'pendiente':
            return 'bg-amber-500';
        case 'confirmado':
            return 'bg-blue-500';
        case 'en_preparacion':
            return 'bg-orange-500';
        case 'listo':
            return 'bg-emerald-500';
        case 'entregado':
        case 'pagado':
            return 'bg-stone-800';
        case 'cancelado':
            return 'bg-rose-500';
        default:
            return 'bg-amber-500';
    }
}

function formatearMoneda(valor) {
    return '$ ' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

onMounted(() => {
    // Si Echo está activo, escuchar cambios del pedido
    if (window.Echo) {
        window.Echo.channel(`menu.${pedidoData.value.sucursal.id}`)
            .listen('.PedidoActualizado', (e) => {
                if (e.pedidoData && e.pedidoData.id === pedidoData.value.id) {
                    pedidoData.value.estado = e.pedidoData.estado;
                    pedidoData.value.estado_etiqueta = e.pedidoData.estado_etiqueta;
                    pedidoData.value.etiqueta_cliente = e.pedidoData.etiqueta_cliente;
                }
            });
    }
});
</script>
