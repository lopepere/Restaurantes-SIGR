<template>
    <ClienteLayout>
        <div class="space-y-6">
            <!-- Encabezado de la vista -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900">
                        Mis Reservas
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Consulta tus reservas activas, el estado de confirmación e histórico.
                    </p>
                </div>
                <div>
                    <a
                        href="/reservar"
                        class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold text-xs shadow-xs transition"
                    >
                        <span>+</span>
                        <span>Nueva Reserva</span>
                    </a>
                </div>
            </div>

            <!-- Listado de Reservas -->
            <div v-if="reservas.data && reservas.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="reserva in reservas.data"
                    :key="reserva.id"
                    class="bg-white rounded-2xl border border-stone-200 p-5 shadow-xs hover:border-orange-300 transition-all flex flex-col justify-between"
                >
                    <div>
                        <!-- Fila superior: Código y Estado -->
                        <div class="flex items-center justify-between border-b border-stone-100 pb-3 mb-3">
                            <span class="font-mono font-bold text-xs text-orange-600 tracking-wider">
                                {{ reserva.codigo }}
                            </span>
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider',
                                    badgeClase(reserva.estado)
                                ]"
                            >
                                {{ reserva.estado_etiqueta }}
                            </span>
                        </div>

                        <!-- Detalles clave -->
                        <div class="space-y-1.5 text-xs text-stone-600">
                            <p class="flex items-center space-x-2">
                                <span class="text-stone-400">🏢 Sucursal:</span>
                                <span class="font-semibold text-stone-800">{{ reserva.sucursal }}</span>
                            </p>
                            <p class="flex items-center space-x-2">
                                <span class="text-stone-400">📅 Fecha y Hora:</span>
                                <span class="font-semibold text-stone-800">{{ reserva.fecha }} · {{ reserva.hora }}</span>
                            </p>
                            <p class="flex items-center space-x-2">
                                <span class="text-stone-400">👥 Comensales:</span>
                                <span class="font-semibold text-stone-800">{{ reserva.personas }} personas</span>
                            </p>
                            <p v-if="reserva.mesa" class="flex items-center space-x-2">
                                <span class="text-stone-400">🪑 Mesa:</span>
                                <span class="font-semibold text-orange-600">{{ reserva.mesa }}</span>
                            </p>
                            <p v-if="reserva.categoria" class="flex items-center space-x-2">
                                <span class="text-stone-400">🎉 Ocasión:</span>
                                <span class="font-medium text-stone-700">{{ reserva.categoria }}</span>
                            </p>
                            <p v-if="reserva.notas" class="text-stone-500 italic pt-1">
                                "{{ reserva.notas }}"
                            </p>
                            <p v-if="reserva.motivo_cancelacion" class="text-rose-600 text-[11px] pt-1">
                                <strong>Cancelada:</strong> {{ reserva.motivo_cancelacion }}
                            </p>
                        </div>
                    </div>

                    <!-- Botón de cancelación si está activa -->
                    <div v-if="reserva.puede_cancelar" class="mt-4 pt-3 border-t border-stone-100 flex justify-end">
                        <button
                            type="button"
                            @click="abrirModalCancelar(reserva)"
                            class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 text-xs font-semibold transition"
                        >
                            Cancelar Reserva
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estado vacío -->
            <div v-else class="bg-white rounded-2xl border border-stone-200 p-12 text-center">
                <span class="text-4xl block mb-2">📅</span>
                <h3 class="text-base font-bold text-stone-900">No tienes reservas registradas</h3>
                <p class="text-xs text-stone-500 mt-1 max-w-sm mx-auto">
                    Cuando reserves una mesa en cualquiera de nuestras sucursales, aparecerá en esta sección.
                </p>
                <div class="mt-5">
                    <a
                        href="/reservar"
                        class="inline-block px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold text-xs shadow-xs transition"
                    >
                        Reservar Ahora
                    </a>
                </div>
            </div>

            <!-- Paginación simple -->
            <div v-if="reservas.links && reservas.links.length > 3" class="flex justify-center space-x-1 pt-4">
                <template v-for="(link, i) in reservas.links" :key="i">
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

        <!-- Modal de Cancelación -->
        <div v-if="modalCancelacionAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Cancelar Reserva {{ reservaSeleccionada?.codigo }}
                    </h3>
                    <button @click="cerrarModalCancelar" class="text-stone-400 hover:text-stone-600 text-lg">
                        ✕
                    </button>
                </div>

                <p class="text-xs text-stone-600">
                    Por favor indícanos el motivo de la cancelación. Liberaremos la mesa de inmediato para otros comensales.
                </p>

                <form @submit.prevent="confirmarCancelacion" class="space-y-4">
                    <div>
                        <label for="motivo" class="block text-xs font-semibold text-stone-700 mb-1">
                            Motivo de la cancelación *
                        </label>
                        <textarea
                            id="motivo"
                            v-model="formCancelar.motivo"
                            rows="3"
                            placeholder="Ej. Cambio de planes de viaje, imprevisto familiar..."
                            class="w-full rounded-xl border border-stone-300 bg-white p-3 text-xs text-stone-800 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-hidden transition"
                            required
                        ></textarea>
                        <p v-if="formCancelar.errors.motivo" class="text-xs text-rose-600 mt-1">
                            {{ formCancelar.errors.motivo }}
                        </p>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button
                            type="button"
                            @click="cerrarModalCancelar"
                            class="px-4 py-2 rounded-xl border border-stone-200 text-xs font-medium text-stone-600 hover:bg-stone-50"
                        >
                            Volver
                        </button>
                        <button
                            type="submit"
                            :disabled="formCancelar.processing"
                            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition"
                        >
                            {{ formCancelar.processing ? 'Cancelando...' : 'Confirmar Cancelación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClienteLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import ClienteLayout from '@/Layouts/ClienteLayout.vue';

const props = defineProps({
    reservas: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const userId = page.props.auth?.user?.id;

const modalCancelacionAbierto = ref(false);
const reservaSeleccionada = ref(null);

const formCancelar = useForm({
    motivo: '',
});

const abrirModalCancelar = (reserva) => {
    reservaSeleccionada.value = reserva;
    formCancelar.reset();
    modalCancelacionAbierto.value = true;
};

const cerrarModalCancelar = () => {
    modalCancelacionAbierto.value = false;
    reservaSeleccionada.value = null;
    formCancelar.reset();
};

const confirmarCancelacion = () => {
    if (!reservaSeleccionada.value) return;

    formCancelar.post(`/cliente/reservas/${reservaSeleccionada.value.id}/cancelar`, {
        onSuccess: () => {
            cerrarModalCancelar();
        },
    });
};

const badgeClase = (estado) => {
    return {
        'pendiente': 'bg-amber-100 text-amber-800 border border-amber-200',
        'confirmada': 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        'cumplida': 'bg-sky-100 text-sky-800 border border-sky-200',
        'no_asistio': 'bg-stone-100 text-stone-700 border border-stone-200',
        'cancelada': 'bg-rose-100 text-rose-800 border border-rose-200',
    }[estado] || 'bg-stone-100 text-stone-700';
};

// WebSocket para refresco en tiempo real
onMounted(() => {
    if (window.Echo && userId) {
        window.Echo.private(`cliente.${userId}.reservas`)
            .listen('.reserva.actualizada', () => {
                router.reload({ only: ['reservas'] });
            });
    }
});

onUnmounted(() => {
    if (window.Echo && userId) {
        window.Echo.leave(`cliente.${userId}.reservas`);
    }
});
</script>
