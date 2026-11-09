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
                        <a href="/" class="text-xl font-bold tracking-tight text-stone-900 hover:text-orange-600 transition">
                            SIGR
                        </a>
                        <span class="hidden sm:inline-block ml-2 text-xs font-medium text-stone-500 bg-stone-100 px-2 py-0.5 rounded-full">
                            Restaurante
                        </span>
                    </div>
                </div>

                <!-- Selector de Sucursal y Enlaces Centrales -->
                <nav class="hidden md:flex items-center space-x-6 text-sm font-medium">
                    <a href="/" class="text-stone-700 hover:text-orange-600 transition">Inicio</a>
                    <a href="/#menu" class="text-stone-700 hover:text-orange-600 transition">Menú</a>
                    <a href="/#promociones" class="text-stone-700 hover:text-orange-600 transition">Promociones</a>
                    <a href="/reservar" class="text-stone-700 hover:text-orange-600 transition">Reservar Mesa</a>

                    <!-- Indicador de Sucursal activa -->
                    <div class="flex items-center space-x-1.5 bg-stone-100 px-3 py-1.5 rounded-lg border border-stone-200 text-stone-700 text-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-semibold">{{ sucursalNombre || 'Sede Centro' }}</span>
                    </div>
                </nav>

                <!-- Acciones de Usuario -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <template v-if="$page.props.auth?.user">
                        <a
                            :href="dashboardUrl"
                            class="text-xs sm:text-sm font-semibold text-stone-700 hover:text-orange-600 px-3 py-1.5 rounded-lg border border-stone-200 bg-stone-50 hover:bg-white shadow-2xs transition"
                        >
                            Mi Panel ({{ $page.props.auth.user.name }})
                        </a>
                        <button
                            type="button"
                            @click="cerrarSesion"
                            class="text-xs sm:text-sm font-medium text-stone-500 hover:text-rose-600 px-2.5 py-1.5 rounded-lg border border-transparent hover:border-rose-200 hover:bg-rose-50 transition flex items-center space-x-1"
                            title="Cerrar sesión actual"
                        >
                            <span>Salir</span>
                            <span class="text-xs">🚪</span>
                        </button>
                    </template>
                    <template v-else>
                        <a
                            href="/login"
                            class="text-xs sm:text-sm font-medium text-stone-700 hover:text-stone-900 px-3 py-1.5 transition"
                        >
                            Ingresar
                        </a>
                        <a
                            href="/registro"
                            class="text-xs sm:text-sm font-semibold bg-orange-600 hover:bg-orange-700 text-white px-3.5 py-1.5 rounded-lg shadow-xs transition"
                        >
                            Registrarse
                        </a>
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

        <!-- Contenido principal -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Botón flotante de pedido con icono de campanita de restaurante -->
        <div class="fixed bottom-6 right-6 z-50">
            <button
                type="button"
                @click="toggleCarrito"
                class="relative bg-orange-600 hover:bg-orange-700 active:scale-95 text-white p-4 rounded-full shadow-xl transition-all duration-200 focus:outline-hidden focus:ring-4 focus:ring-orange-300 flex items-center justify-center group"
                title="Tu pedido (Campanita de servicio)"
                aria-label="Ver pedido en curso o carrito"
            >
                <RestaurantBellIcon class="w-7 h-7 text-white transform group-hover:-rotate-12 transition-transform duration-200" />
                <!-- Badge con conteo de items del pedido -->
                <span
                    v-if="itemsCount > 0"
                    class="absolute -top-1 -right-1 bg-amber-400 text-stone-950 font-extrabold text-xs px-2 py-0.5 rounded-full ring-2 ring-white shadow-xs"
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
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import RestaurantBellIcon from '@/Components/UI/RestaurantBellIcon.vue';

const page = usePage();
const itemsCount = ref(0);
const sucursalNombre = ref('Sede Centro');

const dashboardUrl = computed(() => {
    return page.props.auth?.user?.dashboard_url || '/cliente';
});

function cerrarSesion() {
    router.post('/logout');
}

function toggleCarrito() {
    // Evento para abrir el carrito/pedido flotante
    window.dispatchEvent(new CustomEvent('toggle-carrito'));
}
</script>

