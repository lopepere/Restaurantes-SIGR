<template>
    <div class="min-h-screen bg-stone-50 flex flex-col font-sans text-stone-800">
        <!-- Barra de navegación pública -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-stone-200 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Marca / Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-600 flex items-center justify-center text-white font-bold text-xl shadow-sm">
                        🍽️
                    </div>
                    <div>
                        <Link href="/" class="text-xl font-bold tracking-tight text-stone-900 hover:text-orange-600 transition">
                            SIGR
                        </Link>
                        <span class="hidden sm:inline-block ml-2 text-xs font-medium text-stone-500 bg-stone-100 px-2 py-0.5 rounded-full">
                            Restaurante
                        </span>
                    </div>
                </div>

                <!-- Selector de Sucursal y Enlaces Centrales -->
                <nav class="hidden md:flex items-center space-x-6 text-sm font-medium">
                    <Link href="/" class="text-stone-700 hover:text-orange-600 transition">Inicio</Link>
                    <a href="/#menu" class="text-stone-700 hover:text-orange-600 transition">Menú</a>
                    <a href="/#promociones" class="text-stone-700 hover:text-orange-600 transition">Promociones</a>
                    <Link href="/reservar" class="text-stone-700 hover:text-orange-600 transition">Reservar Mesa</Link>

                    <!-- Indicador de Sucursal activa -->
                    <div class="flex items-center space-x-1.5 bg-stone-100 px-3 py-1.5 rounded-lg border border-stone-200 text-stone-700 text-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-semibold">{{ sucursalNombre || 'Sede Centro' }}</span>
                    </div>
                </nav>

                <!-- Acciones de Usuario -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="dashboardUrl"
                            class="text-xs sm:text-sm font-semibold text-stone-700 hover:text-orange-600 px-3 py-1.5 rounded-lg border border-stone-200 bg-stone-50 hover:bg-white shadow-2xs transition"
                        >
                            Mi Panel ({{ $page.props.auth.user.name }})
                        </Link>
                        <button
                            type="button"
                            @click="cerrarSesion"
                            class="text-xs sm:text-sm font-medium text-stone-500 hover:text-rose-600 px-2.5 py-1.5 rounded-lg border border-transparent hover:border-rose-200 hover:bg-rose-50 transition flex items-center space-x-1 cursor-pointer"
                            title="Cerrar sesión actual"
                        >
                            <span>Salir</span>
                            <span class="text-xs">🚪</span>
                        </button>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="text-xs sm:text-sm font-medium text-stone-700 hover:text-stone-900 px-3 py-1.5 transition"
                        >
                            Ingresar
                        </Link>
                        <Link
                            href="/registro"
                            class="text-xs sm:text-sm font-semibold bg-orange-600 hover:bg-orange-700 text-white px-3.5 py-1.5 rounded-lg shadow-xs transition"
                        >
                            Registrarse
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Mensajes Flash -->
        <div v-if="$page.props.flash?.success" class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm p-3 rounded-lg flex items-center justify-between">
                <span>{{ $page.props.flash.success }}</span>
            </div>
        </div>
        <div v-if="$page.props.flash?.error" class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm p-3 rounded-lg flex items-center justify-between">
                <span>{{ $page.props.flash.error }}</span>
            </div>
        </div>

        <!-- Contenido principal con transición suave -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Botón flotante de pedido con icono de campanita de restaurante y efecto animado llamativo -->
        <div class="fixed bottom-6 right-6 z-50">
            <!-- Aura pulsante de luz cuando hay ítems elegidos -->
            <span
                v-if="itemsCount > 0"
                class="absolute -inset-2 rounded-full bg-orange-500/30 blur-md animate-pulse pointer-events-none"
            ></span>
            <span
                v-if="itemsCount > 0"
                class="absolute inset-0 rounded-full bg-amber-400 opacity-60 animate-ping pointer-events-none"
            ></span>

            <button
                type="button"
                @click="abrirElCarrito"
                :class="[
                    'relative p-4 rounded-full shadow-2xl transition-all duration-300 flex items-center justify-center cursor-pointer group active:scale-95 focus:outline-hidden',
                    itemsCount > 0
                        ? 'bg-gradient-to-tr from-orange-600 via-amber-500 to-orange-500 text-white ring-4 ring-amber-300 ring-offset-2 ring-offset-stone-50 animate-bounce'
                        : 'bg-orange-600 hover:bg-orange-700 text-white hover:shadow-orange-600/30'
                ]"
                title="Tu pedido (Campanita de servicio)"
                aria-label="Ver pedido en curso o carrito"
            >
                <RestaurantBellIcon class="w-7 h-7 text-white transform group-hover:-rotate-12 transition-transform duration-200" />
                
                <!-- Badge con conteo de items del pedido -->
                <span
                    v-if="itemsCount > 0"
                    class="absolute -top-1.5 -right-1.5 bg-amber-300 text-stone-950 font-black text-xs px-2.5 py-0.5 rounded-full ring-2 ring-white shadow-md animate-pulse"
                >
                    {{ itemsCount }}
                </span>
            </button>
        </div>

        <!-- Pie de página con aviso de tratamiento de datos personales (Ley 1581 de 2012) -->
        <footer class="bg-stone-900 text-stone-300 border-t border-stone-800 mt-16 text-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-white font-bold text-base mb-2">SIGR Restaurante</h3>
                        <p class="text-stone-400 text-xs leading-relaxed">
                            Plataforma integral de gestión culinaria y atención al cliente. Multi-sucursal, pedidos en tiempo real y reservas garantizadas.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-xs tracking-wider uppercase mb-2">Atención al cliente</h4>
                        <p class="text-stone-400 text-xs">Horario: Lunes a Domingo de 12:00 a 22:00</p>
                        <p class="text-stone-400 text-xs">Teléfono: +57 300 123 4567</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold text-xs tracking-wider uppercase mb-2">Protección de datos (Ley 1581)</h4>
                        <p class="text-stone-400 text-xs leading-relaxed">
                            En cumplimiento de la Ley Estatutaria 1581 de 2012, tus datos personales recolectados en pedidos y reservas son tratados con confidencialidad para fines exclusivos del servicio gastronómico.
                        </p>
                    </div>
                </div>
                <div class="border-t border-stone-800 mt-8 pt-6 text-center text-xs text-stone-500">
                    &copy; 2026 SIGR — Todos los derechos reservados.
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import RestaurantBellIcon from '@/Components/UI/RestaurantBellIcon.vue';

const props = defineProps({
    itemsCount: {
        type: Number,
        default: 0,
    },
    sucursalNombre: {
        type: String,
        default: 'Sede Centro',
    },
});

const emit = defineEmits(['toggle-carrito', 'abrir-carrito']);
const page = usePage();

const dashboardUrl = computed(() => {
    return page.props.auth?.user?.dashboard_url || '/cliente';
});

onMounted(() => {
    if (typeof window !== 'undefined' && page.props.auth?.user) {
        sessionStorage.setItem('sigr_auth_active', '1');
        sessionStorage.setItem('sigr_dashboard', dashboardUrl.value);
    }
});

function cerrarSesion() {
    if (typeof window !== 'undefined') {
        sessionStorage.removeItem('sigr_auth_active');
        sessionStorage.removeItem('sigr_dashboard');
    }
    router.post('/logout');
}

function abrirElCarrito() {
    emit('abrir-carrito');
    emit('toggle-carrito');
    window.dispatchEvent(new CustomEvent('abrir-carrito'));
    window.dispatchEvent(new CustomEvent('toggle-carrito'));
}
</script>
