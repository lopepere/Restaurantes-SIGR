<template>
    <SuperAdminLayout>
        <div class="space-y-8">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-stone-900 tracking-tight flex items-center gap-2">
                        <span>⚖️</span>
                        <span>Configuración Tributaria e Impuestos</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Control de tarifas fiscales (INC, IVA), régimen de la empresa y catálogo dinámico de gravámenes aplicables al menú.
                    </p>
                </div>

                <button
                    type="button"
                    @click="abrirModalCrear"
                    class="bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-xs transition flex items-center gap-2"
                >
                    <span>➕</span>
                    <span>Nuevo Impuesto</span>
                </button>
            </div>

            <!-- Catálogo Dinámico de Impuestos (Item 1) -->
            <div class="bg-white rounded-3xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="p-6 border-b border-stone-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">Catálogo de Impuestos Disponibles</h2>
                        <p class="text-xs text-stone-500">Impuestos y gravámenes seleccionables al crear o editar productos y combos.</p>
                    </div>
                    <span class="text-xs font-bold text-stone-400 font-mono">{{ listaCatalogo.length }} configurados</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider border-b border-stone-100">
                            <tr>
                                <th class="px-6 py-3.5">Código</th>
                                <th class="px-6 py-3.5">Nombre del Impuesto</th>
                                <th class="px-6 py-3.5">Tarifa (%)</th>
                                <th class="px-6 py-3.5">Descripción</th>
                                <th class="px-6 py-3.5 text-center">Estado</th>
                                <th class="px-6 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 text-stone-700">
                            <tr v-for="(imp, idx) in listaCatalogo" :key="imp.id" class="hover:bg-stone-50/60 transition">
                                <td class="px-6 py-4 font-mono font-bold text-stone-900">
                                    <span class="px-2 py-1 rounded-md bg-stone-100 border border-stone-200">
                                        {{ imp.codigo }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-stone-900">
                                    {{ imp.nombre }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-black text-amber-600 font-mono">{{ imp.porcentaje }}%</span>
                                </td>
                                <td class="px-6 py-4 text-stone-500 max-w-xs truncate">
                                    {{ imp.descripcion || '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button
                                        type="button"
                                        @click="alternarEstado(idx)"
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[11px] font-bold transition cursor-pointer',
                                            imp.activo
                                                ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                                : 'bg-stone-200 text-stone-600 hover:bg-stone-300'
                                        ]"
                                    >
                                        {{ imp.activo ? '● Activo' : '○ Inactivo' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button
                                        type="button"
                                        @click="abrirModalEditar(idx)"
                                        class="px-2.5 py-1.5 rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold transition"
                                    >
                                        ✏️ Editar
                                    </button>
                                    <button
                                        type="button"
                                        @click="eliminarImpuesto(idx)"
                                        class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold transition"
                                    >
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Formulario de Parámetros Generales de Impuestos -->
            <form @submit.prevent="guardarParametros" class="bg-white rounded-3xl border border-stone-200 shadow-xs p-6 sm:p-8 space-y-6">
                <div>
                    <h2 class="text-base font-bold text-stone-900">Parámetros Tributarios Globales</h2>
                    <p class="text-xs text-stone-500">Configuración por defecto para facturación legal y Comprobantes de Venta FAC.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Impuesto al Consumo (INC) -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase text-stone-600 tracking-wider">
                            Impuesto Nacional al Consumo (INC %) Base
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                v-model.number="form.inc_porcentaje"
                                min="0"
                                max="100"
                                step="0.5"
                                class="w-full text-sm border border-stone-300 rounded-xl px-3 py-2.5 pr-8 bg-stone-50 focus:bg-white focus:ring-2 focus:ring-amber-500 transition font-mono font-bold"
                            />
                            <span class="absolute right-3 top-2.5 text-xs text-stone-400 font-bold">%</span>
                        </div>
                        <p class="text-xs text-stone-400">Tarifa general del 8% INC aplicable al expendio de comidas y bebidas en Colombia.</p>
                    </div>

                    <!-- Impuesto sobre las Ventas (IVA) -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase text-stone-600 tracking-wider">
                            Impuesto al Valor Agregado (IVA %) Base
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                v-model.number="form.iva_porcentaje"
                                min="0"
                                max="100"
                                step="0.5"
                                class="w-full text-sm border border-stone-300 rounded-xl px-3 py-2.5 pr-8 bg-stone-50 focus:bg-white focus:ring-2 focus:ring-amber-500 transition font-mono font-bold"
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
                            class="w-full text-sm border border-stone-300 rounded-xl px-3 py-2.5 bg-stone-50 focus:bg-white focus:ring-2 focus:ring-amber-500 transition"
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
                                <span class="text-sm font-bold text-stone-800">Precios de venta al público incluyen impuestos</span>
                                <p class="text-xs text-stone-400">El menú muestra el valor total al cliente y desglosa el impuesto en el ticket.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-stone-200 flex justify-end">
                    <button
                        type="submit"
                        :disabled="guardando"
                        class="bg-stone-900 hover:bg-black active:scale-95 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-xs transition flex items-center space-x-2"
                    >
                        <span>💾</span>
                        <span>{{ guardando ? 'Guardando...' : 'Guardar Parámetros Globales' }}</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal Crear / Editar Impuesto -->
        <div v-if="modalImpuestoAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-stone-200 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-stone-100">
                    <h3 class="text-base font-black text-stone-900">
                        {{ indiceEditando !== null ? 'Editar Impuesto' : 'Nuevo Impuesto Tributario' }}
                    </h3>
                    <button type="button" @click="modalImpuestoAbierto = false" class="text-stone-400 hover:text-stone-700">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Código del Impuesto *</label>
                        <input
                            type="text"
                            v-model="impuestoForm.codigo"
                            placeholder="Ej: INC, IVA_19, ICO, EXENTO"
                            class="w-full rounded-xl border border-stone-300 p-2.5 font-mono uppercase"
                            required
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Nombre Completo *</label>
                        <input
                            type="text"
                            v-model="impuestoForm.nombre"
                            placeholder="Ej: Impuesto Nacional al Consumo 8%"
                            class="w-full rounded-xl border border-stone-300 p-2.5"
                            required
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Tarifa Porcentual (%) *</label>
                        <input
                            type="number"
                            v-model.number="impuestoForm.porcentaje"
                            step="0.01"
                            min="0"
                            max="100"
                            class="w-full rounded-xl border border-stone-300 p-2.5 font-mono font-bold"
                            required
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Descripción / Norma legal</label>
                        <textarea
                            v-model="impuestoForm.descripcion"
                            rows="2"
                            placeholder="Ej: Aplicable a expendio de comidas según estatuto tributario..."
                            class="w-full rounded-xl border border-stone-300 p-2"
                        ></textarea>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-stone-700">
                            <input
                                type="checkbox"
                                v-model="impuestoForm.activo"
                                class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4"
                            />
                            <span>Habilitado para selección en productos</span>
                        </label>
                    </div>
                </div>

                <div class="pt-3 border-t border-stone-100 flex justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="modalImpuestoAbierto = false"
                        class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="guardarImpuestoModal"
                        class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold shadow-xs transition"
                    >
                        {{ indiceEditando !== null ? 'Actualizar' : 'Agregar' }}
                    </button>
                </div>
            </div>
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
    catalogo: {
        type: Array,
        default: () => [],
    },
});

const guardando = ref(false);
const listaCatalogo = ref([...props.catalogo]);

// Formulario de parámetros generales
const form = reactive({
    inc_porcentaje: props.impuestos.inc_porcentaje,
    iva_porcentaje: props.impuestos.iva_porcentaje,
    regimen: props.impuestos.regimen,
    precios_con_impuesto: props.impuestos.precios_con_impuesto,
});

// Modal de impuesto individual
const modalImpuestoAbierto = ref(false);
const indiceEditando = ref(null);
const impuestoForm = reactive({
    id: '',
    codigo: '',
    nombre: '',
    porcentaje: 8.0,
    descripcion: '',
    activo: true,
});

function abrirModalCrear() {
    indiceEditando.value = null;
    impuestoForm.id = 'imp_' + Date.now();
    impuestoForm.codigo = '';
    impuestoForm.nombre = '';
    impuestoForm.porcentaje = 8.0;
    impuestoForm.descripcion = '';
    impuestoForm.activo = true;
    modalImpuestoAbierto.value = true;
}

function abrirModalEditar(idx) {
    indiceEditando.value = idx;
    const imp = listaCatalogo.value[idx];
    impuestoForm.id = imp.id;
    impuestoForm.codigo = imp.codigo;
    impuestoForm.nombre = imp.nombre;
    impuestoForm.porcentaje = imp.porcentaje;
    impuestoForm.descripcion = imp.descripcion || '';
    impuestoForm.activo = imp.activo;
    modalImpuestoAbierto.value = true;
}

function alternarEstado(idx) {
    listaCatalogo.value[idx].activo = !listaCatalogo.value[idx].activo;
    sincronizarCatalogo();
}

function eliminarImpuesto(idx) {
    if (confirm(`¿Eliminar el impuesto "${listaCatalogo.value[idx].nombre}" del catálogo?`)) {
        listaCatalogo.value.splice(idx, 1);
        sincronizarCatalogo();
    }
}

function guardarImpuestoModal() {
    if (!impuestoForm.codigo || !impuestoForm.nombre) {
        alert('Por favor completa el código y el nombre del impuesto.');
        return;
    }

    if (indiceEditando.value !== null) {
        listaCatalogo.value[indiceEditando.value] = { ...impuestoForm };
    } else {
        listaCatalogo.value.push({ ...impuestoForm });
    }

    modalImpuestoAbierto.value = false;
    sincronizarCatalogo();
}

function sincronizarCatalogo() {
    router.post('/superadmin/impuestos/catalogo', {
        catalogo: listaCatalogo.value,
    }, {
        preserveScroll: true,
    });
}

function guardarParametros() {
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
