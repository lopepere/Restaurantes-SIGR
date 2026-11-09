<template>
    <MeseroLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between border-b border-stone-200 pb-4">
                <div>
                    <h1 class="text-2xl font-black text-stone-900 tracking-tight">Tomar Nueva Comanda</h1>
                    <p class="text-xs text-stone-500">Selecciona la mesa o modalidad, agrega platos y confirma para enviar a cocina.</p>
                </div>
                <a
                    href="/mesero/pedidos"
                    class="text-xs font-bold text-stone-600 hover:text-stone-900 bg-stone-100 hover:bg-stone-200 px-3.5 py-2 rounded-xl transition"
                >
                    ← Volver a Comandas
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna 1 y 2: Selector de Mesa y Catálogo de Platos -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Selección de Espacio / Mesa -->
                    <div class="bg-white rounded-3xl border border-stone-200 p-6 shadow-xs space-y-3">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-700">Mesa o Modalidad de Servicio</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button
                                type="button"
                                @click="form.tipo = 'en_sitio'; form.espacio_id = null"
                                :class="['py-2.5 px-3 rounded-xl text-xs font-bold border transition text-center', form.tipo === 'en_sitio' && !form.espacio_id ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100']"
                            >
                                Sin mesa fija
                            </button>
                            <button
                                v-for="espacio in espacios"
                                :key="espacio.id"
                                type="button"
                                @click="form.tipo = 'en_sitio'; form.espacio_id = espacio.id"
                                :class="[
                                    'py-2.5 px-3 rounded-xl text-xs font-bold border transition text-center flex flex-col items-center justify-center',
                                    form.espacio_id === espacio.id
                                        ? 'bg-orange-600 text-white border-orange-600'
                                        : (espacio.estado === 'ocupado' ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100')
                                ]"
                            >
                                <span>{{ espacio.nombre }}</span>
                                <span class="text-[10px] opacity-75">({{ espacio.capacidad }}p • {{ espacio.estado }})</span>
                            </button>
                            <button
                                type="button"
                                @click="form.tipo = 'para_llevar'; form.espacio_id = null"
                                :class="['py-2.5 px-3 rounded-xl text-xs font-bold border transition text-center', form.tipo === 'para_llevar' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100']"
                            >
                                🛍️ Para llevar
                            </button>
                        </div>
                    </div>

                    <!-- Buscador y Carta de Platos -->
                    <div class="bg-white rounded-3xl border border-stone-200 p-6 shadow-xs space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-sm font-bold text-stone-900 uppercase tracking-wider">Carta de Platos</h3>
                            <input
                                v-model="terminoBusqueda"
                                type="search"
                                placeholder="Buscar plato..."
                                class="px-3 py-1.5 text-xs rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-orange-500 w-48 sm:w-64"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-96 overflow-y-auto pr-1">
                            <div
                                v-for="item in itemsFiltrados"
                                :key="item.id"
                                class="p-3 rounded-2xl border border-stone-200 hover:border-orange-400 bg-stone-50/50 hover:bg-orange-50/20 transition flex items-center justify-between cursor-pointer"
                                @click="agregarItem(item)"
                            >
                                <div>
                                    <h4 class="font-bold text-xs text-stone-900">{{ item.nombre }}</h4>
                                    <p class="text-[11px] text-stone-500">{{ item.categoria?.nombre || 'General' }}</p>
                                    <p class="text-xs font-black text-orange-600 mt-1">{{ formatearMoneda(item.precio) }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="w-7 h-7 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold text-sm shadow-xs hover:bg-orange-700 transition"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 3: Comanda Actual y Enviar -->
                <div class="bg-white rounded-3xl border border-stone-200 p-6 shadow-xs flex flex-col justify-between space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-stone-900 uppercase tracking-wider border-b border-stone-100 pb-3 flex items-center justify-between">
                            <span>Resumen Comanda</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-stone-100 text-stone-700">
                                {{ itemsComanda.length }} ítem(s)
                            </span>
                        </h3>

                        <div v-if="itemsComanda.length === 0" class="py-12 text-center text-stone-400 text-xs">
                            <p class="text-2xl mb-1">📝</p>
                            Haz clic en los platos para agregarlos a la comanda.
                        </div>

                        <div v-else class="divide-y divide-stone-100 max-h-80 overflow-y-auto space-y-2 pt-2">
                            <div
                                v-for="(ci, idx) in itemsComanda"
                                :key="idx"
                                class="py-2 space-y-1.5"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-stone-900">{{ ci.nombre }}</span>
                                    <span class="font-black text-stone-900">{{ formatearMoneda(ci.precio * ci.cantidad) }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="decrementarItem(idx)"
                                            class="w-6 h-6 rounded-lg bg-stone-100 text-stone-700 font-bold text-xs hover:bg-stone-200"
                                        >
                                            -
                                        </button>
                                        <span class="w-6 text-center text-xs font-bold text-stone-800">{{ ci.cantidad }}</span>
                                        <button
                                            type="button"
                                            @click="ci.cantidad++"
                                            class="w-6 h-6 rounded-lg bg-stone-100 text-stone-700 font-bold text-xs hover:bg-stone-200"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <input
                                        v-model="ci.notas"
                                        type="text"
                                        placeholder="Instrucción (ej: sin cebolla)"
                                        class="flex-1 text-[11px] px-2 py-1 rounded-lg border border-stone-200 focus:outline-none focus:ring-1 focus:ring-orange-500"
                                    />
                                    <button
                                        type="button"
                                        @click="removerItem(idx)"
                                        class="text-stone-400 hover:text-rose-600 text-xs font-bold"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notas generales y confirmación -->
                    <div class="space-y-3 pt-3 border-t border-stone-100">
                        <div>
                            <label class="block text-[11px] font-semibold text-stone-600 mb-1">Notas generales de la comanda</label>
                            <input
                                v-model="form.notas"
                                type="text"
                                placeholder="Observación general para la comanda..."
                                class="w-full text-xs px-3 py-2 rounded-xl border border-stone-200 focus:outline-none focus:ring-1 focus:ring-orange-500"
                            />
                        </div>

                        <div class="flex items-center justify-between text-base font-black text-stone-900 pt-2">
                            <span>Total Comanda:</span>
                            <span class="text-xl text-orange-600">{{ formatearMoneda(totalComanda) }}</span>
                        </div>

                        <button
                            type="button"
                            :disabled="itemsComanda.length === 0 || enviando"
                            @click="enviarComanda"
                            class="w-full py-3 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm rounded-xl shadow-md transition cursor-pointer flex items-center justify-center gap-2"
                        >
                            <span v-if="enviando">⏳ Enviando...</span>
                            <span v-else>🚀 Enviar Comanda a Cocina</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MeseroLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import MeseroLayout from '@/Layouts/MeseroLayout.vue';

const props = defineProps({
    sucursal_id: {
        type: Number,
        required: true,
    },
    espacios: {
        type: Array,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
});

const terminoBusqueda = ref('');
const itemsComanda = ref([]);
const enviando = ref(false);

const form = ref({
    tipo: 'en_sitio',
    espacio_id: null,
    nombre_contacto: '',
    telefono_contacto: '',
    notas: '',
});

const itemsFiltrados = computed(() => {
    if (!terminoBusqueda.value.trim()) {
        return props.items;
    }
    const q = terminoBusqueda.value.toLowerCase();
    return props.items.filter(i => i.nombre.toLowerCase().includes(q));
});

const totalComanda = computed(() => {
    return itemsComanda.value.reduce((acc, it) => acc + (it.precio * it.cantidad), 0);
});

function agregarItem(item) {
    const existe = itemsComanda.value.find(ci => ci.item_menu_id === item.id);
    if (existe) {
        existe.cantidad++;
    } else {
        itemsComanda.value.push({
            item_menu_id: item.id,
            nombre: item.nombre,
            precio: Number(item.precio),
            cantidad: 1,
            notas: '',
        });
    }
}

function decrementarItem(idx) {
    if (itemsComanda.value[idx].cantidad > 1) {
        itemsComanda.value[idx].cantidad--;
    } else {
        removerItem(idx);
    }
}

function removerItem(idx) {
    itemsComanda.value.splice(idx, 1);
}

function formatearMoneda(valor) {
    return '$ ' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function enviarComanda() {
    enviando.value = true;
    router.post('/mesero/pedidos', {
        tipo: form.value.tipo,
        espacio_id: form.value.espacio_id,
        nombre_contacto: form.value.nombre_contacto || null,
        telefono_contacto: form.value.telefono_contacto || null,
        notas: form.value.notas || null,
        items: itemsComanda.value.map(ci => ({
            item_menu_id: ci.item_menu_id,
            cantidad: ci.cantidad,
            notas: ci.notas || null,
        })),
    }, {
        onFinish: () => {
            enviando.value = false;
        },
    });
}
</script>
