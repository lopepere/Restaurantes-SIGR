<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Encabezado de Monitor de Pedidos en Vivo -->
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-stone-200 pb-5">
                <div>
                    <h1 class="text-2xl font-black text-stone-900 tracking-tight flex items-center gap-2">
                        <span>🛎️ Monitor de Pedidos en Tiempo Real</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1.5"></span>
                            En Vivo
                        </span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">Control de comandas, cocina, estados de entrega y solicitudes de cancelación.</p>
                </div>

                <!-- Botón de sonido de alertas -->
                <button
                    type="button"
                    @click="toggleSonido"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold border transition flex items-center gap-2 cursor-pointer"
                    :class="sonidoHabilitado ? 'bg-orange-50 border-orange-200 text-orange-800' : 'bg-stone-50 border-stone-200 text-stone-600'"
                >
                    <span>{{ sonidoHabilitado ? '🔔 Alertas Sonoras Activas' : '🔕 Alertas Sonoras Silenciadas' }}</span>
                </button>
            </div>

            <!-- Banner Alerta de Cancelación Pendiente -->
            <div v-if="alertasCancelacionCount > 0" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center justify-between text-rose-800 animate-pulse">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <h4 class="font-black text-sm">Hay {{ alertasCancelacionCount }} solicitud(es) de cancelación esperando respuesta</h4>
                        <p class="text-xs opacity-90">Revisa la pestaña "Solicitudes de cancelación" para aprobar o rechazar la anulación.</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="cambiarPestana('solicitudes_cancelacion')"
                    class="px-4 py-1.5 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition"
                >
                    Ver Solicitudes
                </button>
            </div>

            <!-- Pestañas Principales (Prompt A.6) -->
            <div class="flex items-center gap-2 border-b border-stone-200 overflow-x-auto pb-px">
                <button
                    v-for="t in pestañasDisponibles"
                    :key="t.key"
                    type="button"
                    @click="cambiarPestana(t.key)"
                    :class="[
                        'px-4 py-2.5 text-xs font-bold border-b-2 whitespace-nowrap transition cursor-pointer flex items-center gap-2',
                        tab === t.key
                            ? 'border-orange-600 text-orange-600 bg-orange-50/40 rounded-t-xl'
                            : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
                    ]"
                >
                    <span>{{ t.icono }}</span>
                    <span>{{ t.label }}</span>
                </button>
            </div>

            <!-- Barra de Filtros (A.6) -->
            <div class="bg-white rounded-2xl border border-stone-200 p-4 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <input
                        v-model="filtroNumero"
                        @keyup.enter="aplicarFiltros"
                        type="text"
                        placeholder="Buscar por número (PED-...)"
                        class="px-3 py-1.5 text-xs rounded-xl border border-stone-200 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    />
                    <input
                        v-model="filtroNombre"
                        @keyup.enter="aplicarFiltros"
                        type="text"
                        placeholder="Buscar por cliente o teléfono"
                        class="px-3 py-1.5 text-xs rounded-xl border border-stone-200 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    />
                    <select
                        v-model="filtroEstado"
                        @change="aplicarFiltros"
                        class="px-3 py-1.5 text-xs rounded-xl border border-stone-200 focus:outline-none focus:ring-1 focus:ring-orange-500"
                    >
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="confirmado">Confirmado</option>
                        <option value="en_preparacion">En preparación</option>
                        <option value="listo">Listo</option>
                        <option value="entregado">Entregado</option>
                        <option value="pagado">Pagado</option>
                        <option value="cancelacion_solicitada">Cancelación solicitada</option>
                        <option value="cancelado">Cancelado</option>
                    </select>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="aplicarFiltros"
                            class="flex-1 py-1.5 px-3 bg-stone-900 hover:bg-black text-white font-bold text-xs rounded-xl transition"
                        >
                            Filtrar
                        </button>
                        <button
                            type="button"
                            @click="limpiarFiltros"
                            class="py-1.5 px-3 bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold text-xs rounded-xl transition"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Listado en Acordeón de Pedidos (Prompt A.6) -->
            <div v-if="pedidos.data.length === 0" class="text-center py-16 bg-white rounded-3xl border border-stone-200 p-8">
                <div class="text-4xl mb-3">🛎️</div>
                <h3 class="text-base font-bold text-stone-800">No hay pedidos en esta vista</h3>
                <p class="text-xs text-stone-500 mt-1">Los pedidos entrantes aparecerán automáticamente aquí sin recargar la página.</p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="p in pedidos.data"
                    :key="p.id"
                    class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden transition"
                >
                    <!-- Fila de Encabezado / Resumen (Clic para expandir acordeón) -->
                    <div
                        @click="toggleAcordeon(p.id)"
                        class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer hover:bg-stone-50/70 transition"
                    >
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="font-black text-base text-stone-900 tracking-wide">{{ p.numero }}</span>
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold', matchBadgeClass(p.estado)]">
                                {{ p.estado_etiqueta }}
                            </span>
                            <span v-if="p.mesa" class="px-2 py-0.5 rounded-full text-xs font-bold bg-stone-100 text-stone-800">
                                📍 {{ p.mesa }}
                            </span>
                            <span v-else class="px-2 py-0.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-600">
                                {{ p.tipo_etiqueta }}
                            </span>
                            <span v-if="p.solicito_cancelacion" class="px-2 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-700 animate-pulse">
                                ⚠️ Cancelación solicitada
                            </span>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-4 text-xs">
                            <div class="text-stone-600 text-right">
                                <span class="font-bold text-stone-900">{{ p.cliente_nombre }}</span>
                                <span v-if="p.cliente_telefono" class="text-stone-400 block">{{ p.cliente_telefono }}</span>
                            </div>

                            <div class="text-right">
                                <span class="font-black text-base text-orange-600 block">{{ formatearMoneda(p.total) }}</span>
                                <span class="text-[10px] text-stone-400">{{ p.fecha }}</span>
                            </div>

                            <span class="text-stone-400 text-sm ml-1 transform transition-transform" :class="acordeonAbierto === p.id ? 'rotate-180' : ''">
                                ▼
                            </span>
                        </div>
                    </div>

                    <!-- Cuerpo Desplegable del Acordeón -->
                    <div v-if="acordeonAbierto === p.id" class="p-5 border-t border-stone-100 bg-stone-50/30 space-y-5">
                        <!-- Datos de entrega y notas -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs bg-white p-4 rounded-xl border border-stone-200/80">
                            <div>
                                <span class="text-stone-400 font-semibold uppercase block">Cliente</span>
                                <p class="font-bold text-stone-900 mt-0.5">{{ p.cliente_nombre }}</p>
                                <p class="text-stone-500">{{ p.cliente_telefono || 'Sin teléfono' }}</p>
                            </div>
                            <div>
                                <span class="text-stone-400 font-semibold uppercase block">Modalidad & Mesa</span>
                                <p class="font-bold text-stone-900 mt-0.5">{{ p.tipo_etiqueta }}</p>
                                <p class="text-stone-500">{{ p.mesa ? `Mesa: ${p.mesa}` : (p.direccion_entrega || 'En sala') }}</p>
                            </div>
                            <div>
                                <span class="text-stone-400 font-semibold uppercase block">Mesero Asignado</span>
                                <p class="font-bold text-stone-900 mt-0.5">{{ p.mesero_nombre || 'Autoservicio / Online' }}</p>
                                <p v-if="p.notas" class="text-orange-700 italic mt-0.5">Nota: {{ p.notas }}</p>
                            </div>
                        </div>

                        <!-- Desglose de Ítems del Pedido -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-stone-700 mb-2">Comanda Detallada</h4>
                            <div class="divide-y divide-stone-100 bg-white rounded-xl border border-stone-200 overflow-hidden text-xs">
                                <div
                                    v-for="item in p.items"
                                    :key="item.id"
                                    class="p-3 flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 flex items-center justify-center bg-orange-100 text-orange-800 font-black rounded-md">
                                            {{ item.cantidad }}
                                        </span>
                                        <div>
                                            <p class="font-bold text-stone-900">{{ item.nombre }}</p>
                                            <p v-if="item.notas" class="text-stone-500 italic">Especial: {{ item.notas }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-bold text-stone-900">{{ formatearMoneda(item.total) }}</span>
                                        <span class="text-[10px] text-stone-400 block">{{ formatearMoneda(item.precio_unitario) }} c/u</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Transición de Estados (ERS 8.3) -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2">
                            <!-- Si hay cancelación solicitada, mostrar botones de resolución (CA-09) -->
                            <div v-if="p.solicito_cancelacion" class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="abrirModalResolucion(p, true)"
                                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
                                >
                                    ✓ Aprobar Cancelación
                                </button>
                                <button
                                    type="button"
                                    @click="abrirModalResolucion(p, false)"
                                    class="px-4 py-2 bg-stone-200 hover:bg-stone-300 text-stone-800 font-bold text-xs rounded-xl transition cursor-pointer"
                                >
                                    ✕ Rechazar Cancelación
                                </button>
                            </div>

                            <!-- Botones para cambiar a los siguientes estados permitidos -->
                            <div v-else class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-semibold text-stone-500 mr-1">Avanzar a:</span>
                                <button
                                    v-for="trans in p.transiciones_permitidas"
                                    :key="trans.value"
                                    type="button"
                                    @click="cambiarEstadoPedido(p, trans.value)"
                                    class="px-3.5 py-1.5 bg-stone-900 hover:bg-black text-white font-bold text-xs rounded-xl transition cursor-pointer"
                                >
                                    {{ trans.etiqueta }}
                                </button>
                            </div>

                            <div class="text-right text-xs text-stone-500">
                                <span>Subtotal: {{ formatearMoneda(p.subtotal) }}</span>
                                <span v-if="p.propina > 0" class="ml-2 text-emerald-700 font-semibold">+ Propina: {{ formatearMoneda(p.propina) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paginación -->
                <div v-if="pedidos.links.length > 3" class="flex justify-center gap-2 pt-4">
                    <template v-for="(link, i) in pedidos.links" :key="i">
                        <a
                            v-if="link.url"
                            :href="link.url"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition', link.active ? 'bg-orange-600 text-white' : 'bg-white border border-stone-200 text-stone-700 hover:bg-stone-50']"
                            v-html="link.label"
                        ></a>
                    </template>
                </div>
            </div>

            <!-- Modal de Resolución de Cancelación (CA-09) -->
            <div v-if="mostrarModalResolucion" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-base font-bold text-stone-900 mb-2">
                        {{ aprobandoCancelacion ? 'Aprobar Cancelación de Pedido' : 'Rechazar Cancelación de Pedido' }}
                    </h3>
                    <p class="text-xs text-stone-500 mb-3">
                        Motivo del cliente: <strong class="text-stone-800">{{ pedidoResolucion?.motivo_cancelacion }}</strong>
                    </p>
                    <textarea
                        v-model="respuestaAdmin"
                        rows="3"
                        placeholder="Observación o motivo para el cliente..."
                        class="w-full px-3 py-2 text-sm rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-orange-500"
                    ></textarea>
                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="mostrarModalResolucion = false"
                            class="px-4 py-2 text-xs font-bold text-stone-600 hover:bg-stone-100 rounded-xl"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="confirmarResolucion"
                            :class="['px-4 py-2 text-xs font-bold text-white rounded-xl', aprobandoCancelacion ? 'bg-rose-600 hover:bg-rose-700' : 'bg-stone-900 hover:bg-black']"
                        >
                            Confirmar Resolución
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    pedidos: {
        type: Object,
        required: true,
    },
    tab: {
        type: String,
        default: 'en_curso',
    },
    filtros: {
        type: Object,
        default: () => ({}),
    },
    sucursal_id: {
        type: Number,
        required: true,
    },
    sucursales: {
        type: Array,
        default: () => [],
    },
    meseros: {
        type: Array,
        default: () => [],
    },
    espacios: {
        type: Array,
        default: () => [],
    },
});

const acordeonAbierto = ref(null);
const sonidoHabilitado = ref(true);
const filtroNumero = ref(props.filtros.numero || '');
const filtroNombre = ref(props.filtros.nombre || '');
const filtroEstado = ref(props.filtros.estado || '');

const mostrarModalResolucion = ref(false);
const pedidoResolucion = ref(null);
const aprobandoCancelacion = ref(true);
const respuestaAdmin = ref('');

let pollingTimer = null;

const pestañasDisponibles = [
    { key: 'en_curso', label: 'En Curso / Cocina', icono: '🔥' },
    { key: 'solicitudes_cancelacion', label: 'Solicitudes Cancelación', icono: '⚠️' },
    { key: 'domicilios', label: 'Domicilios', icono: '🛵' },
    { key: 'para_llevar', label: 'Para Llevar', icono: '🛍️' },
    { key: 'en_sitio', label: 'En Sitio / Mesas', icono: '🍽️' },
    { key: 'todos', label: 'Todos los Pedidos', icono: '📋' },
];

const alertasCancelacionCount = computed(() => {
    return props.pedidos.data.filter(p => p.solicito_cancelacion).length;
});

function toggleAcordeon(pedidoId) {
    acordeonAbierto.value = acordeonAbierto.value === pedidoId ? null : pedidoId;
}

function cambiarPestana(nuevaPestana) {
    router.get('/admin/pedidos', { ...props.filtros, tab: nuevaPestana }, { preserveState: true, preserveScroll: true });
}

function aplicarFiltros() {
    router.get('/admin/pedidos', {
        ...props.filtros,
        numero: filtroNumero.value || undefined,
        nombre: filtroNombre.value || undefined,
        estado: filtroEstado.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function limpiarFiltros() {
    filtroNumero.value = '';
    filtroNombre.value = '';
    filtroEstado.value = '';
    router.get('/admin/pedidos', { tab: props.tab }, { preserveState: true, preserveScroll: true });
}

function matchBadgeClass(estado) {
    switch (estado) {
        case 'pendiente':
            return 'bg-amber-100 text-amber-800 border border-amber-200';
        case 'confirmado':
            return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'en_preparacion':
            return 'bg-orange-100 text-orange-800 border border-orange-200';
        case 'listo':
            return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        case 'entregado':
        case 'pagado':
            return 'bg-stone-800 text-white';
        case 'cancelacion_solicitada':
        case 'cancelado':
            return 'bg-rose-100 text-rose-800 border border-rose-200';
        default:
            return 'bg-stone-100 text-stone-800';
    }
}

function formatearMoneda(valor) {
    return '$ ' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function reproducirChimeSonoro() {
    if (!sonidoHabilitado.value) return;

    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();

        // Campanita de servicio sintetizada (dos tonos armónicos)
        const osc1 = ctx.createOscillator();
        const osc2 = ctx.createOscillator();
        const gain = ctx.createGain();

        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(880, ctx.currentTime); // Nota A5
        osc1.frequency.exponentialRampToValueAtTime(1760, ctx.currentTime + 0.1);

        osc2.type = 'triangle';
        osc2.frequency.setValueAtTime(1320, ctx.currentTime + 0.05);

        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.8);

        osc1.connect(gain);
        osc2.connect(gain);
        gain.connect(ctx.destination);

        osc1.start();
        osc2.start();
        osc1.stop(ctx.currentTime + 0.8);
        osc2.stop(ctx.currentTime + 0.8);
    } catch (e) {
        // En navegadores con autoplay bloqueado hasta interacción de usuario
    }
}

function toggleSonido() {
    sonidoHabilitado.value = !sonidoHabilitado.value;
    if (sonidoHabilitado.value) {
        reproducirChimeSonoro();
    }
}

function cambiarEstadoPedido(pedido, nuevoEstado) {
    router.post(`/admin/pedidos/${pedido.id}/estado`, {
        nuevo_estado: nuevoEstado,
    }, {
        preserveScroll: true,
    });
}

function abrirModalResolucion(pedido, aprobar) {
    pedidoResolucion.value = pedido;
    aprobandoCancelacion.value = aprobar;
    respuestaAdmin.value = '';
    mostrarModalResolucion.value = true;
}

function confirmarResolucion() {
    router.post(`/admin/pedidos/${pedidoResolucion.value.id}/resolver-cancelacion`, {
        aprobar: aprobandoCancelacion.value,
        respuesta_admin: respuestaAdmin.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            mostrarModalResolucion.value = false;
        },
    });
}

onMounted(() => {
    // 1. Escucha en tiempo real de canales de Reverb
    if (window.Echo) {
        window.Echo.private(`sucursal.${props.sucursal_id}.pedidos`)
            .listen('.PedidoCreado', (e) => {
                reproducirChimeSonoro();
                router.reload({ only: ['pedidos'], preserveScroll: true, preserveState: true });
            })
            .listen('.PedidoActualizado', (e) => {
                if (e.alerta_sonora) {
                    reproducirChimeSonoro();
                }
                router.reload({ only: ['pedidos'], preserveScroll: true, preserveState: true });
            })
            .listen('.CancelacionSolicitada', (e) => {
                reproducirChimeSonoro();
                router.reload({ only: ['pedidos'], preserveScroll: true, preserveState: true });
            });
    }

    // 2. Polling de respaldo de 15 segundos (Prompt A.6)
    pollingTimer = setInterval(() => {
        router.reload({ only: ['pedidos'], preserveScroll: true, preserveState: true });
    }, 15000);
});

onUnmounted(() => {
    if (pollingTimer) {
        clearInterval(pollingTimer);
    }
});
</script>
