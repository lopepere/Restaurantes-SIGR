<template>
    <ClienteLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-stone-200 shadow-xs">
                <h1 class="text-xl font-bold text-stone-900 mb-1">Mi Perfil</h1>
                <p class="text-xs text-stone-500">Actualiza tus datos de contacto y preferencias.</p>

                <!-- Avatar Local -->
                <div class="mt-6 flex items-center space-x-6 pb-6 border-b border-stone-200">
                    <div class="w-16 h-16 rounded-full bg-stone-100 border-2 border-orange-500 overflow-hidden flex items-center justify-center">
                        <img v-if="user.avatar" :src="'/storage/' + user.avatar" :alt="user.nombre" class="w-full h-full object-cover" />
                        <span v-else class="text-lg font-bold text-stone-600">{{ user.nombre[0] }}</span>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-stone-800">Avatar Local Generado</h2>
                        <p class="text-xs text-stone-500">Sin conexiones a servicios externos.</p>
                        <button
                            type="button"
                            @click="regenerarAvatar"
                            class="mt-2 text-xs font-semibold text-orange-600 hover:text-orange-700 bg-orange-50 hover:bg-orange-100 px-3 py-1.5 rounded-lg transition"
                        >
                            🎲 Regenerar Avatar Aleatorio
                        </button>
                    </div>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Nombre Completo</label>
                        <input
                            v-model="form.nombre"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                        />
                        <p v-if="form.errors.nombre" class="text-xs text-rose-600 mt-1">{{ form.errors.nombre }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Correo Electrónico (No modificable)</label>
                            <input
                                :value="user.email"
                                type="email"
                                readonly
                                class="mt-1 block w-full rounded-lg bg-stone-50 border border-stone-200 px-3 py-2 text-sm text-stone-500 cursor-not-allowed"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Teléfono (E.164)</label>
                            <input
                                v-model="form.telefono"
                                type="tel"
                                required
                                class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                            />
                            <p v-if="form.errors.telefono" class="text-xs text-rose-600 mt-1">{{ form.errors.telefono }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Dirección de Entrega</label>
                        <input
                            v-model="form.direccion"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                        />
                        <p v-if="form.errors.direccion" class="text-xs text-rose-600 mt-1">{{ form.errors.direccion }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Idioma de la Interfaz</label>
                        <select
                            v-model="form.idioma"
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                        >
                            <option value="es">Español</option>
                            <option value="en">English</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-stone-200">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-stone-500 mb-3">Cambiar Contraseña (Opcional)</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-stone-700">Nueva Contraseña</label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-700">Confirmar</label>
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                                />
                            </div>
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-rose-600 mt-1">{{ form.errors.password }}</p>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 transition disabled:opacity-50"
                        >
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClienteLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import ClienteLayout from '@/Layouts/ClienteLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    nombre: props.user.nombre,
    telefono: props.user.telefono,
    direccion: props.user.direccion,
    idioma: props.user.idioma || 'es',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put('/cliente/perfil', {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
}

function regenerarAvatar() {
    router.post('/cliente/perfil/avatar');
}
</script>
