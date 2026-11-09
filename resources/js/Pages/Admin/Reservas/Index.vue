<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado de la página -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>📅</span>
                        <span>Monitor y Gestión de Reservas</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Control en tiempo real de mesas reservadas, aprobaciones, reprogramaciones y asistencia.
                    </p>
                </div>

                <!-- Selector de Sucursal y Enlace a Mapa de Mesas -->
                <div class="flex items-center space-x-3">
                    <div v-if="sucursales && sucursales.length > 1">
                        <select
                            v-model="sucursalSeleccionada"
                            @change="cambiarSucursal"
                            class="rounded-xl border border-stone-300 bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 shadow-xs focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                        >
                            <option v-for="s in sucursales" :key="s.id" :value="s.id">
                                🏢 {{ s.nombre }}
                            </option>
                        </select>
                    </div>

                    <a
                        href="/admin/espacios"
                        class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-stone-900 hover:bg-stone-800 text-white text-xs font-bold shadow-xs transition"
                    >
                        <span>🪑</span>
                        <span>Ver Mapa de Mesas</span>
                    </a>
                </div>
            </div>

            <!-- Barra de Pestañas (Tabs) -->
            <div class="flex overflow-x-auto space-x-2 border-b border-stone-200 pb-2">
                <button
                    v-for="t in pestanas"
                    :key="t.id"
                    type="button"
                    @click="cambiarTab(t.id)"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center space-x-1.5',
                        tab === t.id
                            ? 'bg-orange-600 text-white shadow-xs'
                            : 'bg-white text-stone-600 hover:bg-stone-100 border border-stone-200'
                    ]"
                >
                    <span>{{ t.icono }}</span>
                    <span>{{ t.nombre }}</span>
                </button>
            </div>

            <!-- Filtros de búsqueda rápida -->
            <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-xs flex flex-wrap gap-3 items-center justify-between">
                <div class="flex flex-wrap gap-2 items-center flex-1">
                    <input
                        type="text"
                        v-model="filtroBusqueda"
                        @keyup.enter="aplicarFiltros"
                        placeholder="Buscar por cliente, teléfono o código..."
                        class="rounded-xl border border-stone-300 px-3.5 py-1.5 text-xs text-stone-700 w-full sm:w-64 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                    />

                    <input
                        type="date"
                        v-model="filtroFecha"
                        @change="aplicarFiltros"
                        class="rounded-xl border border-stone-300 px-3 py-1.5 text-xs text-stone-700 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                    />

                    <button
                        type="button"
                        @click="aplicarFiltros"
                        class="px-3.5 py-1.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-semibold transition"
                    >
                        Filtrar
                    </button>

                    <button
                        v-if="filtroBusqueda || filtroFecha"
                        type="button"
                        @click="limpiarFiltros"
                        class="px-3 py-1.5 text-xs text-stone-400 hover:text-stone-600 transition"
                    >
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla de Reservas -->
            <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 uppercase tracking-wider font-semibold">
                                <th class="p-3.5">Código</th>
                                <th class="p-3.5">Fecha y Franja</th>
                                <th class="p-3.5">Cliente / Contacto</th>
                                <th class="p-3.5">Comensales</th>
                                <th class="p-3.5">Mesa / Espacio</th>
                                <th class="p-3.5">Estado</th>
                                <th class="p-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr
                                v-for="reserva in reservas.data"
                                :key="reserva.id"
                                class="hover:bg-stone-50/70 transition"
                            >
                                <!-- Código -->
                                <td class="p-3.5 font-mono font-bold text-orange-600 select-all">
                                    {{ reserva.codigo }}
                                    <span v-if="reserva.categoria" class="block font-sans text-[10px] text-stone-400 font-normal">
                                        {{ reserva.categoria }}
                                    </span>
                                </td>

                                <!-- Fecha y Franja -->
                                <td class="p-3.5">
                                    <span class="font-bold text-stone-800">{{ reserva.fecha }}</span>
                                    <span class="block text-stone-500 text-[11px] font-mono">
                                        {{ reserva.hora_inicio }} - {{ reserva.hora_fin }}
                                    </span>
                                </td>

                                <!-- Contacto -->
                                <td class="p-3.5">
                                    <span class="font-semibold text-stone-800">{{ reserva.nombre_contacto }}</span>
                                    <span class="block text-stone-500 text-[11px]">
                                        📞 {{ reserva.telefono_contacto }}
                                    </span>
                                    <span v-if="reserva.notas" class="block text-stone-400 italic text-[10px] mt-0.5 max-w-xs truncate" :title="reserva.notas">
                                        "{{ reserva.notas }}"
                                    </span>
                                </td>

                                <!-- Personas -->
                                <td class="p-3.5">
                                    <span class="font-bold text-stone-800">{{ reserva.personas }}</span>
                                    <span class="text-stone-400 text-[11px]"> comensales</span>
                                </td>

                                <!-- Mesa -->
                                <td class="p-3.5">
                                    <span v-if="reserva.mesa" class="inline-flex items-center space-x-1 font-semibold text-stone-700 bg-stone-100 px-2 py-0.5 rounded-md">
                                        <span>🪑</span>
                                        <span>{{ reserva.mesa }}</span>
                                    </span>
                                    <span v-else class="text-stone-400 italic">Sin asignar</span>
                                </td>

                                <!-- Estado -->
                                <td class="p-3.5">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                            badgeClase(reserva.estado)
                                        ]"
                                    >
                                        {{ reserva.estado_etiqueta }}
                                    </span>
                                    <span v-if="reserva.motivo_cancelacion" class="block text-[10px] text-rose-600 mt-1 truncate max-w-xs" :title="reserva.motivo_cancelacion">
                                        Cancelada: {{ reserva.motivo_cancelacion }}
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                                    <!-- Aprobar (si pendiente) -->
                                    <button
                                        v-if="reserva.puede_aprobar"
                                        type="button"
                                        @click="aprobarReserva(reserva)"
                                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold text-[11px] shadow-xs transition"
                                        title="Confirmar reserva"
                                    >
                                        Aprobar
                                    </button>

                                    <!-- Reprogramar (si activa) -->
                                    <button
                                        v-if="reserva.puede_reprogramar"
                                        type="button"
                                        @click="abrirModalReprogramar(reserva)"
                                        class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold text-[11px] shadow-xs transition"
                                        title="Reprogramar fecha u hora"
                                    >
                                        Reprogramar
                                    </button>

                                    <!-- Marcar Asistencia (si confirmada) -->
                                    <template v-if="reserva.puede_marcar_asistencia">
                                        <button
                                            type="button"
                                            @click="marcarAsistencia(reserva, true)"
                                            class="px-2 py-1 bg-sky-600 hover:bg-sky-700 text-white rounded-lg font-semibold text-[11px] shadow-xs transition"
                                            title="Marcar como cumplida (llegó el cliente)"
                                        >
                                            Cumplida
                                        </button>
                                        <button
                                            type="button"
                                            @click="marcarAsistencia(reserva, false)"
                                            class="px-2 py-1 bg-stone-600 hover:bg-stone-700 text-white rounded-lg font-semibold text-[11px] shadow-xs transition"
                                            title="Marcar no asistió"
                                        >
                                            No asistió
                                        </button>
                                    </template>

                                    <!-- Cancelar (si activa) -->
                                    <button
                                        v-if="reserva.puede_cancelar"
                                        type="button"
                                        @click="abrirModalCancelar(reserva)"
                                        class="px-2 py-1 border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg font-semibold text-[11px] transition"
                                        title="Cancelar reserva"
                                    >
                                        Cancelar
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!reservas.data || reservas.data.length === 0">
                                <td colspan="7" class="p-10 text-center text-stone-400">
                                    No se encontraron reservas en esta pestaña o con los filtros aplicados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="reservas.links && reservas.links.length > 3" class="p-4 border-t border-stone-100 flex justify-center space-x-1">
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
        </div>

        <!-- Modal de Cancelación -->
        <div v-if="modalCancelarAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Cancelar Reserva {{ reservaSeleccionada?.codigo }}
                    </h3>
                    <button @click="cerrarModalCancelar" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarCancelacion" class="space-y-4">
                    <div>
                        <label for="motivo_admin" class="block text-xs font-semibold text-stone-700 mb-1">
                            Motivo de la cancelación *
                        </label>
                        <textarea
                            id="motivo_admin"
                            v-model="formCancelar.motivo"
                            rows="3"
                            placeholder="Ej. Solicitud telefónica del cliente, cierre por mantenimiento..."
                            class="w-full rounded-xl border border-stone-300 p-3 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                            required
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="cerrarModalCancelar" class="px-4 py-2 rounded-xl border border-stone-200 text-xs font-medium text-stone-600 hover:bg-stone-50">
                            Volver
                        </button>
                        <button type="submit" :disabled="formCancelar.processing" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition">
                            {{ formCancelar.processing ? 'Cancelando...' : 'Confirmar Cancelación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal de Reprogramación -->
        <div v-if="modalReprogramarAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Reprogramar Reserva {{ reservaSeleccionada?.codigo }}
                    </h3>
                    <button @click="cerrarModalReprogramar" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarReprogramacion" class="space-y-4">
                    <div>
                        <label for="nueva_fecha" class="block text-xs font-semibold text-stone-700 mb-1">
                            Nueva Fecha *
                        </label>
                        <input
                            id="nueva_fecha"
                            type="date"
                            v-model="formReprogramar.nueva_fecha"
                            class="w-full rounded-xl border border-stone-300 px-3.5 py-2 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                            required
                        />
                    </div>
                    <div>
                        <label for="nueva_hora" class="block text-xs font-semibold text-stone-700 mb-1">
                            Nueva Hora de Inicio *
                        </label>
                        <input
                            id="nueva_hora"
                            type="time"
                            step="1800"
                            v-model="formReprogramar.nueva_hora_inicio"
                            class="w-full rounded-xl border border-stone-300 px-3.5 py-2 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                            required
                        />
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="cerrarModalReprogramar" class="px-4 py-2 rounded-xl border border-stone-200 text-xs font-medium text-stone-600 hover:bg-stone-50">
                            Volver
                        </button>
                        <button type="submit" :disabled="formReprogramar.processing" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition">
                            {{ formReprogramar.processing ? 'Reprogramando...' : 'Guardar Nueva Fecha' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    reservas: { type: Object, required: true },
    tab: { type: String, default: 'proximas' },
    filtros: { type: Object, default: () => ({}) },
    sucursales: { type: Array, default: () => [] },
    sucursal_id: { type: Number, required: true },
    espacios: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
});

const sucursalSeleccionada = ref(props.sucursal_id);
const filtroBusqueda = ref(props.filtros.nombre || '');
const filtroFecha = ref(props.filtros.fecha || '');

const pestanas = [
    { id: 'proximas', nombre: 'Próximas', icono: '⏳' },
    { id: 'hoy', nombre: 'Para Hoy', icono: '📌' },
    { id: 'pendientes', nombre: 'Pendientes', icono: '🟡' },
    { id: 'confirmadas', nombre: 'Confirmadas', icono: '🟢' },
    { id: 'historico', nombre: 'Histórico', icono: '📁' },
    { id: 'todas', nombre: 'Todas', icono: '📋' },
];

const cambiarSucursal = () => {
    router.get('/admin/reservas', {
        sucursal_id: sucursalSeleccionada.value,
        tab: props.tab,
    });
};

const cambiarTab = (nuevaTab) => {
    router.get('/admin/reservas', {
        sucursal_id: sucursalSeleccionada.value,
        tab: nuevaTab,
        fecha: filtroFecha.value || undefined,
        nombre: filtroBusqueda.value || undefined,
    });
};

const aplicarFiltros = () => {
    router.get('/admin/reservas', {
        sucursal_id: sucursalSeleccionada.value,
        tab: props.tab,
        fecha: filtroFecha.value || undefined,
        nombre: filtroBusqueda.value || undefined,
    });
};

const limpiarFiltros = () => {
    filtroBusqueda.value = '';
    filtroFecha.value = '';
    cambiarTab(props.tab);
};

// Acciones sobre reservas
const aprobarReserva = (reserva) => {
    router.post(`/admin/reservas/${reserva.id}/aprobar`, {}, {
        preserveScroll: true,
    });
};

const marcarAsistencia = (reserva, asistio) => {
    router.post(`/admin/reservas/${reserva.id}/asistencia`, { asistio }, {
        preserveScroll: true,
    });
};

// Modal Cancelar
const modalCancelarAbierto = ref(false);
const reservaSeleccionada = ref(null);
const formCancelar = useForm({ motivo: '' });

const abrirModalCancelar = (reserva) => {
    reservaSeleccionada.value = reserva;
    formCancelar.reset();
    modalCancelarAbierto.value = true;
};

const cerrarModalCancelar = () => {
    modalCancelarAbierto.value = false;
    reservaSeleccionada.value = null;
    formCancelar.reset();
};

const ejecutarCancelacion = () => {
    if (!reservaSeleccionada.value) return;
    formCancelar.post(`/admin/reservas/${reservaSeleccionada.value.id}/cancelar`, {
        preserveScroll: true,
        onSuccess: () => cerrarModalCancelar(),
    });
};

// Modal Reprogramar
const modalReprogramarAbierto = ref(false);
const formReprogramar = useForm({
    nueva_fecha: '',
    nueva_hora_inicio: '',
});

const abrirModalReprogramar = (reserva) => {
    reservaSeleccionada.value = reserva;
    formReprogramar.nueva_fecha = reserva.fecha_raw;
    formReprogramar.nueva_hora_inicio = reserva.hora_inicio;
    modalReprogramarAbierto.value = true;
};

const cerrarModalReprogramar = () => {
    modalReprogramarAbierto.value = false;
    reservaSeleccionada.value = null;
    formReprogramar.reset();
};

const ejecutarReprogramacion = () => {
    if (!reservaSeleccionada.value) return;
    formReprogramar.post(`/admin/reservas/${reservaSeleccionada.value.id}/reprogramar`, {
        preserveScroll: true,
        onSuccess: () => cerrarModalReprogramar(),
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

// Notificación sonora suave con Web Audio API
const reproducirCampana = () => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
        osc.frequency.exponentialRampToValueAtTime(440, audioCtx.currentTime + 0.5);
        gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.5);
    } catch {
        // Silencio si no tiene soporte de audio
    }
};

// WebSocket para reservas en tiempo real
onMounted(() => {
    if (window.Echo && props.sucursal_id) {
        window.Echo.private(`sucursal.${props.sucursal_id}.reservas`)
            .listen('.reserva.creada', () => {
                reproducirCampana();
                router.reload({ only: ['reservas'] });
            })
            .listen('.reserva.actualizada', () => {
                router.reload({ only: ['reservas'] });
            });
    }
});

onUnmounted(() => {
    if (window.Echo && props.sucursal_id) {
        window.Echo.leave(`sucursal.${props.sucursal_id}.reservas`);
    }
});
</script>
