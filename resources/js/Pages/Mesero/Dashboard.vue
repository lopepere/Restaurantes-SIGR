<template>
    <MeseroLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-stone-900">Panel de Operaciones — Mesero</h1>
                    <p class="text-xs text-stone-500">Toma de pedidos en sitio, atención de mesas y domicilios.</p>
                </div>
            </div>

            <!-- Estado de mesas y pedidos rápidos -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Mis Pedidos en Curso</span>
                    <p class="text-2xl font-black text-stone-900 mt-2">{{ pedidosPropios.length }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Pedidos de la Sucursal</span>
                    <p class="text-2xl font-black text-stone-900 mt-2">{{ pedidosSucursal.length }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Mesas Activas</span>
                    <p class="text-2xl font-black text-orange-600 mt-2">12 Disponibles</p>
                </div>
            </div>
        </div>
    </MeseroLayout>
</template>

<script setup>
import MeseroLayout from '@/Layouts/MeseroLayout.vue';

defineProps({
    pedidosPropios: { type: Array, default: () => [] },
    pedidosSucursal: { type: Array, default: () => [] },
});
</script>
