<template>
    <PublicLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Tarjeta principal de confirmación -->
            <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden text-center p-8 sm:p-12">
                <!-- Icono de Estado -->
                <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-6 shadow-xs">
                    ✓
                </div>

                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-2 bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ reserva.estado_etiqueta }}
                </span>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">
                    ¡Reserva Agendada con Éxito!
                </h1>

                <p class="mt-2 text-stone-600 text-sm max-w-md mx-auto">
                    Hemos registrado tu solicitud. Te enviamos los detalles a <span class="font-semibold text-stone-800">{{ reserva.email_contacto }}</span>.
                </p>

                <!-- Código de Reserva en Bloque Destacado -->
                <div class="my-8 p-6 bg-stone-50 rounded-2xl border border-stone-200/80 max-w-sm mx-auto">
                    <span class="text-xs font-semibold text-stone-500 uppercase tracking-wider block mb-1">
                        Código de Confirmación
                    </span>
                    <span class="text-2xl sm:text-3xl font-black font-mono tracking-widest text-orange-600 select-all">
                        {{ reserva.codigo }}
                    </span>
                </div>

                <!-- Detalles de la reserva -->
                <div class="border-t border-b border-stone-100 py-6 my-6 text-left space-y-3.5 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-stone-500">Sede / Sucursal:</span>
                        <span class="font-semibold text-stone-800">{{ reserva.sucursal.nombre }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-stone-500">Dirección:</span>
                        <span class="font-medium text-stone-700">{{ reserva.sucursal.direccion }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-stone-500">Fecha:</span>
                        <span class="font-semibold text-stone-800">{{ reserva.fecha }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-stone-500">Horario reservado:</span>
                        <span class="font-semibold text-stone-800">{{ reserva.hora_inicio }} a {{ reserva.hora_fin }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-stone-500">Comensales:</span>
                        <span class="font-semibold text-stone-800">{{ reserva.personas }} personas</span>
                    </div>
                    <div v-if="reserva.mesa" class="flex justify-between items-center">
                        <span class="text-stone-500">Mesa asignada:</span>
                        <span class="font-semibold text-orange-600">{{ reserva.mesa }}</span>
                    </div>
                    <div v-if="reserva.categoria" class="flex justify-between items-center">
                        <span class="text-stone-500">Tipo de ocasión:</span>
                        <span class="font-medium text-stone-700">{{ reserva.categoria }}</span>
                    </div>
                    <div v-if="reserva.notas" class="flex flex-col pt-2 border-t border-stone-100">
                        <span class="text-xs text-stone-400">Peticiones especiales:</span>
                        <span class="text-xs text-stone-600 italic mt-0.5">{{ reserva.notas }}</span>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <a
                        href="/reservar"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-stone-300 text-stone-700 hover:bg-stone-50 font-semibold text-xs tracking-wider transition"
                    >
                        Hacer otra reserva
                    </a>
                    <a
                        href="/"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs tracking-wider shadow-sm transition"
                    >
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    reserva: {
        type: Object,
        required: true,
    },
});
</script>
