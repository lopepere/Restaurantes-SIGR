<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900">Papelera del Sistema</h1>
                    <p class="text-xs text-stone-500">
                        Registros eliminados lógicamente disponibles para restauración (DE-11, RN-GEN-05, CA-21).
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-xs">
                <table class="min-w-full divide-y divide-stone-200 text-left text-xs">
                    <thead class="bg-stone-50 text-stone-600 uppercase font-bold">
                        <tr>
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Descripción / Nombre</th>
                            <th class="px-6 py-3">Fecha de Eliminación</th>
                            <th class="px-6 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        <tr v-for="item in registros.data" :key="item.id" class="hover:bg-stone-50">
                            <td class="px-6 py-4 font-mono text-stone-500">#{{ item.id }}</td>
                            <td class="px-6 py-4 font-semibold text-stone-900">{{ item.nombre || item.name || 'Registro' }}</td>
                            <td class="px-6 py-4 font-mono text-stone-600">{{ item.deleted_at }}</td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    type="button"
                                    @click="restaurar(item.id)"
                                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-xs transition"
                                >
                                    ♻️ Restaurar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="registros.data.length === 0">
                            <td colspan="4" class="px-6 py-8 text-center text-xs text-stone-500">
                                La papelera de este módulo está vacía.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    modulo: { type: String, required: true },
    registros: { type: Object, required: true },
});

function restaurar(id) {
    if (confirm('¿Deseas restaurar este registro a su estado activo?')) {
        router.post(`/admin/papelera/${props.modulo}/${id}/restaurar`);
    }
}
</script>
