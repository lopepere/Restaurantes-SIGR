<template>
    <PublicLayout>
        <div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl border border-stone-200 shadow-sm">
                <div>
                    <h2 class="text-center text-2xl font-extrabold text-stone-900">Nueva Contraseña</h2>
                    <p class="mt-2 text-center text-xs text-stone-600">Ingresa tu nueva clave de acceso.</p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Correo Electrónico</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            readonly
                            class="mt-1 block w-full rounded-lg bg-stone-50 border border-stone-300 px-3 py-2 text-sm text-stone-600"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Nueva Contraseña</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                        />
                        <p v-if="form.errors.password" class="text-xs text-rose-600 mt-1">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Confirmar Nueva Contraseña</label>
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
                        Restablecer Contraseña
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
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password');
}
</script>
