<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900">Tablero Operativo de Sucursal</h1>
                    <p class="text-xs text-stone-500">Métricas del día y monitoreo de turnos.</p>
                </div>
            </div>

            <!-- Indicadores del día -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Ventas Hoy</span>
                    <p class="text-xl sm:text-2xl font-black text-stone-900 mt-2">$ {{ indicadores.ventas_hoy }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Pedidos Hoy</span>
                    <p class="text-xl sm:text-2xl font-black text-stone-900 mt-2">{{ indicadores.pedidos_hoy }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Reservas Hoy</span>
                    <p class="text-xl sm:text-2xl font-black text-stone-900 mt-2">{{ indicadores.reservas_hoy }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Cancelaciones</span>
                    <p class="text-xl sm:text-2xl font-black text-orange-600 mt-2">{{ indicadores.cancelaciones_pendientes }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    indicadores: { type: Object, required: true },
});
</script>
