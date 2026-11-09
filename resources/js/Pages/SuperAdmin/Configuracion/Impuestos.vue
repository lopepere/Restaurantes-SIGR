<template>
    <SuperAdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-stone-900">Configuración Tributaria e Impuestos</h1>
                <p class="text-xs text-stone-500">Parámetros de IVA, Impoconsumo (INC) y régimen fiscal aplicable a la facturación de las sucursales.</p>
            </div>

            <!-- Formulario de impuestos -->
            <form @submit.prevent="guardar" class="bg-white rounded-2xl border border-stone-200 shadow-xs p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Impuesto al Consumo (INC) -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase text-stone-600 tracking-wider">
                            Impuesto Nacional al Consumo (INC %)
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                v-model.number="form.inc_porcentaje"
                                min="0"
                                max="100"
                                step="0.5"
                                class="w-full text-sm border border-stone-300 rounded-xl px-3 py-2 pr-8 bg-stone-50 focus:bg-white focus:ring-2 focus:ring-amber-500 transition"
                            />
                            <span class="absolute right-3 top-2.5 text-xs text-stone-400 font-bold">%</span>
                        </div>
                        <p class="text-xs text-stone-400">Tarifa general del 8% INC aplicable al expendio de comidas y bebidas en Colombia.</p>
                    </div>

                    <!-- Impuesto sobre las Ventas (IVA) -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase text-stone-600 tracking-wider">
                            Impuesto al Valor Agregado (IVA %)
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                v-model.number="form.iva_porcentaje"
                                min="0"
                                max="100"
                                step="0.5"
                                class="w-full text-sm border border-stone-300 rounded-xl px-3 py-2 pr-8 bg-stone-50 focus:bg-white focus:ring-2 focus:ring-amber-500 transition"
                            />
                            <span class="absolute right-3 top-2.5 text-xs text-stone-400 font-bold">%</span>
                        </div>
                        <p class="text-xs text-stone-400">Tarifa general del 19% IVA para productos gravados con tarifa general.</p>
                    </div>

                    <!-- Régimen Tributario -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase text-stone-600 tracking-wider">
                            Régimen Tributario de la Empresa
                        </label>
                        <select
                            v-model="form.regimen"
                            class="w-full text-sm border border-stone-300 rounded-xl px-3 py-2 bg-stone-50 focus:bg-white focus:ring-2 focus:ring-amber-500 transition"
                        >
                            <option value="Responsable de IVA / INC">Responsable de IVA / INC (Régimen Común)</option>
                            <option value="No Responsable de IVA">No Responsable de IVA (Régimen Simplificado)</option>
                            <option value="Régimen Simple de Tributación (RST)">Régimen Simple de Tributación (RST)</option>
                        </select>
                        <p class="text-xs text-stone-400">Figura tributaria que se refleja en los Comprobantes de Venta FAC (SC-03).</p>
                    </div>

                    <!-- Modalidad de precios en catálogo -->
                    <div class="space-y-2 flex flex-col justify-center">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.precios_con_impuesto"
                                class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-stone-300"
                            />
                            <div>
                                <span class="text-sm font-medium text-stone-800">Precios de venta al público incluyen impuestos</span>
                                <p class="text-xs text-stone-400">El menú muestra el valor total al cliente y desglosa el impuesto en el ticket.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-stone-200 flex justify-end">
                    <button
                        type="submit"
                        :disabled="guardando"
                        class="bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-semibold text-sm px-6 py-2.5 rounded-xl shadow-xs transition flex items-center space-x-2"
                    >
                        <span>⚖️</span>
                        <span>{{ guardando ? 'Guardando...' : 'Actualizar Impuestos' }}</span>
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
    impuestos: {
        type: Object,
        required: true,
    },
});

const guardando = ref(false);

const form = reactive({
    inc_porcentaje: props.impuestos.inc_porcentaje,
    iva_porcentaje: props.impuestos.iva_porcentaje,
    regimen: props.impuestos.regimen,
    precios_con_impuesto: props.impuestos.precios_con_impuesto,
});

function guardar() {
    guardando.value = true;
    router.put('/superadmin/configuracion', {
        parametros: {
            'impuestos.inc_porcentaje': form.inc_porcentaje,
            'impuestos.iva_porcentaje': form.iva_porcentaje,
            'impuestos.regimen': form.regimen,
            'impuestos.precios_con_impuesto': form.precios_con_impuesto,
        },
    }, {
        preserveScroll: true,
        onFinish: () => {
            guardando.value = false;
        },
    });
}
</script>
