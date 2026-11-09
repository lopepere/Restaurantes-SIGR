<template>
    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-stone-900">Bitácora de Auditoría Inmutable</h1>
                <p class="text-xs text-stone-500">
                    Registro de solo inserción con sello de tiempo en microsegundos (DE-12, RN-GEN-06, CA-19). Sin opciones de modificación o borrado.
                </p>
            </div>

            <!-- Tabla de sólo lectura (Sin botones de editar ni eliminar) -->
            <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-xs">
                <table class="min-w-full divide-y divide-stone-200 text-left text-xs">
                    <thead class="bg-stone-50 text-stone-600 uppercase font-bold">
                        <tr>
                            <th class="px-6 py-3">Fecha y Hora (Exacta)</th>
                            <th class="px-6 py-3">Módulo</th>
                            <th class="px-6 py-3">Acción</th>
                            <th class="px-6 py-3">Usuario Responsable</th>
                            <th class="px-6 py-3">Detalle de Cambios</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        <tr v-for="actividad in actividades.data" :key="actividad.id" class="hover:bg-stone-50">
                            <td class="px-6 py-4 font-mono text-stone-600 whitespace-nowrap">{{ actividad.created_at }}</td>
                            <td class="px-6 py-4 font-semibold text-stone-900 uppercase text-[10px]">{{ actividad.log_name }}</td>
                            <td class="px-6 py-4">
                                <span class="bg-stone-100 text-stone-800 px-2 py-0.5 rounded-md font-mono text-[10px]">
                                    {{ actividad.description }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-stone-700 font-medium">
                                {{ actividad.causer?.nombre || 'Sistema' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-[11px] text-stone-600 max-w-xs truncate">
                                {{ JSON.stringify(actividad.properties) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    actividades: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
});
</script>
