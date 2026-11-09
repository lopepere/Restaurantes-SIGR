<template>
    <MeseroLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-stone-200 pb-4">
                <div>
                    <h1 class="text-2xl font-black text-stone-900 tracking-tight">Panel de Operaciones — Mesero</h1>
                    <p class="text-xs text-stone-500">Toma de pedidos en sala, seguimiento de comandas y estado de mesas.</p>
                </div>
                <a
                    href="/mesero/pedidos/crear"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm rounded-xl shadow-xs transition"
                >
                    <span>📝 Tomar Comanda</span>
                </a>
            </div>

            <!-- Métricas rápidas de la jornada -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-6 rounded-3xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Mis Comandas Activas</span>
                        <p class="text-3xl font-black text-stone-900 mt-1">{{ pedidosPropios.length }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-2xl font-black">
                        📝
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Otras Órdenes Activas</span>
                        <p class="text-3xl font-black text-stone-900 mt-1">{{ pedidosSucursal.length }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl font-black">
                        🛎️
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-500">Mesas Ocupadas</span>
                        <p class="text-3xl font-black text-orange-600 mt-1">{{ mesasOcupadas }} / {{ totalMesas || 12 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-2xl font-black">
                        🍽️
                    </div>
                </div>
            </div>

            <!-- Comandas Propias en Curso -->
            <div class="bg-white rounded-3xl border border-stone-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-4">
                    <h2 class="text-base font-bold text-stone-900 flex items-center gap-2">
                        <span>⭐ Mis Comandas en Curso</span>
                    </h2>
                    <a href="/mesero/pedidos" class="text-xs font-bold text-orange-600 hover:underline">Ver todas →</a>
                </div>

                <div v-if="pedidosPropios.length === 0" class="py-8 text-center text-stone-400 text-xs">
                    No tienes comandas en preparación asignadas a tu usuario en este momento.
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="p in pedidosPropios"
                        :key="p.id"
                        class="p-4 rounded-2xl border border-orange-200 bg-orange-50/30 flex items-center justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-black text-sm text-stone-900">{{ p.numero }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-orange-500 text-white">
                                    {{ p.estado_etiqueta }}
                                </span>
                            </div>
                            <p class="text-xs text-stone-500 mt-1">
                                {{ p.mesa ? `📍 Mesa: ${p.mesa}` : p.tipo_etiqueta }} • {{ p.items_count }} producto(s)
                            </p>
                        </div>
                        <span class="font-black text-sm text-stone-900">{{ formatearMoneda(p.total) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </MeseroLayout>
</template>

<script setup>
import MeseroLayout from '@/Layouts/MeseroLayout.vue';

const props = defineProps({
    pedidosPropios: { type: Array, default: () => [] },
    pedidosSucursal: { type: Array, default: () => [] },
    mesasOcupadas: { type: Number, default: 0 },
    totalMesas: { type: Number, default: 0 },
    sucursal_id: { type: Number, default: 1 },
});

function formatearMoneda(valor) {
    return '$ ' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
