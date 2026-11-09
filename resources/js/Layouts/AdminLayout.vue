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

            <!-- Selector de Sucursal Activa Persistente -->
            <div v-if="$page.props.sucursales_usuario && $page.props.sucursales_usuario.length > 1" class="p-3 border-b border-stone-800">
                <label class="block text-[11px] font-semibold text-stone-400 mb-1">Sucursal Activa</label>
                <select
                    :value="$page.props.sucursal_activa_id"
                    @change="cambiarSucursal($event.target.value)"
                    class="w-full bg-stone-800 border border-stone-700 text-white text-xs rounded-lg px-2.5 py-1.5 focus:ring-orange-500 focus:border-orange-500 cursor-pointer"
                >
                    <option
                        v-for="s in $page.props.sucursales_usuario"
                        :key="s.id"
                        :value="s.id"
                    >
                        🏢 {{ s.nombre }}
                    </option>
                </select>
            </div>
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
            <!-- Barra superior móvil con menú desplegable completo -->
            <header class="bg-stone-900 text-white border-b border-stone-800 lg:hidden sticky top-0 z-40">
                <div class="h-16 flex items-center justify-between px-4 sm:px-6">
                    <Link href="/admin" class="flex items-center space-x-2">
                        <span class="text-xl">👨‍💼</span>
                        <div>
                            <span class="font-bold text-white block text-sm leading-tight">SIGR Admin</span>
                            <span class="text-[10px] text-orange-400 font-mono block">{{ sucursalNombre }}</span>
                        </div>
                    </Link>
                    <div class="flex items-center space-x-2">
                        <Link href="/" class="px-2.5 py-1.5 rounded-lg bg-stone-800 text-xs font-semibold text-stone-200 hover:text-white">
                            🏠 Menú
                        </Link>
                        <button
                            type="button"
                            @click="menuMovilAbierto = !menuMovilAbierto"
                            class="px-3 py-1.5 rounded-lg bg-orange-600 text-white text-xs font-bold flex items-center gap-1.5 cursor-pointer"
                            aria-label="Abrir menú de módulos"
                        >
                            <span>{{ menuMovilAbierto ? '✕' : '☰' }}</span>
                            <span>Módulos</span>
                        </button>
                    </div>
                </div>

                <!-- Menú Desplegable Móvil con todas las opciones del Admin -->
                <div v-if="menuMovilAbierto" class="border-t border-stone-800 bg-stone-900 px-4 py-4 space-y-3 max-h-[80vh] overflow-y-auto shadow-2xl">
                    <div v-if="$page.props.sucursales_usuario && $page.props.sucursales_usuario.length > 1">
                        <label class="block text-[11px] font-semibold text-stone-400 mb-1">Sucursal Activa</label>
                        <select
                            :value="$page.props.sucursal_activa_id"
                            @change="cambiarSucursal($event.target.value)"
                            class="w-full bg-stone-800 border border-stone-700 text-white text-xs rounded-lg px-2.5 py-2"
                        >
                            <option v-for="s in $page.props.sucursales_usuario" :key="s.id" :value="s.id">
                                🏢 {{ s.nombre }}
                            </option>
                        </select>
                    </div>

                    <div v-if="esSuperAdmin">
                        <Link
                            href="/superadmin"
                            @click="menuMovilAbierto = false"
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-amber-500/20 text-amber-300 text-xs font-bold border border-amber-500/30"
                        >
                            <span>👑</span>
                            <span>Ir al Panel SuperAdmin</span>
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 gap-1.5 text-xs">
                        <Link href="/admin" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">📊 Tablero General</Link>
                        <Link href="/admin/pedidos" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">🛎️ Monitor Pedidos</Link>
                        <Link href="/admin/menu" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">🍔 Menú y Precios</Link>
                        <Link href="/admin/promociones" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">🏷️ Promociones</Link>
                        <Link href="/admin/reservas" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">📅 Reservas</Link>
                        <Link href="/admin/espacios" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">🪑 Mapa de Mesas</Link>
                        <Link href="/admin/caja" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">💵 Caja y Turnos</Link>
                        <Link href="/admin/comprobantes" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">🧾 Comprobantes</Link>
                        <Link href="/admin/compras" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">🛒 Compras</Link>
                        <Link href="/admin/inventario" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">📦 Inventario</Link>
                        <Link href="/admin/meseros" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">🧑‍🍳 Meseros</Link>
                        <Link href="/admin/informes" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">📈 Informes</Link>
                        <Link href="/admin/bitacora" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">🛡️ Bitácora</Link>
                        <Link href="/admin/papelera" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-800/80 hover:bg-stone-800 text-stone-200 font-medium">🗑️ Papelera</Link>
                    </div>

                    <div class="pt-2 border-t border-stone-800 flex items-center justify-between text-xs">
                        <span class="text-stone-400">{{ $page.props.auth?.user?.name }}</span>
                        <button type="button" @click="logout" class="px-3 py-1.5 rounded-lg bg-rose-600 text-white font-bold">
                            Salir 🚪
                        </button>
                    </div>
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
const menuMovilAbierto = ref(false);
const sucursalNombre = computed(() => {
    const actId = page.props.sucursal_activa_id;
    const found = page.props.sucursales_usuario?.find(s => s.id == actId);
    return found ? found.nombre : 'Sede Operativa';
});

function cambiarSucursal(id) {
    router.post('/admin/seleccionar-sucursal', { sucursal_id: id }, {
        preserveScroll: true,
    });
}

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
