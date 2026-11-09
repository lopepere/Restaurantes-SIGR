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

            <!-- Selector Global de Sucursales (Persistente) -->
            <div class="p-3 border-b border-stone-800">
                <label class="block text-xs font-semibold text-stone-400 mb-1">Sucursal Activa (Persistente)</label>
                <select
                    :value="$page.props.sucursal_activa_id || 'todas'"
                    @change="cambiarSucursal($event.target.value)"
                    class="w-full bg-stone-900 border border-stone-700 text-white text-xs rounded-lg px-2.5 py-1.5 focus:ring-amber-500 focus:border-amber-500 cursor-pointer"
                >
                    <option value="todas">-- Todas las sedes (Global) --</option>
                    <option
                        v-for="s in $page.props.sucursales_usuario"
                        :key="s.id"
                        :value="s.id"
                    >
                        🏢 {{ s.nombre }}
                    </option>
                </select>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
                <div class="px-3 pb-1 text-[11px] font-bold text-stone-500 uppercase tracking-wider">
                    Administración Maestra
                </div>
                <Link href="/superadmin/sucursales" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-stone-900 text-stone-300 hover:text-white transition">
                    <span>🏢</span>
                    <span>Sucursales, Cajas y Cocina</span>
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
            <!-- Cabecera móvil con menú desplegable completo -->
            <header class="bg-stone-950 text-white border-b border-stone-800 lg:hidden sticky top-0 z-40">
                <div class="h-16 flex items-center justify-between px-4 sm:px-6">
                    <Link href="/superadmin" class="flex items-center space-x-2">
                        <span class="text-xl">👑</span>
                        <span class="font-bold text-white text-sm">SIGR Super Admin</span>
                    </Link>
                    <div class="flex items-center space-x-2">
                        <Link href="/" class="px-2.5 py-1.5 rounded-lg bg-stone-800 text-xs font-semibold text-stone-200">
                            🏠 Menú
                        </Link>
                        <button
                            type="button"
                            @click="menuMovilAbierto = !menuMovilAbierto"
                            class="px-3 py-1.5 rounded-lg bg-amber-600 text-white text-xs font-bold flex items-center gap-1.5 cursor-pointer"
                            aria-label="Abrir menú SuperAdmin"
                        >
                            <span>{{ menuMovilAbierto ? '✕' : '☰' }}</span>
                            <span>Menú</span>
                        </button>
                    </div>
                </div>

                <!-- Menú Desplegable Móvil SuperAdmin -->
                <div v-if="menuMovilAbierto" class="border-t border-stone-800 bg-stone-950 px-4 py-4 space-y-3 max-h-[82vh] overflow-y-auto shadow-2xl">
                    <div>
                        <label class="block text-[11px] font-semibold text-stone-400 mb-1">Sucursal Activa (Persistente)</label>
                        <select
                            :value="$page.props.sucursal_activa_id || 'todas'"
                            @change="cambiarSucursal($event.target.value)"
                            class="w-full bg-stone-900 border border-stone-700 text-white text-xs rounded-lg px-2.5 py-2"
                        >
                            <option value="todas">-- Todas las sedes (Global) --</option>
                            <option v-for="s in $page.props.sucursales_usuario" :key="s.id" :value="s.id">
                                🏢 {{ s.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="text-[10px] font-bold uppercase tracking-wider text-amber-400">Administración Maestra</div>
                    <div class="grid grid-cols-2 gap-1.5 text-xs">
                        <Link href="/superadmin/sucursales" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900 text-stone-200 font-medium">🏢 Sucursales y Cajas</Link>
                        <Link href="/superadmin/administradores" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900 text-stone-200 font-medium">👥 Administradores</Link>
                        <Link href="/superadmin/impuestos" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900 text-stone-200 font-medium">⚖️ Impuestos</Link>
                        <Link href="/superadmin/configuracion" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900 text-stone-200 font-medium">⚙️ Parámetros</Link>
                        <Link href="/superadmin/plantillas" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900 text-stone-200 font-medium">✉️ Plantillas Correo</Link>
                        <Link href="/superadmin/informes" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900 text-stone-200 font-medium">📊 Informes</Link>
                    </div>

                    <div class="text-[10px] font-bold uppercase tracking-wider text-amber-400 pt-1">Módulos de Operación de Sede</div>
                    <div class="grid grid-cols-2 gap-1.5 text-xs">
                        <Link href="/admin" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">📊 Tablero General</Link>
                        <Link href="/admin/pedidos" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">🛎️ Monitor Pedidos</Link>
                        <Link href="/admin/menu" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">🍔 Menú y Combos</Link>
                        <Link href="/admin/promociones" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">🏷️ Promociones</Link>
                        <Link href="/admin/reservas" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">📅 Reservas</Link>
                        <Link href="/admin/espacios" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">🪑 Mapa de Mesas</Link>
                        <Link href="/admin/caja" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">💵 Caja y Turnos</Link>
                        <Link href="/admin/comprobantes" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">🧾 Comprobantes</Link>
                        <Link href="/admin/compras" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">🛒 Compras</Link>
                        <Link href="/admin/inventario" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">📦 Inventario</Link>
                        <Link href="/admin/meseros" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">🧑‍🍳 Meseros</Link>
                        <Link href="/admin/bitacora" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">🛡️ Bitácora</Link>
                        <Link href="/admin/papelera" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-stone-900/80 text-stone-300">🗑️ Papelera</Link>
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
import { ref, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const sucursalSeleccionada = ref('todas');
const seccionAdminAbierta = ref(false);
const menuMovilAbierto = ref(false);

onMounted(() => {
    if (typeof window !== 'undefined' && page.props.auth?.user) {
        sessionStorage.setItem('sigr_auth_active', '1');
        sessionStorage.setItem('sigr_dashboard', page.props.auth.user.dashboard_url || '/superadmin');
    }
});

function cambiarSucursal(id) {
    router.post('/admin/seleccionar-sucursal', { sucursal_id: id }, {
        preserveScroll: true,
    });
}

function logout() {
    if (typeof window !== 'undefined') {
        sessionStorage.removeItem('sigr_auth_active');
        sessionStorage.removeItem('sigr_dashboard');
    }
    router.post('/logout');
}
</script>
