<template>
    <ClienteLayout>
        <div class="space-y-8">
            <!-- Bienvenida -->
            <div class="bg-gradient-to-r from-orange-600 to-amber-600 rounded-2xl p-6 sm:p-8 text-white shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        ¡Hola, {{ $page.props.auth?.user?.name }}!
                    </h1>
                    <p class="mt-1 text-orange-100 text-xs sm:text-sm">
                        Bienvenido a tu panel de cliente. Revisa tus pedidos en curso y comprobantes de compra.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a
                        href="/#menu"
                        class="px-4 py-2 bg-white text-orange-700 font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:bg-orange-50 transition active:scale-95"
                    >
                        🍽️ Ordenar Ahora
                    </a>
                </div>
            </div>

            <!-- Pedidos en vivo -->
            <div class="bg-white rounded-2xl border border-stone-200 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-stone-900">Pedidos en Vivo</h2>
                    <span class="text-xs text-stone-500 font-medium">Actualización automática</span>
                </div>

                <div v-if="pedidosActivos.length === 0" class="text-center py-8 text-stone-500 text-xs">
                    <div class="text-3xl mb-2">🛎️</div>
                    No tienes ningún pedido activo en este momento.
                    <div class="mt-3">
                        <a href="/#menu" class="text-orange-600 font-bold hover:underline">Ver Menú y Ordenar</a>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="pedido in pedidosActivos"
                        :key="pedido.id"
                        class="border border-stone-200 rounded-xl p-4 flex items-center justify-between"
                    >
                        <div>
                            <span class="font-bold text-sm text-stone-900">#{{ pedido.numero }}</span>
                            <span class="ml-2 text-xs bg-orange-100 text-orange-800 font-semibold px-2 py-0.5 rounded-md">
                                {{ pedido.estado }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClienteLayout>
</template>

<script setup>
import ClienteLayout from '@/Layouts/ClienteLayout.vue';

defineProps({
    pedidosActivos: { type: Array, default: () => [] },
    promociones: { type: Array, default: () => [] },
});
</script>
