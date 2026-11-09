<template>
    <PublicLayout>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-b from-orange-50/50 to-white py-16 sm:py-24 border-b border-stone-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="inline-flex items-center space-x-2 bg-orange-100 text-orange-800 text-xs font-semibold px-3 py-1 rounded-full mb-6">
                    <span>🌟</span>
                    <span>Bienvenidos a SIGR — Experiencia Gastronómica</span>
                </div>
                <h1 class="text-4xl sm:text-6xl font-extrabold text-stone-900 tracking-tight leading-tight">
                    Sabor artesanal, gestión en <span class="text-orange-600">tiempo real</span>.
                </h1>
                <p class="mt-4 max-w-2xl mx-auto text-base sm:text-lg text-stone-600">
                    Pide en línea a domicilio, para llevar o reserva tu mesa en nuestra sede favorita. Sin fricciones ni registros obligatorios.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a
                        href="#menu"
                        class="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm shadow-md hover:shadow-lg transition active:scale-95 text-center"
                    >
                        Ver Menú Digital
                    </a>
                    <a
                        href="#reservas"
                        class="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-white hover:bg-stone-50 text-stone-800 font-bold text-sm border border-stone-300 shadow-xs transition active:scale-95 text-center"
                    >
                        Reservar Mesa
                    </a>
                </div>
            </div>
        </section>

        <!-- Sección de muestra del Menú y Campanita -->
        <section id="menu" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-stone-900 tracking-tight">Menú Destacado</h2>
                    <p class="text-stone-500 text-sm mt-1">Explora nuestros platos preparados con ingredientes frescos de la región.</p>
                </div>
            </div>

            <!-- Placeholder temporal para Iteración I-2 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-xs hover:shadow-md transition">
                    <div class="h-40 bg-stone-100 rounded-xl mb-4 flex items-center justify-center text-4xl">
                        🍲
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-md">Entradas</span>
                        <span class="font-extrabold text-stone-900">$ 14.000,00</span>
                    </div>
                    <h3 class="font-bold text-lg text-stone-900 mt-2">Patacones con Hogao</h3>
                    <p class="text-stone-500 text-xs mt-1 leading-relaxed">
                        Plátano verde crujiente acompañado de tradicional hogao criollo colombiano.
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-xs hover:shadow-md transition">
                    <div class="h-40 bg-stone-100 rounded-xl mb-4 flex items-center justify-center text-4xl">
                        🥩
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-md">Platos principales</span>
                        <span class="font-extrabold text-stone-900">$ 38.000,00</span>
                    </div>
                    <h3 class="font-bold text-lg text-stone-900 mt-2">Bandeja Paisa Tradicional</h3>
                    <p class="text-stone-500 text-xs mt-1 leading-relaxed">
                        Frijoles, arroz, chicharrón crujiente, carne molida, huevo frito, tajada de plátano y arepa.
                    </p>
                </div>

                <div class="bg-white rounded-2xl border border-stone-200 p-5 shadow-xs hover:shadow-md transition">
                    <div class="h-40 bg-stone-100 rounded-xl mb-4 flex items-center justify-center text-4xl">
                        🥥
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-md">Bebidas</span>
                        <span class="font-extrabold text-stone-900">$ 12.000,00</span>
                    </div>
                    <h3 class="font-bold text-lg text-stone-900 mt-2">Limonada de Coco</h3>
                    <p class="text-stone-500 text-xs mt-1 leading-relaxed">
                        Refrescante combinación de leche de coco y zumo natural de limón recién exprimido.
                    </p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
</script>
