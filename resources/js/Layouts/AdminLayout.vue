<template>
    <div class="min-h-screen bg-stone-100 flex font-sans text-stone-800">
        <!-- Sidebar Administrador -->
        <aside class="w-64 bg-stone-900 text-stone-300 flex-shrink-0 hidden lg:flex flex-col border-r border-stone-800">
            <div class="p-5 border-b border-stone-800 flex items-center space-x-3">
                <span class="text-2xl">👨‍💼</span>
                <div>
                    <h1 class="font-bold text-white tracking-tight">SIGR Admin</h1>
                    <span class="text-xs text-orange-400 font-mono">{{ sucursalNombre }}</span>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
                <a href="/admin" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-orange-600/20 text-orange-400 font-semibold">
                    <span>📊</span>
                    <span>Tablero y Pedidos</span>
                </a>
                <a href="/admin/menu" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🍔</span>
                    <span>Menú y Precios</span>
                </a>
                <a href="/admin/promociones" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🏷️</span>
                    <span>Promociones</span>
                </a>
                <a href="/admin/reservas" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>📅</span>
                    <span>Reservas y Mesas</span>
                </a>
                <a href="/admin/caja" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>💵</span>
                    <span>Caja y Turnos</span>
                </a>
                <a href="/admin/comprobantes" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🧾</span>
                    <span>Comprobantes (FAC)</span>
                </a>
                <a href="/admin/compras" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🛒</span>
                    <span>Compras</span>
                </a>
                <a href="/admin/inventario" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>📦</span>
                    <span>Inventario y Kárdex</span>
                </a>
                <a href="/admin/meseros" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🧑‍🍳</span>
                    <span>Meseros</span>
                </a>
                <a href="/admin/informes" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>📈</span>
                    <span>Informes</span>
                </a>
                <a href="/admin/bitacora" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🛡️</span>
                    <span>Bitácora Auditoría</span>
                </a>
                <a href="/admin/papelera" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🗑️</span>
                    <span>Papelera</span>
                </a>
            </nav>

            <div class="p-4 border-t border-stone-800 flex items-center justify-between text-xs">
                <span class="text-stone-400 truncate">{{ $page.props.auth?.user?.name }}</span>
                <form method="POST" action="/logout">
                    <button type="submit" class="text-orange-400 hover:text-orange-300 font-semibold transition">
                        Salir
                    </button>
                </form>
            </div>
        </aside>

        <!-- Contenido principal -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Barra superior móvil -->
            <header class="bg-white border-b border-stone-200 h-16 flex items-center justify-between px-6 lg:hidden">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">👨‍💼</span>
                    <span class="font-bold text-stone-900">SIGR Admin</span>
                </div>
                <form method="POST" action="/logout">
                    <button type="submit" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                        Cerrar sesión
                    </button>
                </form>
            </header>

            <main class="p-6 max-w-7xl w-full mx-auto flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const sucursalNombre = ref('Sede Centro');
</script>

