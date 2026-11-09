<template>
    <PublicLayout>
        <div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl border border-stone-200 shadow-sm text-center">
                <div class="w-16 h-16 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto text-3xl">
                    ✉️
                </div>
                <h2 class="text-2xl font-extrabold text-stone-900">Verifica tu Correo</h2>
                <p class="text-xs text-stone-600 leading-relaxed">
                    Hemos enviado un enlace de confirmación a tu dirección de correo electrónico. Por favor revisa tu bandeja de entrada o carpeta de spam para activar tu cuenta.
                </p>

                <div class="pt-4 flex flex-col space-y-3">
                    <form @submit.prevent="resendVerification">
                        <button
                            type="submit"
                            :disabled="resending"
                            class="w-full py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 transition disabled:opacity-50"
                        >
                            <span v-if="resending">Reenviando...</span>
                            <span v-else>Reenviar Correo de Verificación</span>
                        </button>
                    </form>

                    <form method="POST" action="/logout">
                        <button type="submit" class="text-xs text-stone-500 hover:text-stone-900 font-semibold">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const resending = ref(false);

function resendVerification() {
    resending.value = true;
    router.post('/email/verification-notification', {}, {
        onFinish: () => {
            resending.value = false;
        },
    });
}
</script>
