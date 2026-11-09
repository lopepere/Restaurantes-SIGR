<template>
    <SuperAdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-stone-900">Panel Maestro — Super Administrador</h1>
                <p class="text-xs text-stone-500">Supervisión integral de todas las sucursales y configuración del sistema.</p>
            </div>

            <!-- Tarjetas de resumen -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Sucursales</span>
                    <p class="text-2xl font-black text-amber-600 mt-2">{{ totalSucursales }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Administradores</span>
                    <p class="text-2xl font-black text-stone-900 mt-2">{{ totalAdministradores }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Meseros</span>
                    <p class="text-2xl font-black text-stone-900 mt-2">{{ totalMeseros }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                    <span class="text-xs font-semibold uppercase text-stone-500">Clientes</span>
                    <p class="text-2xl font-black text-stone-900 mt-2">{{ totalClientes }}</p>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

defineProps({
    totalSucursales: { type: Number, required: true },
    totalAdministradores: { type: Number, required: true },
    totalMeseros: { type: Number, required: true },
    totalClientes: { type: Number, required: true },
});
</script>
