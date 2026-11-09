<template>
    <AdminLayout :titulo="'Gestión de Promociones'">
        <div class="space-y-6">
            <!-- Barra Superior y Acciones -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                <div>
                    <h1 class="text-xl font-bold text-stone-900 tracking-tight">Promociones y Descuentos</h1>
                    <p class="text-xs text-stone-500 mt-0.5">Define ofertas especiales, descuentos por porcentaje o valor fijo programados por horario y calendario.</p>
                </div>

                <div>
                    <button
                        @click="abrirModalPromocion()"
                        class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>+</span>
                        <span>Nueva Promoción</span>
                    </button>
                </div>
            </div>

            <!-- Tabla de Promociones -->
            <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 font-semibold uppercase tracking-wider">
                            <th class="p-3.5 pl-5">Promoción</th>
                            <th class="p-3.5">Beneficio</th>
                            <th class="p-3.5">Vigencia Fechas</th>
                            <th class="p-3.5">Horario</th>
                            <th class="p-3.5">Alcance</th>
                            <th class="p-3.5">Estado</th>
                            <th class="p-3.5 pr-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <tr v-if="promociones.data.length === 0">
                            <td colspan="7" class="p-8 text-center text-stone-400">
                                No hay promociones registradas en esta sede.
                            </td>
                        </tr>
                        <tr v-for="promo in promociones.data" :key="promo.id" class="hover:bg-stone-50/70 transition">
                            <td class="p-3.5 pl-5">
                                <span class="font-bold text-stone-900 block">{{ promo.nombre }}</span>
                                <span class="text-[11px] text-stone-400 line-clamp-1">{{ promo.descripcion || '—' }}</span>
                            </td>
                            <td class="p-3.5">
                                <span class="font-bold text-orange-600">
                                    {{ promo.tipo_beneficio === 'porcentaje' ? `${promo.valor_beneficio}% OFF` : `$ ${Number(promo.valor_beneficio).toLocaleString('es-CO')}` }}
                                </span>
                            </td>
                            <td class="p-3.5 text-stone-600 font-medium">
                                {{ new Date(promo.fecha_inicio).toLocaleDateString('es-CO') }} - {{ new Date(promo.fecha_fin).toLocaleDateString('es-CO') }}
                            </td>
                            <td class="p-3.5 text-stone-600">
                                {{ promo.hora_inicio ? `${promo.hora_inicio} a ${promo.hora_fin}` : 'Todo el día' }}
                            </td>
                            <td class="p-3.5 text-stone-600">
                                <span v-if="promo.aplica_a_todo" class="font-semibold text-purple-700">Todo el menú</span>
                                <span v-else-if="promo.categorias && promo.categorias.length > 0" class="text-stone-700">
                                    {{ promo.categorias.map(c => c.nombre).join(', ') }}
                                </span>
                                <span v-else-if="promo.items && promo.items.length > 0" class="text-stone-700">
                                    {{ promo.items.map(i => i.nombre).join(', ') }}
                                </span>
                                <span v-else class="text-stone-400 italic">Específico</span>
                            </td>
                            <td class="p-3.5">
                                <span :class="[
                                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold',
                                    promo.publicada ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-500'
                                ]">
                                    {{ promo.publicada ? '🔥 Publicada' : 'Pausada' }}
                                </span>
                            </td>
                            <td class="p-3.5 pr-5 text-right space-x-2">
                                <button
                                    @click="abrirModalPromocion(promo)"
                                    class="text-orange-600 hover:text-orange-800 font-semibold"
                                >
                                    Editar
                                </button>
                                <button
                                    @click="eliminarPromocion(promo)"
                                    class="text-rose-600 hover:text-rose-800 font-semibold"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Promoción -->
        <div v-if="modalVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/50 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-stone-200 my-8">
                <h3 class="text-lg font-bold text-stone-900 mb-4">
                    {{ promoEnEdicion ? 'Editar Promoción' : 'Nueva Promoción' }}
                </h3>
                <form @submit.prevent="guardarPromocion" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Nombre</label>
                        <input type="text" v-model="formPromo.nombre" required class="w-full border rounded-xl p-2 text-stone-900" />
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Descripción</label>
                        <textarea v-model="formPromo.descripcion" rows="2" class="w-full border rounded-xl p-2 text-stone-900"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Tipo de Beneficio</label>
                            <select v-model="formPromo.tipo_beneficio" class="w-full border rounded-xl p-2 text-stone-900">
                                <option v-for="tipo in tiposBeneficio" :key="tipo" :value="tipo">{{ tipo }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Valor Beneficio (% o $)</label>
                            <input type="number" step="0.5" min="0" v-model.number="formPromo.valor_beneficio" required class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Fecha Inicio</label>
                            <input type="date" v-model="formPromo.fecha_inicio" required class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Fecha Fin</label>
                            <input type="date" v-model="formPromo.fecha_fin" required class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Hora Inicio (Opcional)</label>
                            <input type="time" v-model="formPromo.hora_inicio" class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Hora Fin (Opcional)</label>
                            <input type="time" v-model="formPromo.hora_fin" class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                    </div>

                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200">
                        <label class="flex items-center gap-2 cursor-pointer mb-2">
                            <input type="checkbox" v-model="formPromo.aplica_a_todo" class="rounded text-orange-600" />
                            <span class="font-bold text-stone-800">¿Aplica a todo el menú?</span>
                        </label>
                        <div v-if="!formPromo.aplica_a_todo" class="space-y-2 mt-2">
                            <div>
                                <label class="block font-semibold text-stone-600 mb-1">Categorías que aplica</label>
                                <select multiple v-model="formPromo.categorias" class="w-full border rounded-xl p-2 text-stone-800 h-20">
                                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="modalVisible = false" class="px-4 py-2 border rounded-xl text-stone-600">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white font-bold rounded-xl">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    promociones: Object,
    categorias: Array,
    items: Array,
    tiposBeneficio: Array,
});

