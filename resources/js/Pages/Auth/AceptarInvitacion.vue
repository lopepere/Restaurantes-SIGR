<template>
    <PublicLayout>
        <div class="min-h-[75vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl border border-stone-200 shadow-sm">
                <div class="text-center">
                    <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto text-2xl mb-3">
                        🎉
                    </div>
                    <h2 class="text-2xl font-extrabold text-stone-900">Activa tu Cuenta</h2>
                    <p class="mt-2 text-xs text-stone-600">
                        Hola <strong class="text-stone-800">{{ user.nombre }}</strong> ({{ user.email }}). Define tu contraseña para acceder a tu panel de cliente y consultar tus comprobantes.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Contraseña Nueva *</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                            placeholder="Mínimo 8 caracteres (letras y números)"
                        />
                        <p v-if="form.errors.password" class="text-xs text-rose-600 mt-1">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Confirmar Contraseña *</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 transition disabled:opacity-50"
                    >
                        <span v-if="form.processing">Activando cuenta...</span>
                        <span v-else>Activar Cuenta y Acceder</span>
                    </button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(window.location.pathname + window.location.search);
}
</script>
