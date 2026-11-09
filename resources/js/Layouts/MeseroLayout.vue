<template>
    <div class="min-h-screen bg-stone-100 flex flex-col font-sans text-stone-800 pb-16 md:pb-0">
        <!-- Barra superior del mesero -->
        <header class="bg-stone-900 text-white sticky top-0 z-30 shadow-md">
            <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
                <!-- Marca / Logo con retorno al panel principal -->
                <Link href="/mesero" class="flex items-center space-x-2 sm:space-x-3 hover:opacity-90 transition">
                    <span class="text-lg">🧑‍🍳</span>
                    <div>
                        <span class="font-bold text-sm tracking-tight text-white">Panel Mesero</span>
                        <span class="ml-2 text-xs text-orange-400 font-mono hidden sm:inline">{{ sucursalNombre }}</span>
                    </div>
                </Link>

                <div class="flex items-center space-x-2 sm:space-x-3">
                    <!-- Botón de Retorno al Menú / Home Público -->
                    <Link
                        href="/"
                        class="text-xs text-stone-300 hover:text-white flex items-center space-x-1 px-2.5 py-1.5 rounded-lg bg-stone-800 hover:bg-stone-750 border border-stone-700 transition"
                        title="Ver la carta pública del restaurante"
                    >
                        <span>🏠</span>
                        <span class="hidden sm:inline">Ver Menú</span>
                    </Link>

                    <!-- Retorno al Panel de Operaciones -->
                    <Link
                        href="/mesero"
                        class="text-xs text-stone-300 hover:text-white flex items-center space-x-1 px-2.5 py-1.5 rounded-lg bg-stone-800 hover:bg-stone-750 border border-stone-700 transition"
                        title="Ver listado de comandas activas"
                    >
                        <span>📋</span>
                        <span class="hidden sm:inline">Comandas</span>
                    </Link>

                    <Link
                        href="/mesero/pedidos/crear"
                        class="bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs px-3 py-1.5 rounded-lg flex items-center space-x-1 shadow-sm active:scale-95 transition"
                    >
                        <span>➕</span>
                        <span>Nueva Comanda</span>
                    </Link>

                    <button type="button" @click="logout" class="hidden sm:inline-block text-xs text-stone-400 hover:text-rose-400 transition cursor-pointer px-1 py-1">
                        Salir 🚪
                    </button>

                    <!-- Botón Menú Desplegable Móvil -->
                    <button
                        type="button"
                        @click="menuMovilAbierto = !menuMovilAbierto"
                        class="sm:hidden p-1.5 rounded-lg bg-stone-800 border border-stone-700 text-stone-200 text-xs font-bold"
                        aria-label="Abrir menú mesero"
                    >
                        {{ menuMovilAbierto ? '✕' : '☰' }}
                    </button>
                </div>
            </div>

            <!-- Menú Desplegable Móvil del Mesero -->
            <div v-if="menuMovilAbierto" class="sm:hidden border-t border-stone-800 bg-stone-900 px-4 py-3 space-y-2 text-xs">
                <Link href="/mesero" @click="menuMovilAbierto = false" class="block px-3 py-2 rounded-lg bg-stone-800 text-white font-semibold">📋 Panel de Operaciones</Link>
                <Link href="/mesero/pedidos" @click="menuMovilAbierto = false" class="block px-3 py-2 rounded-lg bg-stone-800 text-white font-semibold">🛎️ Comandas Activas</Link>
                <Link href="/mesero/pedidos/crear" @click="menuMovilAbierto = false" class="block px-3 py-2 rounded-lg bg-orange-600 text-white font-bold">➕ Registrar Nueva Comanda</Link>
                <Link href="/" @click="menuMovilAbierto = false" class="block px-3 py-2 rounded-lg bg-stone-800 text-stone-200 font-semibold">🏠 Ir al Home / Menú</Link>
                <button type="button" @click="logout" class="w-full text-left px-3 py-2 rounded-lg bg-rose-600 text-white font-bold">
                    🚪 Cerrar Sesión
                </button>
            </div>
        </header>

        <!-- Navegación inferior fija en móviles/tablets -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-stone-200 z-40 flex justify-around py-2 shadow-lg">
            <Link href="/mesero" class="flex flex-col items-center text-xs text-orange-600 font-semibold">
                <span class="text-base">📋</span>
                <span>Mis Pedidos</span>
            </Link>
            <Link href="/mesero/mesas" class="flex flex-col items-center text-xs text-stone-600">
                <span class="text-base">🪑</span>
                <span>Mesas</span>
            </Link>
            <Link href="/mesero/domicilios" class="flex flex-col items-center text-xs text-stone-600">
                <span class="text-base">🛵</span>
                <span>Domicilios</span>
            </Link>
            <Link href="/" class="flex flex-col items-center text-xs text-stone-600">
                <span class="text-base">🏠</span>
                <span>Menú</span>
            </Link>
        </nav>

        <main class="max-w-7xl mx-auto px-4 py-6 flex-1 w-full">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const sucursalNombre = ref('Sede Centro');
const menuMovilAbierto = ref(false);

onMounted(() => {
    if (typeof window !== 'undefined' && page.props.auth?.user) {
        sessionStorage.setItem('sigr_auth_active', '1');
        sessionStorage.setItem('sigr_dashboard', page.props.auth.user.dashboard_url || '/mesero');
    }
});

function logout() {
    if (typeof window !== 'undefined') {
        sessionStorage.removeItem('sigr_auth_active');
        sessionStorage.removeItem('sigr_dashboard');
    }
    router.post('/logout');
}
</script>
