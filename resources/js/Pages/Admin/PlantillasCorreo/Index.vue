<template>
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>✉️</span>
                        <span>Plantillas de Correo Electrónico</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Personalización segura de mensajes transaccionales, saneamiento HTMLPurifier y variables dinámicas sin Blade (RF-CFG-04..09, CA-34..36).
                    </p>
                </div>

                <!-- Selector de Sucursal -->
                <div v-if="sucursales && sucursales.length > 1">
                    <select
                        v-model="sucursalSeleccionada"
                        @change="cambiarSucursal"
                        class="rounded-xl border border-stone-300 bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 shadow-xs focus:ring-1 focus:ring-orange-500"
                    >
                        <option v-for="s in sucursales" :key="s.id" :value="s.id">
                            🏢 {{ s.nombre }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Contenedor Maestro-Detalle -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Columna Izquierda: Listado de Plantillas (4 columnas) -->
                <div class="lg:col-span-4 bg-white rounded-2xl border border-stone-200 shadow-xs p-4 space-y-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-stone-500 px-2 pb-2 border-b border-stone-100">
                        Plantillas del Sistema ({{ plantillas.length }})
                    </h2>

                    <div class="space-y-1.5 max-h-[700px] overflow-y-auto pr-1">
                        <button
                            v-for="p in plantillas"
                            :key="p.id"
                            type="button"
                            @click="seleccionarPlantilla(p)"
                            :class="[
                                'w-full text-left p-3 rounded-xl transition flex flex-col space-y-1 border',
                                plantillaActiva?.id === p.id
                                    ? 'bg-orange-50/70 border-orange-300 ring-1 ring-orange-400'
                                    : 'bg-white border-stone-100 hover:bg-stone-50'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-stone-900">{{ p.nombre }}</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Activa
                                </span>
                            </div>
                            <span class="text-[11px] font-mono text-stone-400">{{ p.clave }}</span>
                            <span class="text-xs text-stone-500 truncate">{{ p.asunto }}</span>
                        </button>
                    </div>
                </div>

                <!-- Columna Derecha: Editor y Vista Previa (8 columnas) -->
                <div v-if="plantillaActiva" class="lg:col-span-8 bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                    <!-- Cabecera del Panel -->
                    <div class="px-6 py-4 border-b border-stone-100 flex flex-wrap items-center justify-between gap-3 bg-stone-50/50">
                        <div>
                            <h2 class="text-sm font-bold text-stone-900">{{ plantillaActiva.nombre }}</h2>
                            <p class="text-xs text-stone-400 font-mono">Clave: {{ plantillaActiva.clave }}</p>
                        </div>

                        <!-- Selector de Modo / Pestañas -->
                        <div class="flex items-center space-x-2">
                            <button
                                type="button"
                                @click="pestanaActiva = 'editor'"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                                    pestanaActiva === 'editor'
                                        ? 'bg-orange-600 text-white shadow-xs'
                                        : 'bg-white text-stone-600 border border-stone-200 hover:bg-stone-50'
                                ]"
                            >
                                ✏️ Editor HTML
                            </button>
                            <button
                                type="button"
                                @click="cargarVistaPrevia"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                                    pestanaActiva === 'previa'
                                        ? 'bg-orange-600 text-white shadow-xs'
                                        : 'bg-white text-stone-600 border border-stone-200 hover:bg-stone-50'
                                ]"
                            >
                                👁️ Vista Previa
                            </button>
                        </div>
                    </div>

                    <!-- Pestaña 1: Editor -->
                    <div v-show="pestanaActiva === 'editor'" class="p-6 space-y-5">
                        <form @submit.prevent="guardarPlantilla" class="space-y-4">
                            <!-- Asunto -->
                            <div>
                                <label class="block text-xs font-bold text-stone-700 mb-1">
                                    Asunto del Correo
                                </label>
                                <input
                                    type="text"
                                    v-model="formPlantilla.asunto"
                                    required
                                    class="w-full rounded-xl border border-stone-300 px-3.5 py-2 text-xs font-medium text-stone-800 focus:ring-1 focus:ring-orange-500"
                                />
                            </div>

                            <!-- Variables Disponibles (Pills) -->
                            <div>
                                <label class="block text-xs font-bold text-stone-700 mb-1">
                                    Variables dinámicas disponibles (clic para copiar al portapapeles)
                                </label>
                                <div class="flex flex-wrap gap-1.5 p-2.5 bg-stone-50 rounded-xl border border-stone-200">
                                    <button
                                        v-for="varName in plantillaActiva.variables_disponibles"
                                        :key="varName"
                                        type="button"
                                        @click="copiarVariable(varName)"
                                        class="px-2 py-1 rounded-lg bg-white border border-stone-300 text-[11px] font-mono text-orange-700 hover:bg-orange-50 hover:border-orange-400 transition shadow-2xs"
                                        title="Copiar variable"
                                        v-text="'{{ ' + varName + ' }}'"
                                    ></button>
                                </div>
                                <p v-if="mensajeCopiado" class="text-[11px] text-emerald-600 mt-1 font-semibold animate-in fade-in">
                                    ✓ Variable {{ mensajeCopiado }} copiada al portapapeles
                                </p>
                            </div>

                            <!-- Cuerpo HTML -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-bold text-stone-700">
                                        Cuerpo HTML del Mensaje (Saneado con HTMLPurifier)
                                    </label>
                                    <span class="text-[10px] text-stone-400">Sin compilación Blade por seguridad</span>
                                </div>
                                <textarea
                                    id="cuerpoHtmlTextarea"
                                    v-model="formPlantilla.cuerpo_html"
                                    rows="14"
                                    required
                                    class="w-full font-mono text-xs rounded-xl border border-stone-300 p-3 bg-stone-900 text-stone-100 focus:ring-1 focus:ring-orange-500 leading-relaxed shadow-inner"
                                ></textarea>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-stone-100">
                                <button
                                    type="button"
                                    @click="enviarCorreoPrueba"
                                    :disabled="cargandoPrueba"
                                    class="px-4 py-2 rounded-xl border border-stone-300 bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold shadow-xs transition flex items-center space-x-1.5"
                                >
                                    <span>📬</span>
                                    <span>{{ cargandoPrueba ? 'Enviando prueba...' : 'Enviar correo de prueba a mi email' }}</span>
                                </button>

                                <button
                                    type="submit"
                                    :disabled="formPlantilla.processing"
                                    class="px-5 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-xs transition disabled:opacity-50"
                                >
                                    {{ formPlantilla.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Pestaña 2: Vista Previa -->
                    <div v-show="pestanaActiva === 'previa'" class="p-6 space-y-4">
                        <div v-if="cargandoVistaPrevia" class="p-12 text-center text-xs text-stone-400">
                            Generando vista previa con datos simulados...
                        </div>
                        <div v-else class="space-y-3">
                            <div class="p-3 bg-stone-100 rounded-xl border border-stone-200">
                                <span class="text-[11px] font-bold text-stone-500 uppercase">Asunto Renderizado:</span>
                                <p class="text-xs font-bold text-stone-900 mt-0.5">{{ datosVistaPrevia.asunto }}</p>
                            </div>

                            <div class="rounded-xl border border-stone-200 overflow-hidden shadow-inner bg-white">
                                <div class="p-2 bg-stone-100 text-[10px] text-stone-500 font-mono border-b border-stone-200 flex items-center justify-between">
                                    <span>Render HTMLPurifier</span>
                                    <span>Variables de prueba sustituidas</span>
                                </div>
                                <div class="p-6 prose prose-sm max-w-none" v-html="datosVistaPrevia.html"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="lg:col-span-8 bg-white rounded-2xl border border-stone-200 shadow-xs p-12 text-center text-stone-400">
                    Selecciona una plantilla de la izquierda para comenzar a editarla.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    plantillas: Array,
    sucursales: Array,
    sucursal_id: Number,
});

