<template>
    <ClienteLayout>
        <div class="space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900">
                        Mis Comprobantes de Venta
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Historial inmutable de tus comprobantes (FAC) con opción de descarga y reenvío por correo.
                    </p>
                </div>
            </div>

            <!-- Listado de Comprobantes -->
            <div v-if="comprobantes.data && comprobantes.data.length > 0" class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 uppercase tracking-wider font-semibold">
                                <th class="p-3.5">Número</th>
                                <th class="p-3.5">Sucursal</th>
                                <th class="p-3.5">Fecha</th>
                                <th class="p-3.5">Total Pagado</th>
                                <th class="p-3.5">Método</th>
                                <th class="p-3.5">Estado</th>
                                <th class="p-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr
                                v-for="c in comprobantes.data"
                                :key="c.id"
                                class="hover:bg-stone-50/70 transition"
                            >
                                <td class="p-3.5 font-mono font-bold text-orange-600 select-all">
                                    {{ c.numero }}
                                </td>
                                <td class="p-3.5 font-medium text-stone-800">
                                    {{ c.sucursal }}
                                </td>
                                <td class="p-3.5 text-stone-500 font-mono">
                                    {{ c.fecha }}
                                </td>
                                <td class="p-3.5 font-bold font-mono text-stone-900">
                                    ${{ c.total.toLocaleString() }}
                                </td>
                                <td class="p-3.5 capitalize text-stone-600">
                                    {{ c.metodo_pago }}
                                </td>
                                <td class="p-3.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        {{ c.estado }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                                    <a
                                        :href="`/comprobante/verificar/${c.codigo_verificacion}`"
                                        target="_blank"
                                        class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg font-semibold text-[11px] transition"
                                        title="Verificar en línea"
                                    >
                                        Ver
                                    </a>
                                    <a
                                        :href="`/cliente/comprobantes/${c.id}/descargar`"
                                        class="px-2.5 py-1 bg-stone-900 hover:bg-stone-800 text-white rounded-lg font-semibold text-[11px] shadow-xs transition"
                                        title="Descargar tirilla"
                                    >
                                        Descargar
                                    </a>
                                    <button
                                        type="button"
                                        @click="abrirModalReenviar(c)"
                                        class="px-2.5 py-1 border border-stone-300 hover:bg-stone-50 text-stone-700 rounded-lg font-semibold text-[11px] transition"
                                        title="Reenviar a correo"
                                    >
                                        Reenviar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="comprobantes.links && comprobantes.links.length > 3" class="p-4 border-t border-stone-100 flex justify-center space-x-1">
                    <template v-for="(link, i) in comprobantes.links" :key="i">
                        <a
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-medium border transition',
                                link.active ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-stone-700 border-stone-200 hover:bg-stone-50'
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border border-stone-200 text-stone-300 opacity-50"
                        />
                    </template>
                </div>
            </div>

            <!-- Estado Vacío -->
            <div v-else class="bg-white rounded-2xl border border-stone-200 p-12 text-center">
                <span class="text-4xl block mb-2">🧾</span>
                <h3 class="text-base font-bold text-stone-900">No tienes comprobantes emitidos</h3>
                <p class="text-xs text-stone-500 mt-1 max-w-sm mx-auto">
                    Cuando realices y pagues tus pedidos en cualquiera de nuestras sucursales, tus comprobantes aparecerán aquí.
                </p>
            </div>
        </div>

        <!-- Modal de Reenvío de Comprobante (CA-31) -->
        <div v-if="modalReenviarAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Reenviar {{ comprobanteSeleccionado?.numero }}
                    </h3>
                    <button @click="cerrarModalReenviar" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <p class="text-xs text-stone-600">
                    Puedes reenviar este comprobante hasta a 3 correos electrónicos designados. Límite de seguridad: 10 reenvíos por hora.
                </p>

                <form @submit.prevent="ejecutarReenvio" class="space-y-4">
                    <div>
                        <label for="correo_reenvio" class="block text-xs font-semibold text-stone-700 mb-1">
                            Correo Electrónico Destino *
                        </label>
                        <input
                            id="correo_reenvio"
                            type="email"
                            v-model="correoInput"
                            placeholder="correo@ejemplo.com"
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                            required
                        />
                        <p v-if="formReenviar.errors.correos" class="text-xs text-rose-600 mt-1">
                            {{ formReenviar.errors.correos }}
                        </p>
                    </div>

                    <div>
                        <label for="mensaje_reenvio" class="block text-xs font-semibold text-stone-700 mb-1">
                            Mensaje personalizado (opcional)
                        </label>
                        <textarea
                            id="mensaje_reenvio"
                            v-model="formReenviar.mensaje"
                            rows="2"
                            placeholder="Te comparto el comprobante del almuerzo..."
                            class="w-full rounded-xl border border-stone-300 p-2 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                        ></textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="cerrarModalReenviar" class="px-4 py-2 rounded-xl border border-stone-200 text-xs font-medium text-stone-600 hover:bg-stone-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formReenviar.processing" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold transition">
                            {{ formReenviar.processing ? 'Enviando...' : 'Reenviar Comprobante' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClienteLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ClienteLayout from '@/Layouts/ClienteLayout.vue';

defineProps({
    comprobantes: {
        type: Object,
        required: true,
    },
});

const modalReenviarAbierto = ref(false);
const comprobanteSeleccionado = ref(null);
const correoInput = ref('');

const formReenviar = useForm({
    correos: [],
    mensaje: '',
});

const abrirModalReenviar = (comprobante) => {
    comprobanteSeleccionado.value = comprobante;
    correoInput.value = '';
    formReenviar.reset();
    modalReenviarAbierto.value = true;
};

const cerrarModalReenviar = () => {
    modalReenviarAbierto.value = false;
    comprobanteSeleccionado.value = null;
    formReenviar.reset();
};

const ejecutarReenvio = () => {
    if (!comprobanteSeleccionado.value || !correoInput.value) return;

    formReenviar.correos = [correoInput.value];
    formReenviar.post(`/cliente/comprobantes/${comprobanteSeleccionado.value.id}/reenviar`, {
        preserveScroll: true,
        onSuccess: () => cerrarModalReenviar(),
    });
};
</script>
