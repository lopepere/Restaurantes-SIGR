<template>
    <div class="min-h-screen bg-stone-100 flex flex-col font-sans text-stone-800">
        <!-- Barra de navegación del cliente -->
        <header class="bg-white border-b border-stone-200 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <Link href="/" class="text-xl font-bold tracking-tight text-orange-600 flex items-center space-x-2">
                        <span>🍽️</span>
                        <span>SIGR</span>
                    </Link>
                    <nav class="hidden md:flex space-x-4 text-sm font-medium">
                        <Link href="/cliente" class="text-stone-900 font-semibold hover:text-orange-600 transition">Inicio</Link>
                        <Link href="/cliente/pedidos" class="text-stone-600 hover:text-orange-600 transition">Mis Pedidos</Link>
                        <Link href="/cliente/comprobantes" class="text-stone-600 hover:text-orange-600 transition">Mis Comprobantes</Link>
                        <Link href="/cliente/reservas" class="text-stone-600 hover:text-orange-600 transition">Mis Reservas</Link>
                        <Link href="/cliente/perfil" class="text-stone-600 hover:text-orange-600 transition">Perfil</Link>
                    </nav>
                </div>

                <!-- Avatar y Salir -->
                <div class="flex items-center space-x-4">
                    <Link href="/" class="text-xs font-semibold text-stone-600 hover:text-orange-600 flex items-center space-x-1">
                        <span>🏠</span>
                        <span class="hidden sm:inline">Ver Menú</span>
                    </Link>

                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-full bg-orange-100 border border-orange-300 flex items-center justify-center text-orange-700 font-semibold text-xs">
                            {{ inicialesUsuario }}
                        </div>
                        <span class="text-xs font-medium text-stone-700 hidden sm:inline">{{ $page.props.auth?.user?.name }}</span>
                    </div>

                    <button
                        type="button"
                        @click="logout"
                        class="text-xs font-semibold text-stone-500 hover:text-rose-600 px-2 py-1 rounded-md transition cursor-pointer"
                    >
                        Cerrar sesión 🚪
                    </button>
                </div>
            </div>
        </header>

        <!-- Mensajes Flash -->
        <div v-if="$page.props.flash?.success" class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm p-3 rounded-lg">
                {{ $page.props.flash.success }}
            </div>
        </div>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';

const page = usePage();

onMounted(() => {
    if (typeof window !== 'undefined' && page.props.auth?.user) {
        sessionStorage.setItem('sigr_auth_active', '1');
        sessionStorage.setItem('sigr_dashboard', page.props.auth.user.dashboard_url || '/cliente');
    }
});

function logout() {
    if (typeof window !== 'undefined') {
        sessionStorage.removeItem('sigr_auth_active');
        sessionStorage.removeItem('sigr_dashboard');
    }
    router.post('/logout');
}

const inicialesUsuario = computed(() => {
    const nombre = page.props.auth?.user?.name || 'Cliente';
    return nombre.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
});
</script>
