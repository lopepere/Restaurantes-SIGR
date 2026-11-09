<template>
    <PublicLayout>
        <div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl border border-stone-200 shadow-sm">
                <div>
                    <h2 class="text-center text-2xl font-extrabold text-stone-900">Recuperar Contraseña</h2>
                    <p class="mt-2 text-center text-xs text-stone-600">
                        Te enviaremos un enlace a tu correo para restablecer tu acceso.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Correo Electrónico</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                        />
                        <p v-if="form.errors.email" class="text-xs text-rose-600 mt-1">{{ form.errors.email }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 transition disabled:opacity-50"
                    >
                        Enviar Enlace de Recuperación
                    </button>

                    <div class="text-center text-xs">
                        <a href="/login" class="text-stone-500 hover:text-stone-900 font-semibold">Volver a iniciar sesión</a>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const form = useForm({ email: '' });

function submit() {
    form.post('/forgot-password');
}
</script>
