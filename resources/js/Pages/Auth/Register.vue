<template>
    <PublicLayout>
        <div class="min-h-[85vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl border border-stone-200 shadow-sm">
                <div>
                    <h2 class="text-center text-3xl font-extrabold text-stone-900">
                        Crear Cuenta
                    </h2>
                    <p class="mt-2 text-center text-xs text-stone-600">
                        Registro voluntario para acumular historial y consultar tus comprobantes
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Nombre Completo *</label>
                        <input
                            v-model="form.nombre"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                            placeholder="Ana Pérez"
                        />
                        <p v-if="form.errors.nombre" class="text-xs text-rose-600 mt-1">{{ form.errors.nombre }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Correo Electrónico *</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                            placeholder="ana@correo.com"
                        />
                        <p v-if="form.errors.email" class="text-xs text-rose-600 mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Teléfono (Móvil) *</label>
                        <input
                            v-model="form.telefono"
                            type="tel"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                            placeholder="+573001234567"
                        />
                        <p v-if="form.errors.telefono" class="text-xs text-rose-600 mt-1">{{ form.errors.telefono }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700">Dirección de Entrega *</label>
                        <input
                            v-model="form.direccion"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                            placeholder="Calle 10 # 40-20, Medellín"
                        />
                        <p v-if="form.errors.direccion" class="text-xs text-rose-600 mt-1">{{ form.errors.direccion }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Contraseña *</label>
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700">Confirmar *</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm shadow-xs focus:border-orange-500 focus:ring-orange-500"
                            />
                        </div>
                    </div>
                    <p v-if="form.errors.password" class="text-xs text-rose-600 mt-1">{{ form.errors.password }}</p>

                    <!-- Aceptación obligatoria de política de tratamiento de datos personales (Ley 1581) -->
                    <div class="pt-2">
                        <label class="flex items-start space-x-2">
                            <input
                                v-model="form.politica_aceptada"
                                type="checkbox"
                                required
                                class="mt-0.5 rounded-sm border-stone-300 text-orange-600 focus:ring-orange-500"
                            />
                            <span class="text-xs text-stone-600 leading-tight">
                                Acepto la política de tratamiento de datos personales conforme a la Ley Estatutaria 1581 de 2012. *
                            </span>
                        </label>
                        <p v-if="form.errors.politica_aceptada" class="text-xs text-rose-600 mt-1">{{ form.errors.politica_aceptada }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full mt-4 flex justify-center py-2.5 px-4 rounded-xl shadow-xs text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition disabled:opacity-50"
                    >
                        <span v-if="form.processing">Registrando cuenta...</span>
                        <span v-else>Crear Cuenta</span>
                    </button>

                    <div class="text-center text-xs text-stone-500 pt-2">
                        ¿Ya tienes una cuenta?
                        <a href="/login" class="font-bold text-orange-600 hover:underline ml-1">Ingresa aquí</a>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const form = useForm({
    nombre: '',
    email: '',
    telefono: '',
    direccion: '',
    password: '',
    password_confirmation: '',
    politica_aceptada: false,
});

function submit() {
    form.post('/registro', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>
