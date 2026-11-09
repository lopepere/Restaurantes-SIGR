<template>
    <div class="min-h-screen bg-stone-100 flex font-sans text-stone-800">
        <!-- Sidebar Administrador -->
        <aside class="w-64 bg-stone-900 text-stone-300 flex-shrink-0 hidden lg:flex flex-col border-r border-stone-800">
            <!-- Encabezado de Marca -->
            <div class="p-5 border-b border-stone-800 flex items-center justify-between">
                <Link href="/admin" class="flex items-center space-x-3 hover:opacity-90 transition">
                    <span class="text-2xl">👨‍💼</span>
                    <div>
                        <h1 class="font-bold text-white tracking-tight">SIGR Admin</h1>
                        <span class="text-xs text-orange-400 font-mono">{{ sucursalNombre }}</span>
                    </div>
                </Link>
            </div>

            <!-- Accesos Rápidos de Navegación -->
            <div class="p-3 border-b border-stone-800 space-y-2">
                <Link
                    href="/"
                    class="w-full flex items-center justify-center space-x-2 px-3 py-1.5 rounded-lg bg-stone-800 hover:bg-stone-750 text-stone-300 hover:text-white text-xs font-semibold border border-stone-700 transition"
                    title="Ir a la carta y vista pública del restaurante"
                >
                    <span>🏠</span>
                    <span>Ver Menú Público</span>
                </Link>

                <!-- Si el usuario actual es SuperAdmin navegando la sucursal, darle acceso inmediato para regresar a su panel raíz -->
                <Link
                    v-if="esSuperAdmin"
                    href="/superadmin"
                    class="w-full flex items-center justify-center space-x-2 px-3 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 text-xs font-bold border border-amber-500/30 transition"
                    title="Regresar a la consola global de Super Administrador"
                >
                    <span>👑</span>
                    <span>Panel SuperAdmin</span>
                </Link>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
                <Link href="/admin" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>📊</span>
                    <span>Tablero General</span>
                </Link>
                <Link href="/admin/pedidos" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🛎️</span>
                    <span>Monitor de Pedidos</span>
                </Link>
                <Link href="/admin/menu" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🍔</span>
                    <span>Menú y Precios</span>
                </Link>
                <Link href="/admin/promociones" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🏷️</span>
                    <span>Promociones</span>
                </Link>
                <Link href="/admin/reservas" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>📅</span>
                    <span>Reservas</span>
                </Link>
                <Link href="/admin/espacios" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🪑</span>
                    <span>Mapa de Mesas</span>
                </Link>
                <Link href="/admin/caja" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>💵</span>
                    <span>Caja y Turnos</span>
                </Link>
                <Link href="/admin/comprobantes" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🧾</span>
                    <span>Comprobantes (FAC)</span>
                </Link>
                <Link href="/admin/compras" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🛒</span>
                    <span>Compras</span>
                </Link>
                <Link href="/admin/inventario" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>📦</span>
                    <span>Inventario y Kárdex</span>
                </Link>
                <Link href="/admin/meseros" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🧑‍🍳</span>
                    <span>Meseros</span>
                </Link>
                <Link href="/admin/informes" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>📈</span>
                    <span>Informes</span>
                </Link>
                <Link href="/admin/bitacora" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🛡️</span>
                    <span>Bitácora Auditoría</span>
                </Link>
                <Link href="/admin/papelera" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-800 text-stone-300 hover:text-white transition">
                    <span>🗑️</span>
                    <span>Papelera</span>
                </Link>
            </nav>

            <div class="p-4 border-t border-stone-800 flex items-center justify-between text-xs">
                <span class="text-stone-400 truncate">{{ $page.props.auth?.user?.name }}</span>
                <button type="button" @click="logout" class="text-orange-400 hover:text-orange-300 font-semibold transition cursor-pointer">
                    Salir 🚪
                </button>
            </div>
        </aside>

        <!-- Contenido principal -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Barra superior móvil -->
            <header class="bg-white border-b border-stone-200 h-16 flex items-center justify-between px-6 lg:hidden">
                <Link href="/admin" class="flex items-center space-x-2">
                    <span class="text-xl">👨‍💼</span>
                    <span class="font-bold text-stone-900">SIGR Admin</span>
                </Link>
                <div class="flex items-center space-x-3">
                    <Link href="/" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                        🏠 Menú
                    </Link>
                    <Link v-if="esSuperAdmin" href="/superadmin" class="text-xs font-semibold text-amber-600 hover:text-amber-800">
                        👑 SuperAdmin
                    </Link>
                    <button type="button" @click="logout" class="text-xs font-semibold text-rose-600 hover:text-rose-800 cursor-pointer">
                        Salir 🚪
                    </button>
                </div>
            </header>

            <main class="p-6 max-w-7xl w-full mx-auto flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const sucursalNombre = ref('Sede Centro');

const esSuperAdmin = computed(() => {
    return page.props.auth?.user?.roles?.includes('super_admin');
});

onMounted(() => {
    if (typeof window !== 'undefined' && page.props.auth?.user) {
        sessionStorage.setItem('sigr_auth_active', '1');
        sessionStorage.setItem('sigr_dashboard', page.props.auth.user.dashboard_url || '/admin');
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
