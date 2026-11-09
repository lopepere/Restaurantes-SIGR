<template>
    <PublicLayout>
        <div class="min-h-[75vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl border border-stone-200 shadow-sm">
                <div>
                    <h2 class="text-center text-3xl font-extrabold text-stone-900">
                        Iniciar Sesión
                    </h2>
                    <p class="mt-2 text-center text-sm text-stone-600">
                        O accede a tu mesa directamente desde el menú
                    </p>
                </div>

                <form class="mt-8 space-y-6" @submit.prevent="submit">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-stone-700">Correo Electrónico</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                                placeholder="tu@correo.com"
                            />
                            <p v-if="form.errors.email" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700">Contraseña</label>
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                            />
                            <p v-if="form.errors.password" class="text-xs text-rose-600 mt-1">{{ form.errors.password }}</p>
                        </div>

                        <div
                            v-if="esErrorSesionActiva"
                            class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800 space-y-2"
                        >
                            <p class="font-semibold">¿Cerraste el navegador sin salir y tu cuenta quedó bloqueada?</p>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="form.forzar_cierre_previa"
                                    type="checkbox"
                                    class="rounded-sm border-amber-400 text-orange-600 focus:ring-orange-500"
                                />
                                <span>Cerrar la sesión anterior e iniciar sesión aquí</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center">
                            <input v-model="form.remember" type="checkbox" class="rounded-sm border-stone-300 text-orange-600 focus:ring-orange-500" />
                            <span class="ml-2 text-stone-600">Recordarme</span>
                        </label>
                        <Link href="/forgot-password" class="font-medium text-orange-600 hover:text-orange-500">¿Olvidaste tu contraseña?</Link>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex justify-center py-2.5 px-4 rounded-xl shadow-xs text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition disabled:opacity-50"
                        >
                            <span v-if="form.processing">Ingresando...</span>
                            <span v-else>Ingresar</span>
                        </button>
                    </div>

                    <div class="text-center text-xs text-stone-500">
                        ¿No tienes una cuenta aún?
                        <Link href="/registro" class="font-bold text-orange-600 hover:underline ml-1">Regístrate aquí</Link>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const page = usePage();

// Si el servidor renderiza /login sin usuario autenticado, limpiar cualquier marca residual de sessionStorage
// para evitar bucles infinitos de redirección tras cerrar sesión en cualquier rol (Cajero, Cocina, etc.).
if (typeof window !== 'undefined') {
    if (page.props.auth?.user) {
        const destino = page.props.auth.user.dashboard_url || '/';
        window.location.replace(destino);
    } else {
        sessionStorage.removeItem('sigr_auth_active');
        sessionStorage.removeItem('sigr_dashboard');
    }
}

const form = useForm({
    email: '',
    password: '',
    remember: false,
    forzar_cierre_previa: false,
});

const esErrorSesionActiva = computed(() =>
    Boolean(form.errors.email && form.errors.email.toLowerCase().includes('sesión activa')),
);

onMounted(() => {
    if (page.props.auth?.user) {
        const destino = page.props.auth.user.dashboard_url || '/';
        window.location.replace(destino);
    } else {
        sessionStorage.removeItem('sigr_auth_active');
        sessionStorage.removeItem('sigr_dashboard');
    }

    // Manejar caché de historial del navegador (bfcache) al presionar Atrás
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });
});

function submit() {
    form.post('/login', {
        replace: true,
        onSuccess: (pageResp) => {
            if (typeof window !== 'undefined' && pageResp?.props?.auth?.user) {
                sessionStorage.setItem('sigr_auth_active', '1');
                if (pageResp.props.auth.user.dashboard_url) {
                    sessionStorage.setItem('sigr_dashboard', pageResp.props.auth.user.dashboard_url);
                }
            }
        },
        onFinish: () => form.reset('password'),
    });
}
</script>
