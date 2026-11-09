<template>
    <SuperAdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900">Gestión de Sucursales</h1>
                    <p class="text-xs text-stone-500">Administra las sedes físicas de tu restaurante (RF-CFG-01, RN-GEN-10).</p>
                </div>
                <button
                    type="button"
                    @click="mostrarModal = true"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-xs transition"
                >
                    ➕ Registrar Sucursal
                </button>
            </div>

            <!-- Listado -->
            <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-xs">
                <table class="min-w-full divide-y divide-stone-200 text-left text-xs">
                    <thead class="bg-stone-50 text-stone-600 uppercase font-bold">
                        <tr>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Dirección</th>
                            <th class="px-6 py-3">Teléfono</th>
                            <th class="px-6 py-3">Zona Horaria</th>
                            <th class="px-6 py-3">Moneda</th>
                            <th class="px-6 py-3">Modalidades Pedido</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        <tr v-for="sucursal in sucursales.data" :key="sucursal.id" class="hover:bg-stone-50">
                            <td class="px-6 py-4 font-semibold text-stone-900">{{ sucursal.nombre }}</td>
                            <td class="px-6 py-4 text-stone-600">{{ sucursal.direccion }}</td>
                            <td class="px-6 py-4 text-stone-600">{{ sucursal.telefono }}</td>
                            <td class="px-6 py-4 font-mono text-stone-500">{{ sucursal.zona_horaria }}</td>
                            <td class="px-6 py-4 font-bold text-stone-700">{{ sucursal.moneda }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <button
                                        type="button"
                                        @click="alternarModalidad(sucursal.id, 'domicilio')"
                                        :class="sucursal.acepta_domicilios !== false ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-stone-100 text-stone-400 border-stone-200 line-through'"
                                        class="px-2 py-0.5 rounded-md border text-[11px] font-semibold transition hover:scale-105"
                                        title="Alternar Domicilios"
                                    >
                                        🛵 Domicilio
                                    </button>
                                    <button
                                        type="button"
                                        @click="alternarModalidad(sucursal.id, 'para_llevar')"
                                        :class="sucursal.acepta_para_llevar !== false ? 'bg-blue-100 text-blue-800 border-blue-300' : 'bg-stone-100 text-stone-400 border-stone-200 line-through'"
                                        class="px-2 py-0.5 rounded-md border text-[11px] font-semibold transition hover:scale-105"
                                        title="Alternar Para Llevar"
                                    >
                                        🥡 Llevar
                                    </button>
                                    <button
                                        type="button"
                                        @click="alternarModalidad(sucursal.id, 'en_sitio')"
                                        :class="sucursal.acepta_en_sitio !== false ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-stone-100 text-stone-400 border-stone-200 line-through'"
                                        class="px-2 py-0.5 rounded-md border text-[11px] font-semibold transition hover:scale-105"
                                        title="Alternar En Sitio"
                                    >
                                        🍽️ En Sitio
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="sucursal.activa ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-600'"
                                    class="px-2 py-0.5 rounded-full font-semibold text-[10px]"
                                >
                                    {{ sucursal.activa ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    type="button"
                                    @click="eliminar(sucursal.id)"
                                    class="text-rose-600 hover:text-rose-800 font-bold"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal Nueva Sucursal -->
            <div v-if="mostrarModal" class="fixed inset-0 z-50 bg-stone-900/50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
                    <h3 class="text-lg font-bold text-stone-900">Registrar Nueva Sucursal</h3>
                    <form @submit.prevent="guardar" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Nombre de la Sede *</label>
                            <input v-model="form.nombre" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" placeholder="Sede Poblado" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Dirección *</label>
                            <input v-model="form.direccion" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Teléfono (E.164) *</label>
                            <input v-model="form.telefono" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" placeholder="+573001234567" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-stone-700">Zona Horaria *</label>
                                <input v-model="form.zona_horaria" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-stone-700">Moneda *</label>
                                <input v-model="form.moneda" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-stone-700 mb-1">Modalidades de Pedido Habilitadas</label>
                            <div class="flex items-center gap-4 text-xs text-stone-700">
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" v-model="form.acepta_domicilios" class="rounded text-amber-600 focus:ring-amber-500" />
                                    🛵 Domicilios
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" v-model="form.acepta_para_llevar" class="rounded text-blue-600 focus:ring-blue-500" />
                                    🥡 Para Llevar
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="checkbox" v-model="form.acepta_en_sitio" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                    🍽️ En Sitio
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2 pt-2">
                            <button type="button" @click="mostrarModal = false" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancelar</button>
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-xs font-bold">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

defineProps({
    sucursales: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
});

const mostrarModal = ref(false);

const form = useForm({
    nombre: '',
    slug: '',
    direccion: '',
    telefono: '',
    zona_horaria: 'America/Bogota',
    moneda: 'COP',
    idioma: 'es',
    acepta_domicilios: true,
    acepta_para_llevar: true,
    acepta_en_sitio: true,
});

function alternarModalidad(sucursalId, modalidad) {
    router.post(`/superadmin/sucursales/${sucursalId}/modalidad`, { modalidad }, {
        preserveScroll: true,
    });
}

function guardar() {
    form.post('/superadmin/sucursales', {
        onSuccess: () => {
            mostrarModal.value = false;
            form.reset();
        },
    });
}

function eliminar(id) {
    if (confirm('¿Deseas eliminar lógicamente esta sucursal?')) {
        router.delete(`/superadmin/sucursales/${id}`);
    }
}
</script>
