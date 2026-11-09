<template>
    <div class="min-h-screen bg-stone-100 flex font-sans text-stone-800">
        <!-- Sidebar SuperAdmin -->
        <aside class="w-64 bg-stone-950 text-stone-300 flex-shrink-0 hidden lg:flex flex-col border-r border-stone-800">
            <div class="p-5 border-b border-stone-800 flex items-center space-x-3">
                <span class="text-2xl">👑</span>
                <div>
                    <h1 class="font-bold text-white tracking-tight">SIGR Maestro</h1>
                    <span class="text-xs text-amber-400 font-semibold uppercase tracking-wider">Super Administrador</span>
                </div>
            </div>

            <!-- Selector Global de Sucursales -->
            <div class="p-3 border-b border-stone-800">
                <label class="block text-xs font-medium text-stone-400 mb-1">Sucursal Activa</label>
                <select
                    v-model="sucursalSeleccionada"
                    class="w-full bg-stone-900 border border-stone-700 text-white text-xs rounded-lg px-2.5 py-1.5 focus:ring-amber-500 focus:border-amber-500"
                >
                    <option value="todas">-- Todas las sedes (Global) --</option>
                    <option value="1">Sede Centro</option>
                    <option value="2">Sede Norte</option>
                </select>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
                <a href="/superadmin" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-amber-500/20 text-amber-400 font-semibold">
                    <span>🏢</span>
                    <span>Sucursales</span>
                </a>
                <a href="/superadmin/administradores" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>👥</span>
                    <span>Administradores</span>
                </a>
                <a href="/superadmin/impuestos" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>⚖️</span>
                    <span>Impuestos (IVA / INC)</span>
                </a>
                <a href="/superadmin/configuracion" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>⚙️</span>
                    <span>Parámetros Globales</span>
                </a>
                <a href="/superadmin/plantillas" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>✉️</span>
                    <span>Plantillas de Correo</span>
                </a>
                <a href="/superadmin/informes" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>📊</span>
                    <span>Informes Consolidados</span>
                </a>
            </nav>

            <div class="p-4 border-t border-stone-800 flex items-center justify-between text-xs">
                <span class="text-stone-400 truncate">{{ $page.props.auth?.user?.name || 'Super Admin' }}</span>
                <button type="button" @click="logout" class="text-amber-400 hover:text-amber-300 font-semibold transition cursor-pointer">
                    Salir 🚪
                </button>
            </div>
        </aside>

        <!-- Contenido principal -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <header class="bg-white border-b border-stone-200 h-16 flex items-center justify-between px-6 lg:hidden">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">👑</span>
                    <span class="font-bold text-stone-900">SIGR Super Admin</span>
                </div>
                <button type="button" @click="logout" class="text-xs font-semibold text-stone-600 hover:text-stone-900 cursor-pointer">
                    Cerrar sesión
                </button>
            </header>

            <main class="p-6 max-w-7xl w-full mx-auto flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const sucursalSeleccionada = ref('todas');

function logout() {
    router.post('/logout');
}
</script>

