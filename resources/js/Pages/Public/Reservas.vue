<template>
    <PublicLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <!-- Encabezado de la página -->
            <div class="text-center mb-10">
                <span class="inline-block p-3 rounded-2xl bg-orange-100 text-orange-600 text-3xl mb-3 shadow-xs">
                    📅
                </span>
                <h1 class="text-3xl font-extrabold tracking-tight text-stone-900 sm:text-4xl">
                    Reserva tu Mesa
                </h1>
                <p class="mt-2 text-stone-600 max-w-xl mx-auto text-sm sm:text-base">
                    Disfruta de una experiencia gastronómica inolvidable. Selecciona tu sede, fecha, hora y confirma tu lugar al instante.
                </p>
            </div>

            <!-- Contenedor del formulario interactivo -->
            <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6 sm:p-8">
                <form @submit.prevent="submitReserva" class="space-y-8">
                    <!-- Paso 1: Selección de Sucursal y Personas -->
                    <div>
                        <h2 class="text-base font-bold text-stone-900 flex items-center space-x-2 border-b border-stone-100 pb-3 mb-5">
                            <span class="w-6 h-6 rounded-full bg-orange-600 text-white text-xs flex items-center justify-center font-bold">1</span>
                            <span>Sucursal y Comensales</span>
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Selector de Sucursal -->
                            <div>
                                <label for="sucursal" class="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1.5">
                                    Sucursal / Sede
                                </label>
                                <select
                                    id="sucursal"
                                    v-model="form.sucursal_id"
                                    @change="onSucursalChange"
                                    class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-stone-800 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-hidden transition"
                                    required
                                >
                                    <option v-for="suc in sucursales" :key="suc.id" :value="suc.id">
                                        {{ suc.nombre }} - {{ suc.direccion }}
                                    </option>
                                </select>
                            </div>

                            <!-- Número de Personas -->
                            <div>
                                <label for="personas" class="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1.5">
                                    Número de Personas
                                </label>
                                <div class="flex items-center space-x-3">
                                    <input
                                        id="personas"
                                        type="number"
                                        min="1"
                                        max="50"
                                        v-model.number="form.personas"
                                        @change="cargarFranjas"
                                        class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-stone-800 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-hidden transition"
                                        required
                                    />
                                    <span class="text-xs text-stone-500 whitespace-nowrap">
                                        {{ form.personas >= 10 ? '👥 Grupo grande' : '🍽️ Mesa' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Fecha y Ocasión -->
                    <div>
                        <h2 class="text-base font-bold text-stone-900 flex items-center space-x-2 border-b border-stone-100 pb-3 mb-5">
                            <span class="w-6 h-6 rounded-full bg-orange-600 text-white text-xs flex items-center justify-center font-bold">2</span>
                            <span>Fecha y Motivo</span>
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Selector de Fecha -->
                            <div>
                                <label for="fecha" class="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1.5">
                                    Fecha de la Reserva
                                </label>
                                <input
                                    id="fecha"
                                    type="date"
                                    v-model="form.fecha"
                                    :min="minFecha"
                                    :max="maxFecha"
                                    @change="cargarFranjas"
                                    class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-stone-800 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-hidden transition"
                                    required
                                />
                            </div>

                            <!-- Ocasión / Categoría -->
                            <div>
                                <label for="categoria" class="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1.5">
                                    Ocasión Especial
                                </label>
                                <select
                                    id="categoria"
                                    v-model="form.categoria_reserva_id"
                                    class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-stone-800 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-hidden transition"
                                >
                                    <option :value="null">Mesa estándar / Sin motivo especial</option>
                                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                                        {{ cat.nombre }} {{ cat.descripcion ? `(${cat.descripcion})` : '' }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Selección de Franja Horaria en Tiempo Real -->
                    <div>
                        <div class="flex items-center justify-between border-b border-stone-100 pb-3 mb-5">
                            <h2 class="text-base font-bold text-stone-900 flex items-center space-x-2">
                                <span class="w-6 h-6 rounded-full bg-orange-600 text-white text-xs flex items-center justify-center font-bold">3</span>
                                <span>Franja Horaria Disponible</span>
                            </h2>
                            <span v-if="cargandoFranjas" class="text-xs text-orange-600 flex items-center space-x-1 animate-pulse">
                                <span>Verificando mesas...</span>
                            </span>
                        </div>

                        <!-- Estado cargando -->
                        <div v-if="cargandoFranjas" class="py-10 text-center">
                            <div class="inline-block w-7 h-7 border-3 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
                            <p class="text-xs text-stone-500 mt-2">Consultando disponibilidad en vivo...</p>
                        </div>

                        <!-- Grilla de Franjas -->
                        <div v-else-if="franjas.length > 0" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-2.5">
                            <button
                                v-for="franja in franjas"
                                :key="franja.hora_inicio"
                                type="button"
                                :disabled="!franja.disponible"
                                @click="form.hora_inicio = franja.hora_inicio"
                                :class="[
                                    'py-2.5 px-3 rounded-xl border text-center transition-all duration-150 flex flex-col items-center justify-center relative',
                                    form.hora_inicio === franja.hora_inicio
                                        ? 'bg-orange-600 border-orange-600 text-white ring-2 ring-orange-300 font-bold shadow-xs'
                                        : franja.disponible
                                            ? 'bg-white border-stone-200 text-stone-800 hover:border-orange-500 hover:bg-orange-50/50 cursor-pointer'
                                            : 'bg-stone-50 border-stone-200 text-stone-400 opacity-50 cursor-not-allowed'
                                ]"
                            >
                                <span class="text-sm font-semibold">{{ franja.hora_inicio }}</span>
                                <span
                                    :class="[
                                        'text-[10px] mt-0.5',
                                        form.hora_inicio === franja.hora_inicio ? 'text-orange-100' : 'text-stone-400'
                                    ]"
                                >
                                    {{ franja.disponible ? `${franja.hora_fin}` : (franja.motivo || 'Ocupado') }}
                                </span>
                            </button>
                        </div>

                        <!-- Sin franjas disponibles -->
                        <div v-else class="p-6 bg-stone-50 rounded-xl border border-stone-200 text-center">
                            <p class="text-stone-600 text-sm font-medium">
                                No hay franjas horarias disponibles para los criterios seleccionados.
                            </p>
                            <p class="text-xs text-stone-400 mt-1">
                                Intenta cambiando la fecha o el número de comensales.
                            </p>
                        </div>

                        <p v-if="form.errors.hora_inicio" class="text-xs text-rose-600 mt-2">
                            {{ form.errors.hora_inicio }}
                        </p>
                    </div>

                    <!-- Paso 4: Datos de Contacto -->
                    <div>
                        <h2 class="text-base font-bold text-stone-900 flex items-center space-x-2 border-b border-stone-100 pb-3 mb-5">
                            <span class="w-6 h-6 rounded-full bg-orange-600 text-white text-xs flex items-center justify-center font-bold">4</span>
                            <span>Datos de Contacto</span>
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Nombre -->
                            <div>
                                <label for="nombre" class="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1.5">
                                    Nombre Completo
                                </label>
                                <input
                                    id="nombre"
                                    type="text"
                                    v-model="form.nombre_contacto"
                                    placeholder="Ej. Juan Pérez"
                                    class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-stone-800 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-hidden transition"
                                    required
                                />
                                <p v-if="form.errors.nombre_contacto" class="text-xs text-rose-600 mt-1">
                                    {{ form.errors.nombre_contacto }}
                                </p>
                            </div>

                            <!-- Correo -->
                            <div>
                                <label for="email" class="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1.5">
                                    Correo Electrónico
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email_contacto"
                                    placeholder="correo@ejemplo.com"
                                    class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-stone-800 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-hidden transition"
                                    required
                                />
                                <p v-if="form.errors.email_contacto" class="text-xs text-rose-600 mt-1">
                                    {{ form.errors.email_contacto }}
                                </p>
                            </div>

                            <!-- Teléfono -->
                            <div>
                                <label for="telefono" class="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1.5">
                                    Teléfono Celular
                                </label>
                                <input
                                    id="telefono"
                                    type="tel"
                                    v-model="form.telefono_contacto"
                                    placeholder="300 123 4567"
                                    class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-stone-800 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-hidden transition"
                                    required
                                />
                                <p v-if="form.errors.telefono_contacto" class="text-xs text-rose-600 mt-1">
                                    {{ form.errors.telefono_contacto }}
                                </p>
                            </div>
                        </div>

                        <!-- Notas especiales -->
                        <div class="mt-4">
                            <label for="notas" class="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1.5">
                                Peticiones Especiales (Opcional)
                            </label>
                            <textarea
                                id="notas"
                                v-model="form.notas"
                                rows="2"
                                placeholder="Alergias alimentarias, preferencia de ubicación, decoración especial..."
                                class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-stone-800 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-hidden transition"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Mensaje informativo de cuenta implícita y política de datos -->
                    <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4 text-xs text-amber-900 space-y-1">
                        <p class="font-semibold flex items-center space-x-1.5">
                            <span>ℹ️</span>
                            <span>Información importante sobre tu reserva:</span>
                        </p>
                        <p>
                            • Si es tu primera vez reservando con nosotros, crearemos de forma automática tu cuenta de cliente y te enviaremos un correo con un enlace seguro válido por 72 horas para definir tu contraseña.
                        </p>
                        <p>
                            • Al continuar, aceptas el tratamiento de datos personales conforme a la <strong>Ley 1581 de 2012</strong>.
                        </p>
                    </div>

                    <!-- Botón de Envío -->
                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing || !form.hora_inicio"
                            class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm shadow-md transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
                        >
                            <span v-if="form.processing">Agendando reserva...</span>
                            <span v-else>Confirmar Reserva</span>
                            <span>→</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    sucursales: {
        type: Array,
        required: true,
    },
    sucursalActiva: {
        type: Object,
        default: null,
    },
    categorias: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const authUser = page.props.auth?.user;

// Cálculo de límites de fechas (hoy hasta 60 días)
const hoy = new Date();
const minFecha = hoy.toISOString().split('T')[0];
const maxFechaObj = new Date();
maxFechaObj.setDate(hoy.getDate() + 60);
const maxFecha = maxFechaObj.toISOString().split('T')[0];

const form = useForm({
    sucursal_id: props.sucursalActiva?.id || (props.sucursales[0]?.id ?? 1),
    fecha: minFecha,
    hora_inicio: '',
    personas: 2,
    categoria_reserva_id: null,
    nombre_contacto: authUser?.name || '',
    email_contacto: authUser?.email || '',
    telefono_contacto: authUser?.telefono || '',
    notas: '',
});

const franjas = ref([]);
const cargandoFranjas = ref(false);

const cargarFranjas = async () => {
    if (!form.sucursal_id || !form.fecha || !form.personas) return;

    cargandoFranjas.value = true;
    form.hora_inicio = '';

    try {
        const response = await axios.get('/reservas/franjas', {
            params: {
                sucursal_id: form.sucursal_id,
                fecha: form.fecha,
                personas: form.personas,
            },
        });
        franjas.value = response.data.franjas || [];
    } catch (error) {
        console.error('Error cargando franjas de disponibilidad:', error);
        franjas.value = [];
    } finally {
        cargandoFranjas.value = false;
    }
};

const onSucursalChange = () => {
    // Recargar vista para refrescar categorías de la sucursal seleccionada
    const selSuc = props.sucursales.find(s => s.id === form.sucursal_id);
    if (selSuc) {
        window.location.href = `/reservar?sucursal=${selSuc.slug}`;
    }
};

const submitReserva = () => {
    form.post('/reservas');
};

onMounted(() => {
    cargarFranjas();
});
</script>
