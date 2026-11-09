<template>
    <div class="min-h-screen bg-stone-100 flex font-sans text-stone-800">
        <!-- Sidebar SuperAdmin -->
        <aside class="w-64 bg-stone-950 text-stone-300 flex-shrink-0 hidden lg:flex flex-col border-r border-stone-800">
            <!-- Cabecera de Marca -->
            <div class="p-5 border-b border-stone-800 flex items-center justify-between">
                <Link href="/superadmin" class="flex items-center space-x-3 hover:opacity-90 transition">
                    <span class="text-2xl">👑</span>
                    <div>
                        <h1 class="font-bold text-white tracking-tight">SIGR Maestro</h1>
                        <span class="text-xs text-amber-400 font-semibold uppercase tracking-wider">Super Administrador</span>
                    </div>
                </Link>
            </div>

            <!-- Accesos Rápidos de Navegación Externa -->
            <div class="p-3 border-b border-stone-800 flex items-center gap-2">
                <Link
                    href="/"
                    class="w-full flex items-center justify-center space-x-2 px-3 py-1.5 rounded-lg bg-stone-900 hover:bg-stone-800 text-stone-300 hover:text-white text-xs font-semibold border border-stone-700/60 transition"
                    title="Ir a la carta y vista pública del restaurante"
                >
                    <span>🏠</span>
                    <span>Ver Menú Público</span>
                </Link>
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
                <div class="px-3 pb-1 text-[11px] font-bold text-stone-500 uppercase tracking-wider">
                    Administración Maestra
                </div>
                <Link href="/superadmin" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>🏢</span>
                    <span>Sucursales</span>
                </Link>
                <Link href="/superadmin/administradores" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>👥</span>
                    <span>Administradores</span>
                </Link>
                <Link href="/superadmin/impuestos" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>⚖️</span>
                    <span>Impuestos (IVA / INC)</span>
                </Link>
                <Link href="/superadmin/configuracion" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>⚙️</span>
                    <span>Parámetros Globales</span>
                </Link>
                <Link href="/superadmin/plantillas" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>✉️</span>
                    <span>Plantillas de Correo</span>
                </Link>
                <Link href="/superadmin/informes" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>📊</span>
                    <span>Informes Consolidados</span>
                </Link>

                <!-- Desplegable / Acordeón: Módulos de Operación de Sucursal (Admin) -->
                <div class="pt-3">
                    <button
                        type="button"
                        @click="seccionAdminAbierta = !seccionAdminAbierta"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-bold uppercase tracking-wider text-amber-400/90 bg-stone-900 hover:bg-stone-850 hover:text-amber-300 transition cursor-pointer border border-stone-800"
                    >
                        <span class="flex items-center space-x-2">
                            <span>📂</span>
                            <span>Módulos de Operación</span>
                        </span>
                        <span class="text-xs transition-transform duration-200" :class="{ 'rotate-180': seccionAdminAbierta }">
                            ▼
                        </span>
                    </button>

                    <div v-show="seccionAdminAbierta" class="mt-1 pl-2 space-y-0.5 border-l-2 border-stone-800 ml-3">
                        <Link href="/admin" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>📊</span>
                            <span>Tablero General</span>
                        </Link>
                        <Link href="/admin/pedidos" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>🛎️</span>
                            <span>Monitor Pedidos</span>
                        </Link>
                        <Link href="/admin/menu" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>🍔</span>
                            <span>Menú y Combos</span>
                        </Link>
                        <Link href="/admin/promociones" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>🏷️</span>
                            <span>Promociones</span>
                        </Link>
                        <Link href="/admin/reservas" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>📅</span>
                            <span>Reservas</span>
                        </Link>
                        <Link href="/admin/espacios" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>🪑</span>
                            <span>Mapa de Mesas</span>
                        </Link>
                        <Link href="/admin/caja" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>💵</span>
                            <span>Caja y Turnos</span>
                        </Link>
                        <Link href="/admin/comprobantes" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>🧾</span>
                            <span>Comprobantes (FAC)</span>
                        </Link>
                        <Link href="/admin/compras" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>🛒</span>
                            <span>Compras</span>
                        </Link>
                        <Link href="/admin/inventario" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>📦</span>
                            <span>Inventario y Kárdex</span>
                        </Link>
                        <Link href="/admin/meseros" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>🧑‍🍳</span>
                            <span>Meseros</span>
                        </Link>
                        <Link href="/admin/bitacora" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>🛡️</span>
                            <span>Bitácora Auditoría</span>
                        </Link>
                        <Link href="/admin/papelera" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs hover:bg-stone-900 text-stone-400 hover:text-white transition">
                            <span>🗑️</span>
                            <span>Papelera</span>
                        </Link>
                    </div>
                </div>
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
            <!-- Cabecera móvil -->
            <header class="bg-white border-b border-stone-200 h-16 flex items-center justify-between px-6 lg:hidden">
                <Link href="/superadmin" class="flex items-center space-x-2">
                    <span class="text-xl">👑</span>
                    <span class="font-bold text-stone-900">SIGR Super Admin</span>
                </Link>
                <div class="flex items-center space-x-3">
                    <Link href="/" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                        🏠 Menú
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
import { ref, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const sucursalSeleccionada = ref('todas');
const seccionAdminAbierta = ref(false);

onMounted(() => {
    if (typeof window !== 'undefined' && page.props.auth?.user) {
        sessionStorage.setItem('sigr_auth_active', '1');
        sessionStorage.setItem('sigr_dashboard', page.props.auth.user.dashboard_url || '/superadmin');
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
