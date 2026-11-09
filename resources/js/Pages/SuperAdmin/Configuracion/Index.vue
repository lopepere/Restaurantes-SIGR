<template>
    <SuperAdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-stone-900">Parámetros Globales del Sistema</h1>
                <p class="text-xs text-stone-500">Configuración operativa transversal para todas las sucursales del restaurante.</p>
            </div>

            <!-- Formulario de parámetros -->
            <form @submit.prevent="guardar" class="bg-white rounded-2xl border border-stone-200 shadow-xs p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Modo de Inventario -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase text-stone-600 tracking-wider">
                            Régimen de Inventario
                        </label>
                        <select
                            v-model="form.modo_inventario"
                            class="w-full text-sm border border-stone-300 rounded-xl px-3 py-2 bg-stone-50 focus:bg-white focus:ring-2 focus:ring-amber-500 transition"
                        >
                            <option value="AUTONOMO">AUTÓNOMO (Descuento directo por plato en menú)</option>
                            <option value="DEPENDIENTE">DEPENDIENTE (Descuento por recetas e insumos)</option>
                        </select>
                        <p class="text-xs text-stone-400">Define si los platillos descuentan stock individualmente o dependen de recetas.</p>
                    </div>

                    <!-- Ventana de edición de pedidos -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase text-stone-600 tracking-wider">
                            Ventana de Edición de Pedidos (Minutos)
                        </label>
                        <input
                            type="number"
                            v-model.number="form.ventana_edicion"
                            min="1"
                            max="30"
                            class="w-full text-sm border border-stone-300 rounded-xl px-3 py-2 bg-stone-50 focus:bg-white focus:ring-2 focus:ring-amber-500 transition"
                        />
                        <p class="text-xs text-stone-400">Tiempo máximo en minutos para que un cliente modifique notas tras ordenar (DE-03).</p>
                    </div>

                    <!-- Duración de Reservas -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase text-stone-600 tracking-wider">
                            Duración Predeterminada de Reserva (Minutos)
                        </label>
                        <input
                            type="number"
                            v-model.number="form.reserva_duracion_minutos"
                            min="30"
                            step="15"
                            max="240"
                            class="w-full text-sm border border-stone-300 rounded-xl px-3 py-2 bg-stone-50 focus:bg-white focus:ring-2 focus:ring-amber-500 transition"
                        />
                        <p class="text-xs text-stone-400">Tiempo estimado que permanece una mesa ocupada por una reserva confirmada.</p>
                    </div>

                    <!-- Opciones booleanas -->
                    <div class="space-y-4 pt-2">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.reserva_aprobacion_automatica"
                                class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-stone-300"
                            />
                            <span class="text-sm font-medium text-stone-800">Aprobación automática de reservas en franjas disponibles</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.cancelacion_automatica"
                                class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-stone-300"
                            />
                            <span class="text-sm font-medium text-stone-800">Permitir auto-cancelación inmediata de pedidos en PENDIENTE</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.ocultar_agotados"
                                class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-stone-300"
                            />
                            <span class="text-sm font-medium text-stone-800">Ocultar productos agotados del menú (en lugar de mostrarlos en gris)</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.propina_preseleccionar"
                                class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-stone-300"
                            />
                            <span class="text-sm font-medium text-stone-800">Preseleccionar sugerencia de propina voluntaria al pagar (SC-02)</span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-stone-200 flex justify-end">
                    <button
                        type="submit"
                        :disabled="guardando"
                        class="bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-semibold text-sm px-6 py-2.5 rounded-xl shadow-xs transition flex items-center space-x-2"
                    >
                        <span>💾</span>
                        <span>{{ guardando ? 'Guardando...' : 'Guardar Parámetros' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    configuraciones: {
        type: Object,
        required: true,
    },
});

const guardando = ref(false);

const form = reactive({
    modo_inventario: props.configuraciones.modo_inventario,
    ventana_edicion: props.configuraciones.ventana_edicion,
    cancelacion_automatica: props.configuraciones.cancelacion_automatica,
    ocultar_agotados: props.configuraciones.ocultar_agotados,
    propina_preseleccionar: props.configuraciones.propina_preseleccionar,
    reserva_duracion_minutos: props.configuraciones.reserva_duracion_minutos,
    reserva_aprobacion_automatica: props.configuraciones.reserva_aprobacion_automatica,
});

function guardar() {
    guardando.value = true;
    router.put('/superadmin/configuracion', {
        parametros: {
            modo_inventario: form.modo_inventario,
            ventana_edicion: form.ventana_edicion,
            cancelacion_automatica: form.cancelacion_automatica,
            ocultar_agotados: form.ocultar_agotados,
            'propina.preseleccionar': form.propina_preseleccionar,
            'reserva.duracion_minutos': form.reserva_duracion_minutos,
            'reserva.aprobacion_automatica': form.reserva_aprobacion_automatica,
        },
    }, {
        preserveScroll: true,
        onFinish: () => {
            guardando.value = false;
        },
    });
}
</script>

