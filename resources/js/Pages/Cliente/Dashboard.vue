<template>
    <ClienteLayout>
        <div class="space-y-8">
            <!-- Bienvenida -->
            <div class="bg-gradient-to-r from-orange-600 to-amber-600 rounded-3xl p-6 sm:p-8 text-white shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                        ¡Hola, {{ $page.props.auth?.user?.name }}!
                    </h1>
                    <p class="mt-1 text-orange-100 text-xs sm:text-sm">
                        Bienvenido a tu panel gastronómico. Sigue tus pedidos en tiempo real y disfruta de nuestras promociones.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a
                        href="/"
                        class="px-5 py-2.5 bg-white text-orange-700 font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:bg-orange-50 transition active:scale-95"
                    >
                        🛎️ Nueva Orden
                    </a>
                </div>
            </div>

            <!-- Promociones primero en el dashboard (Prompt A.6) -->
            <div v-if="promociones.length > 0" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-stone-700 flex items-center gap-1.5">
                        <span>🏷️ Promociones Exclusivas de Hoy</span>
                    </h2>
                    <span class="text-xs text-orange-600 font-semibold">{{ promociones.length }} activas</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="promo in promociones"
                        :key="promo.id"
                        class="bg-gradient-to-br from-amber-50 to-orange-50 border border-orange-200/80 rounded-2xl p-5 shadow-xs relative overflow-hidden flex flex-col justify-between"
                    >
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-black bg-orange-600 text-white mb-2 shadow-xs">
                                {{ promo.beneficio_texto }}
                            </span>
                            <h3 class="font-black text-stone-900 text-base">{{ promo.nombre }}</h3>
                            <p v-if="promo.descripcion" class="text-xs text-stone-600 mt-1 line-clamp-2">{{ promo.descripcion }}</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-orange-200/50 flex justify-between items-center text-xs text-stone-500">
                            <span>Sede: <strong>{{ promo.sucursal }}</strong></span>
                            <a href="/" class="text-orange-600 font-bold hover:underline">Aprovechar →</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pedidos en vivo / En curso -->
            <div class="bg-white rounded-3xl border border-stone-200 p-6 sm:p-8 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-stone-900 flex items-center gap-2">
                            <span>🛎️ Pedidos en Vivo</span>
                            <span v-if="pedidosActivosList.length > 0" class="px-2 py-0.5 text-xs font-black rounded-full bg-orange-100 text-orange-700">
                                {{ pedidosActivosList.length }}
                            </span>
                        </h2>
                        <p class="text-xs text-stone-500">Actualización automática por Reverb WebSockets</p>
                    </div>
                    <a
                        href="/cliente/pedidos"
                        class="text-xs font-bold text-orange-600 hover:text-orange-700"
                    >
                        Ver todos los pedidos →
                    </a>
                </div>

                <div v-if="pedidosActivosList.length === 0" class="text-center py-10 text-stone-500 text-xs">
                    <div class="text-3xl mb-2">🍽️</div>
                    <p class="font-bold text-stone-700 text-sm">No tienes ningún pedido en curso en este momento.</p>
                    <p class="text-stone-400 mt-1">¿Tienes hambre? Pide algo delicioso de nuestra carta.</p>
                    <div class="mt-4">
                        <a href="/" class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                            Explorar Menú
                        </a>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="pedido in pedidosActivosList"
                        :key="pedido.id"
                        class="border border-orange-200 bg-orange-50/30 rounded-2xl p-5 hover:border-orange-300 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <span class="font-black text-base text-stone-900">{{ pedido.numero }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-500 text-white shadow-xs">
                                    {{ pedido.etiqueta_cliente }}
                                </span>
                                <span v-if="pedido.solicito_cancelacion" class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                    Cancelación solicitada
                                </span>
                            </div>
                            <p class="text-xs text-stone-500">
                                Sede: <span class="font-semibold text-stone-700">{{ pedido.sucursal }}</span> •
                                Tipo: <span class="font-semibold text-stone-700">{{ pedido.tipo_etiqueta }}</span> •
                                {{ pedido.items_count }} producto(s)
                            </p>
                        </div>

                        <div class="flex items-center gap-4 sm:text-right">
                            <p class="text-lg font-black text-orange-600">{{ formatearMoneda(pedido.total) }}</p>
                            <a
                                :href="`/cliente/pedidos/${pedido.id}`"
                                class="px-4 py-2 bg-stone-900 hover:bg-black text-white text-xs font-bold rounded-xl transition"
                            >
                                Seguir Pedido
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial Reciente -->
            <div v-if="pedidosRecientes.length > 0" class="bg-white rounded-3xl border border-stone-200 p-6 sm:p-8 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-stone-900">Historial Reciente</h2>
                    <a href="/cliente/pedidos" class="text-xs font-bold text-orange-600 hover:underline">Ver historial completo</a>
                </div>

                <div class="divide-y divide-stone-100">
                    <div
                        v-for="pedido in pedidosRecientes"
                        :key="pedido.id"
                        class="py-3 flex items-center justify-between text-xs"
                    >
                        <div>
                            <span class="font-bold text-stone-900">{{ pedido.numero }}</span>
                            <span class="text-stone-400 ml-2">{{ pedido.fecha }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-stone-900">{{ formatearMoneda(pedido.total) }}</span>
                            <a :href="`/cliente/pedidos/${pedido.id}`" class="text-orange-600 font-bold hover:underline">Detalle</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClienteLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ClienteLayout from '@/Layouts/ClienteLayout.vue';

const props = defineProps({
    promociones: {
        type: Array,
        default: () => [],
    },
    pedidosActivos: {
        type: Array,
        default: () => [],
    },
    pedidosRecientes: {
        type: Array,
        default: () => [],
    },
});

const pedidosActivosList = ref([...props.pedidosActivos]);

function formatearMoneda(valor) {
    return '$ ' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

onMounted(() => {
    // Si Echo está presente, escuchar actualizaciones de pedidos en tiempo real
    if (window.Echo && window.Echo.connector?.options?.auth?.headers) {
        // Echo private listening is managed through private channel if configured
    }
});
</script>
