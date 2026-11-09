<template>
    <PublicLayout :sucursal-nombre="sucursalActiva?.nombre" :items-count="totalItemsEnCarrito" @toggle-carrito="mostrarCarrito = !mostrarCarrito">
        <!-- Alerta flotante de Inventario / Rol en tiempo real -->
        <div v-if="alertaInventario" class="fixed top-20 right-6 z-50 max-w-md bg-amber-500 text-stone-950 px-4 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-amber-300 animate-bounce">
            <span class="text-xl">⚠️</span>
            <span class="text-xs font-black">{{ alertaInventario }}</span>
            <button type="button" @click="alertaInventario = ''" class="ml-auto text-stone-900 font-bold hover:text-black">✕</button>
        </div>
        <!-- Hero Section y Selector de Sucursal -->
        <section class="bg-gradient-to-b from-orange-50/70 via-stone-50 to-stone-50 py-10 px-4 sm:px-6 lg:px-8 border-b border-stone-200">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 bg-orange-100 text-orange-800 text-xs font-semibold px-3 py-1 rounded-full mb-3">
                            <span>✨ Bienvenido a SIGR Gourmet</span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-stone-900 tracking-tight">
                            Sabores Auténticos, <span class="text-orange-600">Experiencias Vivas</span>
                        </h1>
                        <p class="mt-2 text-stone-600 max-w-2xl text-sm sm:text-base">
                            Explora nuestro menú artesanal preparado con ingredientes frescos del día. Pide en tu mesa, para llevar o a domicilio sin fricciones.
                        </p>
                    </div>

                    <!-- Selector de Sucursal Activa (RF-PUB-02) -->
                    <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm flex flex-col sm:min-w-[280px]">
                        <label for="sucursalSelect" class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                            <span>📍</span> Sede Activa
                        </label>
                        <select
                            id="sucursalSelect"
                            v-model="sucursalSeleccionadaSlug"
                            @change="cambiarSucursal"
                            class="w-full bg-stone-50 border border-stone-300 text-stone-900 text-sm font-semibold rounded-xl p-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition cursor-pointer"
                        >
                            <option v-for="suc in sucursales" :key="suc.id" :value="suc.slug">
                                {{ suc.nombre }}
                            </option>
                        </select>
                        <div v-if="sucursalActiva" class="mt-2 text-xs text-stone-500 flex flex-col gap-0.5">
                            <span>{{ sucursalActiva.direccion }}</span>
                            <span class="text-stone-400">📞 {{ sucursalActiva.telefono }}</span>
                        </div>
                    </div>
                </div>

                <!-- Carrusel de Promociones Vigentes Primero (RF-PUB-03, ERS 8.1) -->
                <div v-if="menu.promociones && menu.promociones.length > 0" class="mt-8" id="promociones">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-bold text-stone-900 flex items-center gap-2">
                            <span>🔥</span> Promociones del Día
                        </h2>
                        <span class="text-xs font-medium text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full border border-orange-200">
                            Tiempo Limitado
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="promo in menu.promociones"
                            :key="promo.id"
                            class="bg-gradient-to-r from-orange-600 to-amber-600 text-white rounded-2xl p-5 shadow-sm hover:shadow-md transition relative overflow-hidden flex flex-col justify-between"
                        >
                            <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-white/10 rounded-full blur-xl pointer-events-none"></div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="bg-white/20 text-white text-xs font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full backdrop-blur-xs">
                                        {{ promo.tipo_beneficio === 'porcentaje' ? `${promo.valor_beneficio}% OFF` : 'Especial' }}
                                    </span>
                                </div>
                                <h3 class="text-xl font-black tracking-tight leading-snug">{{ promo.nombre }}</h3>
                                <p class="text-white/90 text-xs mt-1.5 leading-relaxed">{{ promo.descripcion }}</p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-white/20 flex items-center justify-between text-xs text-white/80">
                                <span>Aplica en la sede actual</span>
                                <span class="font-bold underline decoration-white/40">Ver en menú ↓</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Barra de Filtros, Categorías y Buscador en Vivo (RF-PUB-04, 05) -->
        <section class="sticky top-16 z-30 bg-white/95 backdrop-blur-sm border-b border-stone-200 shadow-xs py-3.5 px-4 sm:px-6 lg:px-8" id="menu">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <!-- Pestañas de Categorías -->
                <div class="flex items-center space-x-2 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0 scrollbar-none">
                    <button
                        type="button"
                        @click="categoriaActivaId = null"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition whitespace-nowrap cursor-pointer',
                            categoriaActivaId === null
                                ? 'bg-orange-600 text-white shadow-xs'
                                : 'bg-stone-100 hover:bg-stone-200 text-stone-700'
                        ]"
                    >
                        Todas las Categorías
                    </button>
                    <button
                        v-for="cat in menu.categorias"
                        :key="cat.id"
                        type="button"
                        @click="categoriaActivaId = cat.id"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition whitespace-nowrap cursor-pointer flex items-center gap-1.5',
                            categoriaActivaId === cat.id
                                ? 'bg-orange-600 text-white shadow-xs'
                                : 'bg-stone-100 hover:bg-stone-200 text-stone-700'
                        ]"
                    >
                        <span>{{ obtenerIconoCategoria(cat.nombre) }}</span>
                        <span>{{ cat.nombre }}</span>
                        <span class="text-xs opacity-75">({{ cat.items.length }})</span>
                    </button>
                </div>

                <!-- Buscador en Vivo (RF-PUB-05) -->
                <div class="relative w-full sm:w-72">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-stone-400">
                        🔍
                    </span>
                    <input
                        type="text"
                        v-model="terminoBusqueda"
                        placeholder="Buscar en el menú..."
                        class="w-full bg-stone-50 border border-stone-300 rounded-xl pl-9 pr-8 py-2 text-xs sm:text-sm text-stone-800 placeholder-stone-400 focus:outline-hidden focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition"
                    />
                    <button
                        v-if="terminoBusqueda"
                        @click="terminoBusqueda = ''"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-stone-400 hover:text-stone-600"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </section>

        <!-- Listado de Productos Agrupados por Categoría (RF-PUB-04, 08, CA-26) -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div v-if="categoriasFiltradas.length === 0" class="text-center py-16 bg-white rounded-3xl border border-dashed border-stone-300">
                <span class="text-4xl block mb-2">🍽️</span>
                <h3 class="text-lg font-bold text-stone-800">No se encontraron productos</h3>
                <p class="text-stone-500 text-sm mt-1">Intenta con otro término de búsqueda o selecciona otra categoría.</p>
                <button
                    @click="terminoBusqueda = ''; categoriaActivaId = null"
                    class="mt-4 px-4 py-2 bg-orange-600 text-white text-xs font-semibold rounded-xl hover:bg-orange-700 transition"
                >
                    Ver Todo el Menú
                </button>
            </div>

            <div v-for="cat in categoriasFiltradas" :key="cat.id" class="mb-12">
                <!-- Cabecera de Categoría -->
                <div class="flex items-center gap-3 mb-6 pb-2 border-b border-stone-200">
                    <span class="text-2xl">{{ obtenerIconoCategoria(cat.nombre) }}</span>
                    <div>
                        <h2 class="text-2xl font-black text-stone-900 tracking-tight">{{ cat.nombre }}</h2>
                        <p v-if="cat.descripcion" class="text-xs text-stone-500 mt-0.5">{{ cat.descripcion }}</p>
                    </div>
                </div>

                <!-- Grilla de Productos -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="item in cat.items"
                        :key="item.id"
                        :class="[
                            'bg-white rounded-2xl border transition-all duration-200 flex flex-col justify-between p-5 relative overflow-hidden',
                            item.esta_agotado
                                ? 'border-stone-200 opacity-60 bg-stone-100/60 grayscale'
                                : 'border-stone-200 hover:border-orange-300 hover:shadow-md'
                        ]"
                    >
                        <!-- Badge de Combo o Promoción -->
                        <div class="flex items-center gap-2 mb-2.5">
                            <span v-if="item.es_combo" class="bg-purple-100 text-purple-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                Combo Especial
                            </span>
                            <span v-if="item.promocion" class="bg-red-100 text-red-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                {{ item.promocion.nombre }}
                            </span>
                            <span v-if="item.esta_agotado" class="bg-stone-300 text-stone-700 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                Agotado
                            </span>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-stone-900 tracking-tight">{{ item.nombre }}</h3>
                            <p class="text-xs text-stone-500 mt-1.5 leading-relaxed">{{ item.descripcion || 'Especialidad preparada con los mejores ingredientes.' }}</p>

                            <!-- Componentes si es combo -->
                            <div v-if="item.es_combo && item.items_del_combo.length > 0" class="mt-3 bg-stone-50 rounded-xl p-2.5 border border-stone-200 text-xs">
                                <span class="font-bold text-stone-700 block mb-1">Incluye:</span>
                                <ul class="list-disc list-inside text-stone-600 space-y-0.5">
                                    <li v-for="comp in item.items_del_combo" :key="comp.id">
                                        {{ comp.cantidad }}x {{ comp.nombre }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Precio y Acción de Compra -->
                        <div class="mt-5 pt-4 border-t border-stone-100 flex items-center justify-between">
                            <div>
                                <div v-if="item.precio_promocional !== null" class="flex items-baseline gap-2">
                                    <span class="text-xl font-black text-orange-600">{{ item.precio_promocional_formateado }}</span>
                                    <span class="text-xs text-stone-400 line-through">{{ item.precio_formateado }}</span>
                                </div>
                                <div v-else>
                                    <span class="text-xl font-black text-stone-900">{{ item.precio_formateado }}</span>
                                </div>
                                <span class="text-[10px] text-stone-400 block">Impuesto (INC 8%) incluido</span>
                                <span v-if="item.controla_inventario && !item.esta_agotado && item.existencia <= 5" class="inline-block mt-0.5 text-[10px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded">
                                    ¡Solo quedan {{ item.existencia }} disp.!
                                </span>
                            </div>

                            <!-- Botón de Agregar / Cantidad -->
                            <div>
                                <template v-if="item.esta_agotado">
                                    <button
                                        disabled
                                        class="px-3.5 py-2 bg-stone-200 text-stone-500 text-xs font-semibold rounded-xl cursor-not-allowed"
                                    >
                                        Agotado
                                    </button>
                                </template>
                                <template v-else-if="obtenerCantidadEnCarrito(item.id) > 0">
                                    <div class="flex items-center space-x-1.5 bg-orange-50 border border-orange-200 rounded-xl p-1">
                                        <button
                                            type="button"
                                            @click="decrementarCantidad(item.id)"
                                            class="w-7 h-7 flex items-center justify-center text-orange-700 font-bold bg-white rounded-lg hover:bg-orange-100 transition"
                                        >
                                            -
                                        </button>
                                        <span class="w-6 text-center text-xs font-extrabold text-orange-950">
                                            {{ obtenerCantidadEnCarrito(item.id) }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="incrementarCantidad(item)"
                                            class="w-7 h-7 flex items-center justify-center text-orange-700 font-bold bg-white rounded-lg hover:bg-orange-100 transition"
                                        >
                                            +
                                        </button>
                                    </div>
                                </template>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="agregarAlCarrito(item)"
                                        class="px-4 py-2 bg-orange-600 hover:bg-orange-700 active:scale-95 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                                    >
                                        <span>+</span>
                                        <span>Agregar</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modal / Drawer del Carrito de Pedido (RF-PUB-09, SC-02) -->
        <div v-if="mostrarCarrito" class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-stone-900/40 backdrop-blur-xs transition-opacity" @click="mostrarCarrito = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col justify-between">
                    <!-- Cabecera del Carrito -->
                    <div class="p-6 border-b border-stone-200 flex items-center justify-between bg-stone-50">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🛎️</span>
                            <div>
                                <h3 class="text-lg font-bold text-stone-900">Tu Pedido</h3>
                                <p class="text-xs text-stone-500">{{ sucursalActiva?.nombre }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="mostrarCarrito = false"
                            class="text-stone-400 hover:text-stone-600 p-2 rounded-xl"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Lista de Ítems en el Carrito -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div v-if="carrito.length === 0" class="text-center py-12 text-stone-400">
                            <span class="text-4xl block mb-2">🍽️</span>
                            <p class="font-medium text-stone-700">Tu pedido está vacío</p>
                            <p class="text-xs mt-1">Agrega deliciosos platos o bebidas del menú.</p>
                        </div>

                        <div
                            v-for="item in carrito"
                            :key="item.id"
                            class="flex items-start justify-between pb-4 border-b border-stone-100"
                        >
                            <div class="flex-1 pr-3">
                                <h4 class="text-sm font-bold text-stone-900">{{ item.nombre }}</h4>
                                <span class="text-xs text-stone-500 font-medium">
                                    {{ item.precio_unitario_formateado }} c/u
                                </span>
                                <input
                                    type="text"
                                    v-model="item.notas"
                                    placeholder="Nota: sin cebolla, salsas aparte..."
                                    class="mt-2 w-full text-[11px] bg-stone-50 border border-stone-200 rounded-lg p-1.5 text-stone-700 focus:outline-hidden focus:border-orange-400"
                                />
                            </div>

                            <div class="flex flex-col items-end gap-1.5">
                                <span class="text-sm font-extrabold text-stone-900">
                                    {{ formatearMoneda(item.precio_unitario * item.cantidad) }}
                                </span>
                                <div class="flex items-center space-x-1.5 bg-stone-100 rounded-lg p-1">
                                    <button
                                        @click="decrementarCantidad(item.id)"
                                        class="w-6 h-6 flex items-center justify-center font-bold text-stone-700 bg-white rounded-md hover:bg-stone-200 transition"
                                    >
                                        -
                                    </button>
                                    <span class="w-5 text-center text-xs font-bold">{{ item.cantidad }}</span>
                                    <button
                                        @click="incrementarCantidad(item)"
                                        class="w-6 h-6 flex items-center justify-center font-bold text-stone-700 bg-white rounded-md hover:bg-stone-200 transition"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Propina Voluntaria (SC-02) -->
                        <div v-if="carrito.length > 0" class="mt-6 pt-4 border-t border-stone-200">
                            <label class="block text-xs font-bold text-stone-800 uppercase tracking-wider mb-2">
                                🤝 Propina Voluntaria (Opcional)
                            </label>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    type="button"
                                    @click="tipoPropina = 'ninguna'; porcentajePropina = 0; valorPropinaLibre = 0"
                                    :class="[
                                        'py-2 rounded-xl text-xs font-bold border transition text-center cursor-pointer',
                                        tipoPropina === 'ninguna' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100'
                                    ]"
                                >
                                    Sin propina
                                </button>
                                <button
                                    type="button"
                                    @click="tipoPropina = '5'; porcentajePropina = 5; valorPropinaLibre = 0"
                                    :class="[
                                        'py-2 rounded-xl text-xs font-bold border transition text-center cursor-pointer',
                                        tipoPropina === '5' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100'
                                    ]"
                                >
                                    5%
                                </button>
                                <button
                                    type="button"
                                    @click="tipoPropina = '10'; porcentajePropina = 10; valorPropinaLibre = 0"
                                    :class="[
                                        'py-2 rounded-xl text-xs font-bold border transition text-center cursor-pointer',
                                        tipoPropina === '10' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100'
                                    ]"
                                >
                                    10%
                                </button>
                                <button
                                    type="button"
                                    @click="tipoPropina = 'abierta'"
                                    :class="[
                                        'py-2 rounded-xl text-xs font-bold border transition text-center cursor-pointer',
                                        tipoPropina === 'abierta' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100'
                                    ]"
                                >
                                    Otro
                                </button>
                            </div>
                            <div v-if="tipoPropina === 'abierta'" class="mt-2.5">
                                <input
                                    type="number"
                                    v-model.number="valorPropinaLibre"
                                    min="0"
                                    step="500"
                                    placeholder="Valor en pesos (ej: 5000)"
                                    class="w-full text-xs bg-stone-50 border border-stone-300 rounded-xl p-2.5 text-stone-900"
                                />
                            </div>
                            <p class="text-[11px] text-stone-500 mt-1.5 leading-tight">
                                La propina sugerida se calcula sobre la base neta antes de tributos y no forma parte de la base gravable.
                            </p>
                        </div>
                    </div>

                    <!-- Resumen del Total y Checkout -->
                    <div v-if="carrito.length > 0" class="p-6 border-t border-stone-200 bg-stone-50 space-y-3">
                        <div class="flex justify-between text-xs text-stone-600">
                            <span>Base imponible:</span>
                            <span>{{ formatearMoneda(resumenTotales.base) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-stone-600">
                            <span>Impuesto al consumo (8%):</span>
                            <span>{{ formatearMoneda(resumenTotales.impuesto) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-stone-600">
                            <span>Subtotal productos:</span>
                            <span>{{ formatearMoneda(resumenTotales.subtotal) }}</span>
                        </div>
                        <div v-if="resumenTotales.propina > 0" class="flex justify-between text-xs text-emerald-700 font-semibold">
                            <span>Propina voluntaria:</span>
                            <span>+ {{ formatearMoneda(resumenTotales.propina) }}</span>
                        </div>
                        <div class="pt-3 border-t border-stone-200 flex justify-between text-base font-black text-stone-900">
                            <span>Total a pagar:</span>
                            <span class="text-xl text-orange-600">{{ formatearMoneda(resumenTotales.total) }}</span>
                        </div>

                        <div v-if="esPersonal" class="p-3 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-950 space-y-1 mb-2">
                            <div class="flex items-center gap-1.5 font-bold text-amber-900">
                                <span>⚠️</span>
                                <span>Rol de Personal ({{ userRoles.join(', ') }})</span>
                            </div>
                            <p class="text-[11px] leading-snug">
                                Los pedidos desde la carta pública solo están permitidos para clientes o invitados. Como parte del personal debes utilizar tu panel operativo.
                            </p>
                        </div>

                        <button
                            type="button"
                            :disabled="esPersonal || carrito.length === 0"
                            @click="mostrarModalCheckout = true"
                            :class="[
                                'w-full py-3.5 text-sm font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2',
                                esPersonal || carrito.length === 0
                                    ? 'bg-stone-200 text-stone-400 cursor-not-allowed shadow-none'
                                    : 'bg-orange-600 hover:bg-orange-700 active:scale-98 text-white cursor-pointer'
                            ]"
                        >
                            <span>🛎️ Continuar con el Pedido</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Checkout / Confirmación de Pedido en Tiempo Real -->
        <div v-if="mostrarModalCheckout" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 overflow-hidden relative max-h-[92vh] flex flex-col">
                <div class="flex justify-between items-center pb-4 border-b border-stone-100">
                    <div>
                        <h3 class="text-lg font-black text-stone-900 flex items-center gap-2">
                            <span>🛎️ Finalizar Pedido</span>
                        </h3>
                        <p class="text-xs text-stone-500">{{ sucursalActiva.nombre }}</p>
                    </div>
                    <button
                        type="button"
                        @click="mostrarModalCheckout = false"
                        class="p-2 text-stone-400 hover:text-stone-700 rounded-lg hover:bg-stone-100 transition"
                    >
                        ✕
                    </button>
                </div>

                <div class="overflow-y-auto py-4 space-y-4 flex-1">
                    <!-- Modalidad del Pedido -->
                    <div>
                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-2">Modalidad</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="checkoutForm.tipo = 'domicilio'"
                                :class="['py-2.5 px-3 rounded-xl text-xs font-bold border transition text-center', checkoutForm.tipo === 'domicilio' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100']"
                            >
                                🛵 Domicilio
                            </button>
                            <button
                                type="button"
                                @click="checkoutForm.tipo = 'para_llevar'"
                                :class="['py-2.5 px-3 rounded-xl text-xs font-bold border transition text-center', checkoutForm.tipo === 'para_llevar' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100']"
                            >
                                🛍️ Para llevar
                            </button>
                            <button
                                type="button"
                                @click="checkoutForm.tipo = 'en_sitio'"
                                :class="['py-2.5 px-3 rounded-xl text-xs font-bold border transition text-center', checkoutForm.tipo === 'en_sitio' ? 'bg-orange-600 text-white border-orange-600' : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100']"
                            >
                                🍽️ En mesa
                            </button>
                        </div>
                    </div>

                    <!-- Datos del Contacto (Invitado o Autenticado) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-700 mb-1">Nombre completo *</label>
                            <input
                                v-model="checkoutForm.nombre_contacto"
                                type="text"
                                required
                                placeholder="Tu nombre"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-orange-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700 mb-1">Teléfono móvil *</label>
                            <input
                                v-model="checkoutForm.telefono_contacto"
                                type="tel"
                                required
                                placeholder="Ej: 3001234567"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-orange-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700 mb-1">Correo electrónico *</label>
                        <input
                            v-model="checkoutForm.email_contacto"
                            type="email"
                            required
                            placeholder="tucorreo@ejemplo.com"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-orange-500"
                        />
                    </div>

                    <div v-if="checkoutForm.tipo === 'domicilio'">
                        <label class="block text-xs font-semibold text-stone-700 mb-1">Dirección de entrega *</label>
                        <input
                            v-model="checkoutForm.direccion_entrega"
                            type="text"
                            required
                            placeholder="Calle, carrera, número, apartamento..."
                            class="w-full px-3 py-2 text-sm rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-orange-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-700 mb-1">Notas especiales para cocina</label>
                        <textarea
                            v-model="checkoutForm.notas"
                            rows="2"
                            placeholder="Ej: salsa aparte, cubiertos biodegradables..."
                            class="w-full px-3 py-2 text-sm rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-orange-500"
                        ></textarea>
                    </div>

                    <div v-if="checkoutError" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium">
                        {{ checkoutError }}
                    </div>
                </div>

                <div class="pt-4 border-t border-stone-100 flex items-center justify-between gap-4">
                    <div>
                        <span class="text-xs text-stone-500">Total a pagar:</span>
                        <p class="text-lg font-black text-orange-600">{{ formatearMoneda(resumenTotales.total) }}</p>
                    </div>

                    <button
                        type="button"
                        :disabled="enviandoPedido"
                        @click="enviarPedido"
                        class="px-6 py-3 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white font-bold text-sm rounded-xl transition cursor-pointer flex items-center gap-2"
                    >
                        <span v-if="enviandoPedido" class="animate-spin">⏳</span>
                        <span>Confirmar Pedido</span>
                    </button>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const page = usePage();
const alertaInventario = ref('');

const userRoles = computed(() => page.props.auth?.user?.roles || []);
const esPersonal = computed(() => {
    return userRoles.value.some(r => ['admin', 'super_admin', 'mesero', 'cajero', 'cocinero'].includes(r));
});

const props = defineProps({
    sucursales: {
        type: Array,
        required: true,
    },
    sucursalActiva: {
        type: Object,
        required: true,
    },
    menu: {
        type: Object,
        required: true,
    },
    filtros: {
        type: Object,
        default: () => ({}),
    },
});

const sucursalSeleccionadaSlug = ref(props.sucursalActiva?.slug || '');
const categoriaActivaId = ref(null);
const terminoBusqueda = ref(props.filtros.buscar || '');
const mostrarCarrito = ref(false);

// Estado del Carrito Reactivo
const carrito = ref([]);
const tipoPropina = ref('ninguna'); // 'ninguna', '5', '10', 'abierta'
const porcentajePropina = ref(0);
const valorPropinaLibre = ref(0);

// Cargar carrito previo de localStorage si corresponde a esta sucursal
onMounted(() => {
    const handleToggleCarrito = () => {
        mostrarCarrito.value = !mostrarCarrito.value;
    };
    window.addEventListener('toggle-carrito', handleToggleCarrito);
    onUnmounted(() => {
        window.removeEventListener('toggle-carrito', handleToggleCarrito);
    });

    const claveLocal = `sigr_carrito_${props.sucursalActiva?.id}`;
    const guardado = localStorage.getItem(claveLocal);
    if (guardado) {
        try {
            carrito.value = JSON.parse(guardado);
        } catch {
            carrito.value = [];
        }
    }

    // Escuchar actualizaciones en tiempo real del menú (Laravel Reverb / WebSockets)
    if (window.Echo && props.sucursalActiva?.id) {
        window.Echo.channel(`menu.${props.sucursalActiva.id}`)
            .listen('.MenuActualizado', () => {
                router.reload({ only: ['menu'] });
            })
            .listen('.PromocionPublicada', () => {
                router.reload({ only: ['menu'] });
            })
            .listen('.PromocionRetirada', () => {
                router.reload({ only: ['menu'] });
            });
    }
});

function guardarCarrito() {
    const claveLocal = `sigr_carrito_${props.sucursalActiva?.id}`;
    localStorage.setItem(claveLocal, JSON.stringify(carrito.value));
}

function cambiarSucursal() {
    if (sucursalSeleccionadaSlug.value) {
        router.visit(`/sucursal/${sucursalSeleccionadaSlug.value}`);
    }
}

// Iconos decorativos por categoría
function obtenerIconoCategoria(nombre) {
    const n = nombre.toLowerCase();
    if (n.includes('entrada') || n.includes('aperitivo')) return '🥟';
    if (n.includes('plato') || n.includes('fuerte') || n.includes('principal')) return '🥩';
    if (n.includes('bebida') || n.includes('jugo') || n.includes('trago')) return '🍹';
    if (n.includes('postre') || n.includes('dulce') || n.includes('infantil')) return '🍰';
    return '🍽️';
}

// Filtrado reactivo de categorías e ítems
const categoriasFiltradas = computed(() => {
    let cats = props.menu.categorias || [];

    if (categoriaActivaId.value !== null) {
        cats = cats.filter(c => c.id === categoriaActivaId.value);
    }

    if (terminoBusqueda.value.trim() !== '') {
        const query = terminoBusqueda.value.toLowerCase().trim();
        return cats.map(c => {
            return {
                ...c,
                items: c.items.filter(i =>
                    i.nombre.toLowerCase().includes(query) ||
                    (i.descripcion && i.descripcion.toLowerCase().includes(query))
                ),
            };
        }).filter(c => c.items.length > 0);
    }

    return cats;
});

// Manejo del Carrito
const totalItemsEnCarrito = computed(() => {
    return carrito.value.reduce((total, i) => total + i.cantidad, 0);
});

function obtenerCantidadEnCarrito(itemId) {
    const encontrado = carrito.value.find(i => i.id === itemId);
    return encontrado ? encontrado.cantidad : 0;
}

function agregarAlCarrito(item) {
    if (item.controla_inventario) {
        const existencia = Number(item.existencia) || 0;
        if (existencia <= 0 || item.esta_agotado) {
            alertaInventario.value = `El producto "${item.nombre}" se encuentra agotado en inventario.`;
            setTimeout(() => { alertaInventario.value = ''; }, 4500);
            return;
        }
    }

    const precio = item.precio_promocional !== null ? item.precio_promocional : item.precio;
    const precioFormateado = item.precio_promocional_formateado !== null ? item.precio_promocional_formateado : item.precio_formateado;

    carrito.value.push({
        id: item.id,
        nombre: item.nombre,
        controla_inventario: item.controla_inventario,
        existencia: item.existencia,
        precio_unitario: precio,
        precio_unitario_formateado: precioFormateado,
        base_imponible: item.base_imponible,
        valor_impuesto: item.valor_impuesto,
        cantidad: 1,
        notas: '',
    });
    guardarCarrito();
}

function incrementarCantidad(item) {
    const encontrado = carrito.value.find(i => i.id === item.id);
    if (encontrado) {
        const itemInventario = item.controla_inventario !== undefined ? item : (encontrado.controla_inventario !== undefined ? encontrado : null);
        if (itemInventario && itemInventario.controla_inventario) {
            const existencia = Number(itemInventario.existencia) || 0;
            if (encontrado.cantidad + 1 > existencia) {
                alertaInventario.value = `No hay más existencias disponibles de "${encontrado.nombre}". (Máximo disponible: ${existencia} unidades).`;
                setTimeout(() => { alertaInventario.value = ''; }, 4500);
                return;
            }
        }
        encontrado.cantidad++;
        guardarCarrito();
    } else {
        agregarAlCarrito(item);
    }
}

function decrementarCantidad(itemId) {
    const idx = carrito.value.findIndex(i => i.id === itemId);
    if (idx !== -1) {
        if (carrito.value[idx].cantidad > 1) {
            carrito.value[idx].cantidad--;
        } else {
            carrito.value.splice(idx, 1);
        }
        guardarCarrito();
    }
}

// Totales del carrito (SC-02, DE-17: Propina calculada sobre la base imponible sin impuestos)
const resumenTotales = computed(() => {
    let subtotal = 0;
    let base = 0;
    let impuesto = 0;

    for (const item of carrito.value) {
        const factor = 1 + (8 / 100);
        const itemBase = Math.round((item.precio_unitario / factor) * 100) / 100;
        const itemImpuesto = Math.round((item.precio_unitario - itemBase) * 100) / 100;

        subtotal += item.precio_unitario * item.cantidad;
        base += itemBase * item.cantidad;
        impuesto += itemImpuesto * item.cantidad;
    }

    let propina = 0;
    if (tipoPropina.value === '5') {
        propina = Math.round(base * 0.05 * 100) / 100;
    } else if (tipoPropina.value === '10') {
        propina = Math.round(base * 0.10 * 100) / 100;
    } else if (tipoPropina.value === 'abierta') {
        propina = Number(valorPropinaLibre.value) || 0;
    }

    return {
        subtotal,
        base,
        impuesto,
        propina,
        total: subtotal + propina,
    };
});

function formatearMoneda(valor) {
    const moneda = props.sucursalActiva?.moneda || 'COP';
    return '$ ' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const mostrarModalCheckout = ref(false);
const enviandoPedido = ref(false);
const checkoutError = ref(null);

const checkoutForm = ref({
    tipo: 'domicilio',
    nombre_contacto: '',
    telefono_contacto: '',
    email_contacto: '',
    direccion_entrega: '',
    notas: '',
});

function procederAlPedido() {
    mostrarModalCheckout.value = true;
}

function enviarPedido() {
    checkoutError.value = null;

    if (esPersonal.value) {
        checkoutError.value = 'Los pedidos desde la carta pública solo están permitidos para clientes o visitantes no registrados. Por favor utiliza tu panel de gestión.';
        return;
    }

    if (checkoutForm.value.tipo === 'domicilio' && !checkoutForm.value.direccion_entrega) {
        checkoutError.value = 'La dirección de entrega es obligatoria para domicilios.';
        return;
    }

    if (!checkoutForm.value.nombre_contacto || !checkoutForm.value.telefono_contacto || !checkoutForm.value.email_contacto) {
        checkoutError.value = 'Por favor completa tu nombre, teléfono y correo electrónico.';
        return;
    }

    enviandoPedido.value = true;

    const payload = {
        sucursal_id: props.sucursalActiva.id,
        tipo: checkoutForm.value.tipo,
        nombre_contacto: checkoutForm.value.nombre_contacto,
        telefono_contacto: checkoutForm.value.telefono_contacto,
        email_contacto: checkoutForm.value.email_contacto,
        direccion_entrega: checkoutForm.value.direccion_entrega,
        notas: checkoutForm.value.notas,
        propina_tipo: tipoPropina.value === '5' || tipoPropina.value === '10' ? 'porcentaje' : (tipoPropina.value === 'abierta' ? 'abierta' : 'ninguna'),
        propina_porcentaje: tipoPropina.value === '5' ? 5 : (tipoPropina.value === '10' ? 10 : null),
        propina_valor: tipoPropina.value === 'abierta' ? Number(valorPropinaLibre.value) || 0 : null,
        items: carrito.value.map(i => ({
            item_menu_id: i.id,
            cantidad: i.cantidad,
            notas: i.notas || null,
        })),
    };

    router.post('/pedidos', payload, {
        onError: (err) => {
            enviandoPedido.value = false;
            checkoutError.value = Object.values(err)[0] || 'Ocurrió un error al procesar el pedido.';
        },
        onFinish: () => {
            enviandoPedido.value = false;
        },
    });
}
</script>
