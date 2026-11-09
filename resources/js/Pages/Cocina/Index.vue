<template>
    <div class="min-h-screen bg-stone-950 text-stone-100 flex flex-col font-sans">
        <!-- Barra Superior de Cocina / KDS -->
        <header class="bg-stone-900 border-b border-stone-800 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-lg">
            <div class="flex items-center space-x-3">
                <span class="text-3xl animate-pulse">👨‍🍳</span>
                <div>
                    <h1 class="text-lg font-black tracking-tight text-white flex items-center gap-2">
                        <span>Pantalla de Cocina (KDS)</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            {{ sucursal?.nombre }}
                        </span>
                    </h1>
                    <p class="text-xs text-stone-400">
                        Cocinero de turno: <strong class="text-white">{{ $page.props.auth?.user?.name }}</strong> &bull;
                        <span class="text-emerald-400">● En vivo (Reverb WebSocket)</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 text-xs">
                <span class="px-3 py-1.5 rounded-xl bg-stone-800 text-stone-300 font-mono font-bold border border-stone-700">
                    {{ pedidos.length }} en preparación
                </span>

                <Link
                    v-if="esAdmin"
                    href="/admin/pedidos"
                    class="px-3 py-1.5 rounded-xl bg-stone-800 hover:bg-stone-700 text-stone-300 font-bold border border-stone-700 transition"
                >
                    Monitor Admin
                </Link>

                <button
                    type="button"
                    @click="logout"
                    class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold transition cursor-pointer"
                >
                    Salir 🚪
                </button>
            </div>
        </header>

        <!-- Tablero de Comandas en Vivo -->
        <main class="flex-1 p-4 sm:p-6 overflow-y-auto">
            <div v-if="pedidos.length === 0" class="h-full flex flex-col items-center justify-center py-24 text-center text-stone-500">
                <span class="text-6xl block mb-3">🍳</span>
                <h2 class="text-xl font-black text-stone-300">¡Cocina al día!</h2>
                <p class="text-sm mt-1 max-w-sm">No hay comandas pendientes por preparar. Las nuevas órdenes aparecerán aquí en tiempo real.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="pedido in pedidos"
                    :key="pedido.id"
                    :class="[
                        'rounded-3xl border flex flex-col justify-between overflow-hidden shadow-xl transition-all duration-200',
                        pedido.minutos_transcurridos >= 25
                            ? 'bg-rose-950/40 border-rose-600 ring-2 ring-rose-500/50'
                            : (pedido.minutos_transcurridos >= 15
                                ? 'bg-amber-950/40 border-amber-500 ring-1 ring-amber-500/40'
                                : 'bg-stone-900 border-stone-800')
                    ]"
                >
                    <!-- Cabecera del Ticket -->
                    <div class="p-4 border-b border-stone-800/80 bg-stone-900/60 flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-black text-lg text-white">#{{ pedido.numero }}</span>
                                <span v-if="pedido.mesa" class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-500 text-stone-950">
                                    📍 {{ pedido.mesa }}
                                </span>
                                <span v-else class="px-2 py-0.5 rounded-full text-xs font-bold bg-stone-800 text-stone-300">
                                    {{ pedido.tipo }}
                                </span>
                            </div>
                            <span class="text-[11px] text-stone-400 mt-0.5 block">
                                Mesero: {{ pedido.mesero }} &bull; Hora: {{ pedido.hora }}
                            </span>
                        </div>

                        <!-- Tiempo transcurrido -->
                        <div
                            :class="[
                                'px-2.5 py-1 rounded-xl text-xs font-mono font-black flex items-center gap-1',
                                pedido.minutos_transcurridos >= 25
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (pedido.minutos_transcurridos >= 15
                                        ? 'bg-amber-500 text-stone-950'
                                        : 'bg-stone-800 text-stone-300')
                            ]"
                        >
                            <span>⏱️</span>
                            <span>{{ pedido.minutos_transcurridos }}m</span>
                        </div>
                    </div>

                    <!-- Lista de Platos y Preparaciones -->
                    <div class="p-4 space-y-3 flex-1 overflow-y-auto max-h-72">
                        <div
                            v-for="it in pedido.items"
                            :key="it.id"
                            class="p-2.5 rounded-2xl bg-stone-950/60 border border-stone-800/60 space-y-1"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-bold text-sm text-white">
                                    {{ it.nombre }}
                                </span>
                                <span class="font-mono font-black text-sm text-amber-400 px-2 py-0.5 bg-amber-500/10 rounded-lg">
                                    x{{ it.cantidad }}
                                </span>
                            </div>

                            <!-- Desglose de componentes si es un combo -->
                            <div v-if="it.combo_desglose && it.combo_desglose.length > 0" class="pl-2 pt-1 space-y-0.5 border-l-2 border-stone-700">
                                <div v-for="cComp in it.combo_desglose" :key="cComp.nombre" class="text-[11px] text-stone-300 flex items-center gap-1.5">
                                    <span class="text-amber-400 font-bold">&bull;</span>
                                    <span>{{ cComp.nombre }} ({{ cComp.cantidad }}x)</span>
                                </div>
                            </div>

                            <!-- Notas de preparación (sin cebolla, etc.) -->
                            <div v-if="it.notas" class="text-xs text-rose-300 font-semibold bg-rose-950/40 px-2 py-1 rounded-lg border border-rose-900/40">
                                ⚠️ {{ it.notas }}
                            </div>
                        </div>

                        <!-- Nota general del pedido -->
                        <div v-if="pedido.notas" class="text-xs text-amber-300 italic bg-amber-950/30 p-2 rounded-xl border border-amber-900/40">
                            💬 Nota comanda: {{ pedido.notas }}
                        </div>
                    </div>

                    <!-- Botón de Estado Operativo de Cocina -->
                    <div class="p-4 border-t border-stone-800 bg-stone-900/60">
                        <button
                            v-if="pedido.estado === 'recibido'"
                            type="button"
                            @click="cambiarEstado(pedido, 'en_preparacion')"
                            class="w-full py-3 bg-amber-600 hover:bg-amber-500 text-stone-950 font-black text-xs rounded-2xl shadow-lg transition active:scale-95 flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>🔥</span>
                            <span>Iniciar Preparación</span>
                        </button>

                        <button
                            v-else-if="pedido.estado === 'en_preparacion'"
                            type="button"
                            @click="cambiarEstado(pedido, 'listo')"
                            class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-2xl shadow-lg transition active:scale-95 flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>✅</span>
                            <span>Marcar como Listo</span>
                        </button>

                        <div
                            v-else
                            class="w-full py-2.5 text-center bg-stone-800 text-emerald-400 font-black text-xs rounded-2xl border border-emerald-500/30"
                        >
                            Listo para Despacho 🍽️
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const esAdmin = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    return roles.includes('admin') || roles.includes('super_admin');
});

const props = defineProps({
    sucursal: Object,
    pedidos: Array,
});

function cambiarEstado(pedido, nuevoEstado) {
    router.post(`/cocina/pedidos/${pedido.id}/estado`, {
        estado: nuevoEstado,
    }, {
        preserveScroll: true,
    });
}

function logout() {
    router.post('/logout');
}

// Timer para refrescar minutos transcurridos cada 30 segundos
let timerId = null;
onMounted(() => {
    timerId = setInterval(() => {
        router.reload({ only: ['pedidos'] });
    }, 30000);

    // Escuchar canal Reverb en tiempo real si Echo está disponible
    if (typeof window !== 'undefined' && window.Echo && props.sucursal?.id) {
        window.Echo.channel(`pedidos.${props.sucursal.id}`)
            .listen('.pedido.creado', () => router.reload({ only: ['pedidos'] }))
            .listen('.pedido.actualizado', () => router.reload({ only: ['pedidos'] }));
    }
});

onUnmounted(() => {
    if (timerId) clearInterval(timerId);
    if (typeof window !== 'undefined' && window.Echo && props.sucursal?.id) {
        window.Echo.leaveChannel(`pedidos.${props.sucursal.id}`);
    }
});
</script>

