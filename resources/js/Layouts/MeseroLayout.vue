<template>
    <div class="min-h-screen bg-stone-100 flex flex-col font-sans text-stone-800 pb-16 md:pb-0">
        <!-- Barra superior del mesero -->
        <header class="bg-stone-900 text-white sticky top-0 z-30 shadow-md">
            <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-lg">🧑‍🍳</span>
                    <div>
                        <span class="font-bold text-sm tracking-tight text-white">Panel Mesero</span>
                        <span class="ml-2 text-xs text-orange-400 font-mono">{{ sucursalNombre }}</span>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <a
                        href="/mesero/pedidos/crear"
                        class="bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs px-3 py-1.5 rounded-lg flex items-center space-x-1 shadow-sm active:scale-95 transition"
                    >
                        <span>➕</span>
                        <span>Nueva Comanda</span>
                    </a>
                    <form method="POST" action="/logout">
                        <button type="submit" class="text-xs text-stone-400 hover:text-white transition">
                            Salir
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Navegación inferior fija en móviles/tablets -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-stone-200 z-40 flex justify-around py-2 shadow-lg">
            <a href="/mesero" class="flex flex-col items-center text-xs text-orange-600 font-semibold">
                <span class="text-base">📋</span>
                <span>Mis Pedidos</span>
            </a>
            <a href="/mesero/mesas" class="flex flex-col items-center text-xs text-stone-600">
                <span class="text-base">🪑</span>
                <span>Mesas</span>
            </a>
            <a href="/mesero/domicilios" class="flex flex-col items-center text-xs text-stone-600">
                <span class="text-base">🛵</span>
                <span>Domicilios</span>
            </a>
            <a href="/mesero/perfil" class="flex flex-col items-center text-xs text-stone-600">
                <span class="text-base">👤</span>
                <span>Perfil</span>
            </a>
        </nav>

        <main class="max-w-7xl mx-auto px-4 py-6 flex-1 w-full">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const sucursalNombre = ref('Sede Centro');
</script>

