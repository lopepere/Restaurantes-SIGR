<template>
    <PublicLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <!-- Tarjeta de Verificación de Comprobante -->
            <div class="bg-white rounded-3xl border border-stone-200 shadow-sm p-6 sm:p-8 space-y-6">
                <!-- Encabezado con Sello de Autenticidad -->
                <div class="text-center border-b border-stone-100 pb-6">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mx-auto mb-3 shadow-xs">
                        🛡️
                    </div>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 mb-2">
                        Autenticidad Verificada en Sistema (SC-03)
                    </span>
                    <h1 class="text-2xl font-black text-stone-900 tracking-tight">
                        {{ comprobante.numero }}
                    </h1>
                    <p class="text-xs text-stone-500 font-mono mt-1 break-all select-all">
                        CUV: {{ comprobante.codigo_verificacion }}
                    </p>
                </div>

                <!-- Datos de la Sede y Cliente -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs border-b border-stone-100 pb-6">
                    <div class="space-y-1">
                        <span class="font-bold text-stone-700 block uppercase tracking-wider text-[10px]">Emisor / Sucursal</span>
                        <p class="font-semibold text-stone-900">{{ comprobante.sucursal.nombre }}</p>
                        <p class="text-stone-500">{{ comprobante.sucursal.direccion }}</p>
                        <p class="text-stone-500">Tel: {{ comprobante.sucursal.telefono }}</p>
                    </div>
                    <div class="space-y-1 sm:text-right">
                        <span class="font-bold text-stone-700 block uppercase tracking-wider text-[10px]">Cliente / Receptor</span>
                        <p class="font-semibold text-stone-900">{{ comprobante.cliente_nombre }}</p>
                        <p v-if="comprobante.cliente_email" class="text-stone-500">{{ comprobante.cliente_email }}</p>
                        <p class="text-stone-400">Fecha: {{ comprobante.fecha }}</p>
                    </div>
                </div>

                <!-- Detalle de Ítems Consumidos -->
                <div class="space-y-3">
                    <h2 class="text-xs font-bold text-stone-700 uppercase tracking-wider">
                        Detalle del Consumo
                    </h2>
                    <div class="divide-y divide-stone-100 text-xs">
                        <div
                            v-for="item in comprobante.items"
                            :key="item.id"
                            class="py-2.5 flex justify-between items-center"
                        >
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-stone-700 bg-stone-100 px-2 py-0.5 rounded-md text-[11px]">
                                    {{ item.cantidad }}x
                                </span>
                                <span class="font-medium text-stone-800">{{ item.nombre }}</span>
                            </div>
                            <span class="font-bold text-stone-900 font-mono">
                                ${{ item.total.toLocaleString() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Totales y Desglose Tributario -->
                <div class="border-t border-stone-100 pt-4 space-y-2 text-xs">
                    <div class="flex justify-between text-stone-600">
                        <span>Subtotal:</span>
                        <span class="font-mono">${{ comprobante.subtotal.toLocaleString() }}</span>
                    </div>
                    <div v-if="comprobante.descuento > 0" class="flex justify-between text-emerald-600">
                        <span>Descuento aplicado:</span>
                        <span class="font-mono">-${{ comprobante.descuento.toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between text-stone-600">
                        <span>Impuestos (INC / IVA):</span>
                        <span class="font-mono">${{ comprobante.impuesto_total.toLocaleString() }}</span>
                    </div>
                    <div v-if="comprobante.propina > 0" class="flex justify-between text-amber-700 font-semibold">
                        <span>Propina voluntaria (SC-02):</span>
                        <span class="font-mono">${{ comprobante.propina.toLocaleString() }}</span>
                    </div>
                    <div class="flex justify-between text-base font-extrabold text-stone-900 border-t border-stone-200 pt-2">
                        <span>TOTAL PAGADO:</span>
                        <span class="font-mono text-orange-600">${{ comprobante.total.toLocaleString() }}</span>
                    </div>
                </div>

                <!-- Hash de Integridad y Métodos -->
                <div class="bg-stone-50 p-3 rounded-xl border border-stone-200/80 text-[10px] space-y-1 font-mono text-stone-500">
                    <p><strong>Método de pago:</strong> {{ comprobante.metodo_pago }}</p>
                    <p class="break-all select-all"><strong>Hash SHA-256:</strong> {{ comprobante.hash_pdf }}</p>
                </div>

                <!-- Botón de Descarga / Impresión de Tirilla -->
                <div class="pt-2 flex justify-center">
                    <a
                        :href="`/comprobante/${comprobante.codigo_verificacion}/tirilla`"
                        target="_blank"
                        class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl bg-stone-900 hover:bg-stone-800 text-white font-bold text-xs shadow-xs transition"
                    >
                        <span>🖨️</span>
                        <span>Ver Formato Tirilla 80 mm</span>
                    </a>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    comprobante: {
        type: Object,
        required: true,
    },
});
</script>
