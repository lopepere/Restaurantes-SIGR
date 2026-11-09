<template>
    <SuperAdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900">Gestión de Administradores</h1>
                    <p class="text-xs text-stone-500">Crea y asigna administradores a una o más sucursales (RF-USR-10).</p>
                </div>
                <button
                    type="button"
                    @click="mostrarModal = true"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-xs transition"
                >
                    ➕ Registrar Administrador
                </button>
            </div>

            <!-- Listado -->
            <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-xs">
                <table class="min-w-full divide-y divide-stone-200 text-left text-xs">
                    <thead class="bg-stone-50 text-stone-600 uppercase font-bold">
                        <tr>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Correo</th>
                            <th class="px-6 py-3">Teléfono</th>
                            <th class="px-6 py-3">Sucursales Asignadas</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200">
                        <tr v-for="admin in administradores.data" :key="admin.id" class="hover:bg-stone-50">
                            <td class="px-6 py-4 font-semibold text-stone-900">{{ admin.nombre }}</td>
                            <td class="px-6 py-4 text-stone-600">{{ admin.email }}</td>
                            <td class="px-6 py-4 text-stone-600">{{ admin.telefono }}</td>
                            <td class="px-6 py-4">
                                <span
                                    v-for="s in admin.sucursales"
                                    :key="s.id"
                                    class="inline-block bg-stone-100 text-stone-800 text-[10px] px-2 py-0.5 rounded-md mr-1 font-medium"
                                >
                                    {{ s.nombre }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="admin.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-600'"
                                    class="px-2 py-0.5 rounded-full font-semibold text-[10px]"
                                >
                                    {{ admin.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    type="button"
                                    @click="eliminar(admin.id)"
                                    class="text-rose-600 hover:text-rose-800 font-bold"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal Nuevo Administrador -->
            <div v-if="mostrarModal" class="fixed inset-0 z-50 bg-stone-900/50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4">
                    <h3 class="text-lg font-bold text-stone-900">Registrar Nuevo Administrador</h3>
                    <form @submit.prevent="guardar" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Nombre *</label>
                            <input v-model="form.nombre" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Correo *</label>
                            <input v-model="form.email" type="email" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Teléfono (E.164) *</label>
                            <input v-model="form.telefono" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Dirección *</label>
                            <input v-model="form.direccion" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Contraseña Inicial *</label>
                            <input v-model="form.password" type="password" required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Sucursal Asignada *</label>
                            <select v-model="form.sucursal_ids" multiple required class="w-full mt-1 border border-stone-300 rounded-lg px-3 py-2 text-xs">
                                <option v-for="s in sucursales" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                            </select>
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
    administradores: { type: Object, required: true },
    sucursales: { type: Array, required: true },
    filtros: { type: Object, default: () => ({}) },
});

const mostrarModal = ref(false);

const form = useForm({
    nombre: '',
    email: '',
    telefono: '',
    direccion: '',
    password: '',
    sucursal_ids: [],
});

function guardar() {
    form.post('/superadmin/administradores', {
        onSuccess: () => {
            mostrarModal.value = false;
            form.reset();
        },
    });
}

function eliminar(id) {
    if (confirm('¿Deseas eliminar lógicamente este administrador?')) {
        router.delete(`/superadmin/administradores/${id}`);
    }
}
</script>