const modalVisible = ref(false);
const promoEnEdicion = ref(null);
const formPromo = ref({
    nombre: '',
    descripcion: '',
    tipo_beneficio: 'porcentaje',
    valor_beneficio: 10,
    fecha_inicio: '',
    fecha_fin: '',
    hora_inicio: '',
    hora_fin: '',
    activa: true,
    aplica_a_todo: false,
    categorias: [],
    items: [],
});

function abrirModalPromocion(promo = null) {
    promoEnEdicion.value = promo;
    if (promo) {
        formPromo.value = {
            ...promo,
            fecha_inicio: promo.fecha_inicio.substring(0, 10),
            fecha_fin: promo.fecha_fin.substring(0, 10),
            categorias: promo.categorias ? promo.categorias.map(c => c.id) : [],
            items: promo.items ? promo.items.map(i => i.id) : [],
        };
    } else {
        const hoy = new Date().toISOString().substring(0, 10);
        formPromo.value = {
            nombre: '',
            descripcion: '',
            tipo_beneficio: 'porcentaje',
            valor_beneficio: 10,
            fecha_inicio: hoy,
            fecha_fin: hoy,
            hora_inicio: '',
            hora_fin: '',
            activa: true,
            aplica_a_todo: true,
            categorias: [],
            items: [],
        };
    }
    modalVisible.value = true;
}

function guardarPromocion() {
    formPromo.value.valor_beneficio = Math.max(0, Number(formPromo.value.valor_beneficio) || 0);
    if (promoEnEdicion.value) {
        router.put(`/admin/promociones/${promoEnEdicion.value.id}`, formPromo.value, {
            onSuccess: () => modalVisible.value = false,
        });
    } else {
        router.post('/admin/promociones', formPromo.value, {
            onSuccess: () => modalVisible.value = false,
        });
    }
}

function eliminarPromocion(promo) {
    if (confirm(`¿Estás seguro de eliminar la promoción "${promo.nombre}"?`)) {
        router.delete(`/admin/promociones/${promo.id}`);
    }
}
</script>