const sucursalSeleccionada = ref(props.sucursal_id);
const plantillaActiva = ref(props.plantillas?.[0] || null);
const pestanaActiva = ref('editor');
const mensajeCopiado = ref('');
const cargandoPrueba = ref(false);
const cargandoVistaPrevia = ref(false);

const datosVistaPrevia = ref({
    asunto: '',
    html: '',
});

const formPlantilla = useForm({
    asunto: plantillaActiva.value?.asunto || '',
    cuerpo_html: plantillaActiva.value?.cuerpo_html || '',
});

function seleccionarPlantilla(p) {
    plantillaActiva.value = p;
    formPlantilla.asunto = p.asunto;
    formPlantilla.cuerpo_html = p.cuerpo_html;
    if (pestanaActiva.value === 'previa') {
        cargarVistaPrevia();
    }
}

function cambiarSucursal() {
    router.get(route('admin.plantillas-correo.index'), {
        sucursal_id: sucursalSeleccionada.value,
    });
}

function copiarVariable(varName) {
    const token = '{{' + varName + '}}';
    navigator.clipboard.writeText(token);
    mensajeCopiado.value = token;
    setTimeout(() => {
        mensajeCopiado.value = '';
    }, 2500);
}

function guardarPlantilla() {
    formPlantilla.put(route('admin.plantillas-correo.update', plantillaActiva.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            plantillaActiva.value.asunto = formPlantilla.asunto;
            plantillaActiva.value.cuerpo_html = formPlantilla.cuerpo_html;
        },
    });
}

async function cargarVistaPrevia() {
    pestanaActiva.value = 'previa';
    cargandoVistaPrevia.value = true;
    try {
        const response = await fetch(route('admin.plantillas-correo.vista-previa', plantillaActiva.value.id));
        const data = await response.json();
        datosVistaPrevia.value = data;
    } catch (e) {
        datosVistaPrevia.value = {
            asunto: formPlantilla.asunto,
            html: '<p class="text-rose-600">Error al cargar la vista previa.</p>',
        };
    } finally {
        cargandoVistaPrevia.value = false;
    }
}

function enviarCorreoPrueba() {
    cargandoPrueba.value = true;
    router.post(route('admin.plantillas-correo.enviar-prueba', plantillaActiva.value.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            cargandoPrueba.value = false;
        },
    });
}
</script>
