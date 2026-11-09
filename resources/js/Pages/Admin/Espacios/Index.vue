<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado de la página -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>🪑</span>
                        <span>Mapa de Mesas y Espacios en Vivo</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Distribución del salón en tiempo real (WebSocket sucursal.{{ sucursal_id }}.mesas).
                    </p>
                </div>

                <!-- Acciones superiores: Sucursal y Crear Mesa -->
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

                    <button
                        type="button"
                        @click="abrirModalCrear"
                        class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        <span>+</span>
                        <span>Nuevo Espacio</span>
                    </button>
                </div>
            </div>

            <!-- Resumen de Estados (Pills) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white p-3.5 rounded-2xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs text-stone-500 font-medium">Libres</span>
                        <p class="text-xl font-black text-emerald-600">{{ conteos.libre }}</p>
                    </div>
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                </div>
                <div class="bg-white p-3.5 rounded-2xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs text-stone-500 font-medium">Ocupadas</span>
                        <p class="text-xl font-black text-rose-600">{{ conteos.ocupado }}</p>
                    </div>
                    <span class="w-3 h-3 rounded-full bg-rose-500 animate-pulse"></span>
                </div>
                <div class="bg-white p-3.5 rounded-2xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs text-stone-500 font-medium">Reservadas</span>
                        <p class="text-xl font-black text-amber-600">{{ conteos.reservado }}</p>
                    </div>
                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                </div>
                <div class="bg-white p-3.5 rounded-2xl border border-stone-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs text-stone-500 font-medium">Fuera de Servicio</span>
                        <p class="text-xl font-black text-stone-500">{{ conteos.fuera_servicio }}</p>
                    </div>
                    <span class="w-3 h-3 rounded-full bg-stone-400"></span>
                </div>
            </div>

            <!-- Grilla del Mapa de Mesas / Salón -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div
                    v-for="espacio in espacios"
                    :key="espacio.id"
                    :class="[
                        'bg-white rounded-2xl border p-5 shadow-xs transition-all relative flex flex-col justify-between',
                        bordeEstado(espacio.estado)
                    ]"
                >
                    <div>
                        <!-- Encabezado de la Mesa -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-lg">
                                {{ iconoTipo(espacio.tipo) }}
                            </span>
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                    badgeEstado(espacio.estado)
                                ]"
                            >
                                {{ espacio.estado_etiqueta }}
                            </span>
                        </div>

                        <!-- Nombre y Tipo -->
                        <h3 class="font-bold text-base text-stone-900 tracking-tight">
                            {{ espacio.nombre }}
                        </h3>
                        <p class="text-xs text-stone-500 mt-0.5">
                            {{ espacio.tipo_mesa_nombre || espacio.tipo }} · Capacidad: <strong class="text-stone-700">{{ espacio.capacidad }} personas</strong>
                        </p>

                        <!-- Pedido Activo en Salón (si ocupado) -->
                        <div v-if="espacio.pedido_activo" class="mt-3 p-2.5 bg-orange-50 border border-orange-200 rounded-xl text-xs">
                            <div class="flex justify-between items-center text-orange-900 font-semibold">
                                <span>Pedido #{{ espacio.pedido_activo.numero }}</span>
                                <span class="font-bold">${{ espacio.pedido_activo.total.toLocaleString() }}</span>
                            </div>
                            <a
                                :href="`/admin/pedidos?tab=todos`"
                                class="text-[10px] text-orange-600 hover:text-orange-800 font-semibold block mt-1"
                            >
                                Ver en monitor →
                            </a>
                        </div>
                    </div>

                    <!-- Fila de Acciones rápidas -->
                    <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between">
                        <!-- Selector rápido de estado -->
                        <select
                            :value="espacio.estado"
                            @change="cambiarEstadoRapido(espacio, $event.target.value)"
                            class="text-[11px] rounded-lg border border-stone-200 bg-stone-50 px-2 py-1 font-semibold text-stone-700 focus:ring-1 focus:ring-orange-500"
                        >
                            <option value="libre">Libre</option>
                            <option value="ocupado">Ocupado</option>
                            <option value="reservado">Reservado</option>
                            <option value="fuera_servicio">Fuera de serv.</option>
                        </select>

                        <div class="flex items-center space-x-1.5">
                            <button
                                type="button"
                                @click="abrirModalEditar(espacio)"
                                class="p-1.5 text-stone-500 hover:text-stone-800 hover:bg-stone-100 rounded-lg transition"
                                title="Editar mesa"
                            >
                                ✏️
                            </button>
                            <button
                                type="button"
                                @click="eliminarEspacio(espacio)"
                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition"
                                title="Enviar a papelera"
                            >
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado vacío si no hay espacios -->
            <div v-if="espacios.length === 0" class="bg-white rounded-2xl border border-stone-200 p-12 text-center">
                <span class="text-4xl block mb-2">🪑</span>
                <h3 class="text-base font-bold text-stone-900">No hay mesas o espacios registrados</h3>
                <p class="text-xs text-stone-500 mt-1 max-w-sm mx-auto">
                    Comienza agregando las mesas, barras o salones disponibles en esta sucursal.
                </p>
                <button
                    type="button"
                    @click="abrirModalCrear"
                    class="mt-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold shadow-xs transition"
                >
                    + Crear Primera Mesa
                </button>
            </div>
        </div>

        <!-- Modal Crear/Editar Espacio -->
        <div v-if="modalEspacioAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        {{ espacioEditando ? `Editar ${espacioEditando.nombre}` : 'Nuevo Espacio / Mesa' }}
                    </h3>
                    <button @click="cerrarModalEspacio" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <form @submit.prevent="guardarEspacio" class="space-y-4 text-xs">
                    <div>
                        <label for="nombre_espacio" class="block font-semibold text-stone-700 mb-1">
                            Nombre del Espacio *
                        </label>
                        <input
                            id="nombre_espacio"
                            type="text"
                            v-model="formEspacio.nombre"
                            placeholder="Ej. Mesa 1, Barra 2, Salón VIP..."
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="tipo_espacio" class="block font-semibold text-stone-700 mb-1">
                                Tipo de Espacio *
                            </label>
                            <select
                                id="tipo_espacio"
                                v-model="formEspacio.tipo"
                                class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                                required
                            >
                                <option value="mesa">Mesa</option>
                                <option value="barra">Barra</option>
                                <option value="salon">Salón</option>
                                <option value="terraza">Terraza</option>
                            </select>
                        </div>

                        <div>
                            <label for="capacidad_espacio" class="block font-semibold text-stone-700 mb-1">
                                Capacidad Máx. *
                            </label>
                            <input
                                id="capacidad_espacio"
                                type="number"
                                min="1"
                                max="100"
                                v-model.number="formEspacio.capacidad"
                                class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label for="tipo_mesa_id" class="block font-semibold text-stone-700 mb-1">
                            Clasificación de Capacidad (Tipo de Mesa)
                        </label>
                        <select
                            id="tipo_mesa_id"
                            v-model="formEspacio.tipo_mesa_id"
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                        >
                            <option :value="null">Automático / Ninguno</option>
                            <option v-for="tm in tiposMesa" :key="tm.id" :value="tm.id">
                                {{ tm.nombre }} ({{ tm.capacidad_minima }}-{{ tm.capacidad_maxima }} pers.{{ tm.es_abierta ? ', abierta' : '' }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="estado_espacio" class="block font-semibold text-stone-700 mb-1">
                            Estado Inicial *
                        </label>
                        <select
                            id="estado_espacio"
                            v-model="formEspacio.estado"
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                            required
                        >
                            <option value="libre">Libre</option>
                            <option value="ocupado">Ocupado</option>
                            <option value="reservado">Reservado</option>
                            <option value="fuera_servicio">Fuera de servicio</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-2 pt-3 border-t border-stone-100">
                        <button type="button" @click="cerrarModalEspacio" class="px-4 py-2 rounded-xl border border-stone-200 text-xs font-medium text-stone-600 hover:bg-stone-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formEspacio.processing" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold transition">
                            {{ formEspacio.processing ? 'Guardando...' : (espacioEditando ? 'Actualizar' : 'Crear Espacio') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    espacios: { type: Array, required: true },
    tiposMesa: { type: Array, default: () => [] },
    sucursales: { type: Array, default: () => [] },
    sucursal_id: { type: Number, required: true },
});

const sucursalSeleccionada = ref(props.sucursal_id);

const conteos = computed(() => {
    return {
        libre: props.espacios.filter(e => e.estado === 'libre').length,
        ocupado: props.espacios.filter(e => e.estado === 'ocupado').length,
        reservado: props.espacios.filter(e => e.estado === 'reservado').length,
        fuera_servicio: props.espacios.filter(e => e.estado === 'fuera_servicio').length,
    };
});

const cambiarSucursal = () => {
    router.get('/admin/espacios', {
        sucursal_id: sucursalSeleccionada.value,
    });
};

// Modal Crear/Editar
const modalEspacioAbierto = ref(false);
const espacioEditando = ref(null);

const formEspacio = useForm({
    sucursal_id: props.sucursal_id,
    nombre: '',
    tipo: 'mesa',
    tipo_mesa_id: null,
    capacidad: 4,
    estado: 'libre',
});

const abrirModalCrear = () => {
    espacioEditando.value = null;
    formEspacio.reset();
    formEspacio.sucursal_id = props.sucursal_id;
    formEspacio.tipo = 'mesa';
    formEspacio.capacidad = 4;
    formEspacio.estado = 'libre';
    modalEspacioAbierto.value = true;
};

const abrirModalEditar = (espacio) => {
    espacioEditando.value = espacio;
    formEspacio.sucursal_id = props.sucursal_id;
    formEspacio.nombre = espacio.nombre;
    formEspacio.tipo = espacio.tipo;
    formEspacio.tipo_mesa_id = espacio.tipo_mesa_id;
    formEspacio.capacidad = espacio.capacidad;
    formEspacio.estado = espacio.estado;
    modalEspacioAbierto.value = true;
};

const cerrarModalEspacio = () => {
    modalEspacioAbierto.value = false;
    espacioEditando.value = null;
    formEspacio.reset();
};

const guardarEspacio = () => {
    if (espacioEditando.value) {
        formEspacio.put(`/admin/espacios/${espacioEditando.value.id}`, {
            preserveScroll: true,
            onSuccess: () => cerrarModalEspacio(),
        });
    } else {
        formEspacio.post('/admin/espacios', {
            preserveScroll: true,
            onSuccess: () => cerrarModalEspacio(),
        });
    }
};

const cambiarEstadoRapido = (espacio, nuevoEstado) => {
    router.put(`/admin/espacios/${espacio.id}`, {
        nombre: espacio.nombre,
        tipo: espacio.tipo,
        tipo_mesa_id: espacio.tipo_mesa_id,
        capacidad: espacio.capacidad,
        estado: nuevoEstado,
    }, {
        preserveScroll: true,
    });
};

const eliminarEspacio = (espacio) => {
    if (confirm(`¿Estás seguro de eliminar "${espacio.nombre}"? Si tiene pedidos o reservas activas, la operación será rechazada.`)) {
        router.delete(`/admin/espacios/${espacio.id}`, {
            preserveScroll: true,
        });
    }
};

const iconoTipo = (tipo) => {
    return {
        'mesa': '🪑',
        'barra': '🍸',
        'salon': '🏛️',
        'terraza': '🌿',
    }[tipo] || '🪑';
};

const bordeEstado = (estado) => {
    return {
        'libre': 'border-emerald-300 hover:border-emerald-400',
        'ocupado': 'border-rose-400 hover:border-rose-500 bg-rose-50/10',
        'reservado': 'border-amber-300 hover:border-amber-400 bg-amber-50/10',
        'fuera_servicio': 'border-stone-300 opacity-60',
    }[estado] || 'border-stone-200';
};

const badgeEstado = (estado) => {
    return {
        'libre': 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        'ocupado': 'bg-rose-100 text-rose-800 border border-rose-200',
        'reservado': 'bg-amber-100 text-amber-800 border border-amber-200',
        'fuera_servicio': 'bg-stone-100 text-stone-700 border border-stone-200',
    }[estado] || 'bg-stone-100 text-stone-700';
};

// WebSocket para mapa de mesas en tiempo real
onMounted(() => {
    if (window.Echo && props.sucursal_id) {
        window.Echo.channel(`sucursal.${props.sucursal_id}.mesas`)
            .listen('.espacio.actualizado', () => {
                router.reload({ only: ['espacios'] });
            });
    }
});

onUnmounted(() => {
    if (window.Echo && props.sucursal_id) {
        window.Echo.leave(`sucursal.${props.sucursal_id}.mesas`);
    }
});
</script>
