<template>
    <AdminLayout>
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 flex items-center space-x-2">
                        <span>🛒</span>
                        <span>Compras, Devoluciones y Anulaciones</span>
                    </h1>
                    <p class="text-xs text-stone-500 mt-1">
                        Recepción de insumos, devoluciones parciales/totales, anulaciones, bitácora y exportación contable (RF-COM-01..08).
                    </p>
                </div>

                <!-- Acciones Superiores -->
                <div class="flex flex-wrap items-center gap-2">
                    <div v-if="sucursales && sucursales.length > 1">
                        <select
                            v-model="sucursalSeleccionada"
                            @change="aplicarFiltros"
                            class="rounded-xl border border-stone-300 bg-white px-3 py-2 text-xs font-semibold text-stone-700 shadow-xs focus:ring-1 focus:ring-orange-500"
                        >
                            <option v-for="s in sucursales" :key="s.id" :value="s.id">
                                🏢 {{ s.nombre }}
                            </option>
                        </select>
                    </div>

                    <a
                        :href="urlExportarExcel"
                        class="px-3 py-2 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5"
                    >
                        <span>📊</span>
                        <span>Exportar Excel</span>
                    </a>

                    <a
                        :href="urlExportarPdf"
                        class="px-3 py-2 rounded-xl border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5"
                    >
                        <span>📄</span>
                        <span>Exportar PDF</span>
                    </a>

                    <button
                        type="button"
                        @click="abrirModalProveedor"
                        class="px-3 py-2 rounded-xl border border-stone-300 bg-white hover:bg-stone-50 text-stone-700 text-xs font-semibold shadow-xs transition"
                    >
                        + Nuevo Proveedor
                    </button>

                    <button
                        type="button"
                        @click="abrirModalCompra"
                        class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        + Registrar Compra
                    </button>
                </div>
            </div>

            <!-- Barra de Búsqueda y Filtros por Fecha -->
            <form
                @submit.prevent="aplicarFiltros"
                class="bg-white rounded-2xl border border-stone-200 p-4 shadow-xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end"
            >
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-stone-600 mb-1">Buscar factura, proveedor, NIT, producto o motivo</label>
                    <input
                        v-model="filtroForm.buscar"
                        type="text"
                        placeholder="Ej. FAC-001, Distribuidora, Arroz..."
                        class="w-full rounded-xl border border-stone-300 px-3 py-2 text-xs text-stone-800 focus:ring-2 focus:ring-orange-500 focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-stone-600 mb-1">Fecha Desde</label>
                    <input
                        v-model="filtroForm.fecha_desde"
                        type="date"
                        class="w-full rounded-xl border border-stone-300 px-3 py-2 text-xs text-stone-800"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-stone-600 mb-1">Fecha Hasta</label>
                    <input
                        v-model="filtroForm.fecha_hasta"
                        type="date"
                        class="w-full rounded-xl border border-stone-300 px-3 py-2 text-xs text-stone-800"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-stone-600 mb-1">Estado</label>
                    <select
                        v-model="filtroForm.estado"
                        class="w-full rounded-xl border border-stone-300 px-3 py-2 text-xs text-stone-800 bg-white"
                    >
                        <option value="">Todos los estados</option>
                        <option value="registrada">Registrada</option>
                        <option value="devuelta_parcial">Devuelta Parcial</option>
                        <option value="devuelta">Devuelta Total</option>
                        <option value="anulada">Anulada</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 px-3 py-2 rounded-xl bg-stone-900 hover:bg-stone-800 text-white text-xs font-bold transition"
                    >
                        🔍 Filtrar
                    </button>
                    <button
                        type="button"
                        @click="limpiarFiltros"
                        class="px-3 py-2 rounded-xl border border-stone-200 hover:bg-stone-100 text-stone-600 text-xs font-semibold transition"
                    >
                        Limpiar
                    </button>
                </div>
            </form>

            <!-- Pestañas de Navegación -->
            <div class="flex flex-wrap gap-2 border-b border-stone-200 pb-2">
                <button
                    type="button"
                    @click="tabActiva = 'compras'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2',
                        tabActiva === 'compras'
                            ? 'bg-orange-600 text-white shadow-xs'
                            : 'bg-white border border-stone-200 text-stone-600 hover:bg-stone-50',
                    ]"
                >
                    <span>🛒 Compras</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/15">
                        {{ compras.total ?? compras.data?.length ?? 0 }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="tabActiva = 'devoluciones'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2',
                        tabActiva === 'devoluciones'
                            ? 'bg-amber-600 text-white shadow-xs'
                            : 'bg-white border border-stone-200 text-stone-600 hover:bg-stone-50',
                    ]"
                >
                    <span>↩️ Devoluciones</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/15">
                        {{ devoluciones.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="tabActiva = 'anulaciones'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2',
                        tabActiva === 'anulaciones'
                            ? 'bg-rose-600 text-white shadow-xs'
                            : 'bg-white border border-stone-200 text-stone-600 hover:bg-stone-50',
                    ]"
                >
                    <span>🚫 Anulaciones</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/15">
                        {{ anulaciones.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="tabActiva = 'bitacora'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2',
                        tabActiva === 'bitacora'
                            ? 'bg-stone-900 text-white shadow-xs'
                            : 'bg-white border border-stone-200 text-stone-600 hover:bg-stone-50',
                    ]"
                >
                    <span>📜 Bitácora de Compras</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/15">
                        {{ bitacoraCompras.length }}
                    </span>
                </button>
            </div>

            <!-- PESTAÑA 1: TABLA DE COMPRAS CON ACORDEÓN -->
            <div v-if="tabActiva === 'compras'" class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 uppercase tracking-wider font-semibold">
                                <th class="p-3.5 w-10">Detalle</th>
                                <th class="p-3.5">Factura #</th>
                                <th class="p-3.5">Proveedor</th>
                                <th class="p-3.5">Fecha</th>
                                <th class="p-3.5">Ítems</th>
                                <th class="p-3.5">Subtotal</th>
                                <th class="p-3.5">Impuesto</th>
                                <th class="p-3.5">Total Compra</th>
                                <th class="p-3.5">Estado</th>
                                <th class="p-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <template v-for="compra in compras.data" :key="compra.id">
                                <tr
                                    class="hover:bg-stone-50/70 transition cursor-pointer"
                                    @click="toggleAcordeonCompra(compra.id)"
                                >
                                    <td class="p-3.5 text-center">
                                        <button
                                            type="button"
                                            class="w-6 h-6 rounded-lg border border-stone-200 bg-stone-50 hover:bg-orange-50 text-stone-600 font-bold text-xs inline-flex items-center justify-center transition"
                                            :title="acordeonCompra[compra.id] ? 'Ocultar detalle' : 'Ver detalle de productos y movimientos'"
                                        >
                                            {{ acordeonCompra[compra.id] ? '▾' : '▸' }}
                                        </button>
                                    </td>
                                    <td class="p-3.5 font-mono font-bold text-stone-900 select-all">
                                        {{ compra.numero_factura }}
                                    </td>
                                    <td class="p-3.5 font-medium text-stone-800">
                                        <div>{{ compra.proveedor }}</div>
                                        <span v-if="compra.proveedor_nit" class="text-[10px] text-stone-400 font-mono">NIT: {{ compra.proveedor_nit }}</span>
                                    </td>
                                    <td class="p-3.5 font-mono text-stone-600">
                                        {{ compra.fecha_compra }}
                                    </td>
                                    <td class="p-3.5 text-stone-600 font-semibold">
                                        {{ compra.items_count }} producto(s)
                                    </td>
                                    <td class="p-3.5 font-mono text-stone-600">
                                        ${{ Number(compra.subtotal || 0).toLocaleString() }}
                                    </td>
                                    <td class="p-3.5 font-mono text-stone-600">
                                        ${{ Number(compra.impuesto_total || 0).toLocaleString() }}
                                    </td>
                                    <td class="p-3.5 font-mono font-bold text-stone-900 text-sm">
                                        ${{ Number(compra.total || 0).toLocaleString() }}
                                    </td>
                                    <td class="p-3.5">
                                        <span
                                            :class="[
                                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                                badgeEstado(compra.estado)
                                            ]"
                                        >
                                            {{ compra.estado_etiqueta }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap" @click.stop>
                                        <button
                                            v-if="compra.puede_devolver"
                                            type="button"
                                            @click="abrirModalDevolver(compra)"
                                            class="px-2.5 py-1 border border-amber-300 text-amber-700 hover:bg-amber-50 rounded-lg font-semibold text-[11px] transition"
                                            title="Registrar devolución a proveedor"
                                        >
                                            Devolver
                                        </button>
                                        <button
                                            v-if="compra.puede_anular"
                                            type="button"
                                            @click="abrirModalAnular(compra)"
                                            class="px-2.5 py-1 border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg font-semibold text-[11px] transition"
                                            title="Anular compra y revertir stock"
                                        >
                                            Anular
                                        </button>
                                    </td>
                                </tr>

                                <!-- Fila Acordeón Desplegable de la Compra -->
                                <tr v-if="acordeonCompra[compra.id]" class="bg-stone-50/90 border-b border-stone-200">
                                    <td colspan="10" class="p-4 space-y-4">
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                            <!-- Detalle de Líneas de Compra -->
                                            <div class="lg:col-span-2 bg-white rounded-xl border border-stone-200 p-3 space-y-2">
                                                <div class="flex items-center justify-between border-b border-stone-100 pb-2">
                                                    <span class="font-bold text-stone-800 text-xs">📦 Productos / Insumos de la Factura #{{ compra.numero_factura }}</span>
                                                    <span class="text-[11px] text-stone-500">Registrado por: <strong>{{ compra.usuario }}</strong></span>
                                                </div>
                                                <table class="w-full text-[11px]">
                                                    <thead>
                                                        <tr class="text-stone-400 uppercase border-b border-stone-100">
                                                            <th class="py-1.5 text-left">Descripción</th>
                                                            <th class="py-1.5 text-left">Producto Vinculado</th>
                                                            <th class="py-1.5 text-right">Cantidad</th>
                                                            <th class="py-1.5 text-right">Costo Unit.</th>
                                                            <th class="py-1.5 text-right">Subtotal Línea</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-stone-100">
                                                        <tr v-for="item in compra.items" :key="item.id">
                                                            <td class="py-1.5 font-semibold text-stone-800">{{ item.descripcion }}</td>
                                                            <td class="py-1.5 text-stone-500">{{ item.producto_menu || 'Insumo general' }}</td>
                                                            <td class="py-1.5 text-right font-mono">{{ item.cantidad }}</td>
                                                            <td class="py-1.5 text-right font-mono">${{ Number(item.costo_unitario || 0).toLocaleString() }}</td>
                                                            <td class="py-1.5 text-right font-mono font-bold text-stone-900">${{ Number(item.total || 0).toLocaleString() }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Historial de Devoluciones / Anulación de esta Compra -->
                                            <div class="bg-white rounded-xl border border-stone-200 p-3 space-y-3">
                                                <h4 class="font-bold text-stone-800 text-xs border-b border-stone-100 pb-2">
                                                    🔎 Trazabilidad y Novedades
                                                </h4>

                                                <div v-if="compra.motivo_anulacion" class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-[11px] space-y-1">
                                                    <div class="font-bold">🚫 Compra Anulada</div>
                                                    <div><strong>Motivo:</strong> {{ compra.motivo_anulacion }}</div>
                                                    <div v-if="compra.anulado_por"><strong>Por:</strong> {{ compra.anulado_por }} ({{ compra.anulado_at }})</div>
                                                </div>

                                                <div v-if="compra.devoluciones && compra.devoluciones.length > 0" class="space-y-2">
                                                    <div
                                                        v-for="dev in compra.devoluciones"
                                                        :key="dev.id"
                                                        class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-[11px] space-y-1"
                                                    >
                                                        <div class="flex justify-between font-bold">
                                                            <span>↩️ Devolución {{ dev.tipo }}</span>
                                                            <span class="font-mono">-${{ Number(dev.total_devuelto || 0).toLocaleString() }}</span>
                                                        </div>
                                                        <div><strong>Fecha:</strong> {{ dev.fecha }} · <strong>Por:</strong> {{ dev.usuario }}</div>
                                                        <div><strong>Motivo:</strong> {{ dev.motivo }}</div>
                                                        <ul v-if="dev.items_devueltos && dev.items_devueltos.length" class="list-disc list-inside text-[10px] text-amber-800 pt-1">
                                                            <li v-for="(itDev, idx) in dev.items_devueltos" :key="idx">
                                                                {{ itDev.descripcion }}: {{ itDev.cantidad_devuelta }} unid. (${{ Number(itDev.subtotal_devuelto || 0).toLocaleString() }})
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <div v-if="!compra.motivo_anulacion && (!compra.devoluciones || compra.devoluciones.length === 0)" class="text-[11px] text-stone-400 italic">
                                                    Sin devoluciones ni anulaciones registradas sobre esta factura.
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="!compras.data || compras.data.length === 0">
                                <td colspan="10" class="p-8 text-center text-stone-400">
                                    No se encontraron compras con los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="compras.links && compras.links.length > 3" class="p-4 border-t border-stone-100 flex justify-center space-x-1">
                    <template v-for="(link, i) in compras.links" :key="i">
                        <a
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-medium border transition',
                                link.active ? 'bg-orange-600 text-white border-orange-600' : 'bg-white text-stone-700 border-stone-200 hover:bg-stone-50'
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border border-stone-200 text-stone-300 opacity-50"
                        />
                    </template>
                </div>
            </div>

            <!-- PESTAÑA 2: DEVOLUCIONES CON ACORDEÓN -->
            <div v-if="tabActiva === 'devoluciones'" class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-amber-50/70 border-b border-amber-200 text-amber-900 uppercase tracking-wider font-semibold">
                                <th class="p-3.5 w-10">Detalle</th>
                                <th class="p-3.5">Factura Compra</th>
                                <th class="p-3.5">Proveedor</th>
                                <th class="p-3.5">Fecha Devolución</th>
                                <th class="p-3.5">Modalidad</th>
                                <th class="p-3.5">Motivo</th>
                                <th class="p-3.5">Total Devuelto</th>
                                <th class="p-3.5">Registrado Por</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <template v-for="dev in devoluciones" :key="dev.id">
                                <tr
                                    class="hover:bg-amber-50/30 transition cursor-pointer"
                                    @click="acordeonDevolucion[dev.id] = !acordeonDevolucion[dev.id]"
                                >
                                    <td class="p-3.5 text-center">
                                        <button
                                            type="button"
                                            class="w-6 h-6 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 font-bold text-xs inline-flex items-center justify-center"
                                        >
                                            {{ acordeonDevolucion[dev.id] ? '▾' : '▸' }}
                                        </button>
                                    </td>
                                    <td class="p-3.5 font-mono font-bold text-stone-900">#{{ dev.numero_factura }}</td>
                                    <td class="p-3.5 font-medium text-stone-800">{{ dev.proveedor }}</td>
                                    <td class="p-3.5 font-mono text-stone-600">{{ dev.fecha_devolucion }}</td>
                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-800 border border-amber-200">
                                            {{ dev.tipo }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-stone-700">{{ dev.motivo }}</td>
                                    <td class="p-3.5 font-mono font-bold text-amber-800">-${{ Number(dev.total_devuelto || 0).toLocaleString() }}</td>
                                    <td class="p-3.5 text-stone-600">{{ dev.usuario }}</td>
                                </tr>

                                <tr v-if="acordeonDevolucion[dev.id]" class="bg-amber-50/40 border-b border-amber-100">
                                    <td colspan="8" class="p-4">
                                        <div class="bg-white rounded-xl border border-amber-200 p-3 space-y-2">
                                            <div class="font-bold text-amber-900 text-xs">
                                                📦 Productos devueltos en la Factura #{{ dev.numero_factura }}
                                            </div>
                                            <table class="w-full text-[11px]">
                                                <thead>
                                                    <tr class="text-stone-400 uppercase border-b border-stone-100">
                                                        <th class="py-1 text-left">Producto / Insumo</th>
                                                        <th class="py-1 text-right">Cantidad Devuelta</th>
                                                        <th class="py-1 text-right">Costo Unitario</th>
                                                        <th class="py-1 text-right">Subtotal Devuelto</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-stone-100">
                                                    <tr v-for="(it, idx) in dev.items_devueltos" :key="idx">
                                                        <td class="py-1.5 font-semibold text-stone-800">{{ it.descripcion }}</td>
                                                        <td class="py-1.5 text-right font-mono">{{ it.cantidad_devuelta }}</td>
                                                        <td class="py-1.5 text-right font-mono">${{ Number(it.costo_unitario || 0).toLocaleString() }}</td>
                                                        <td class="py-1.5 text-right font-mono font-bold text-amber-800">-${{ Number(it.subtotal_devuelto || 0).toLocaleString() }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="devoluciones.length === 0">
                                <td colspan="8" class="p-8 text-center text-stone-400">
                                    No hay devoluciones registradas para los filtros actuales.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PESTAÑA 3: ANULACIONES CON ACORDEÓN -->
            <div v-if="tabActiva === 'anulaciones'" class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-rose-50/70 border-b border-rose-200 text-rose-900 uppercase tracking-wider font-semibold">
                                <th class="p-3.5 w-10">Detalle</th>
                                <th class="p-3.5">Factura #</th>
                                <th class="p-3.5">Proveedor</th>
                                <th class="p-3.5">Fecha Compra</th>
                                <th class="p-3.5">Fecha Anulación</th>
                                <th class="p-3.5">Motivo de Anulación</th>
                                <th class="p-3.5">Total Anulado</th>
                                <th class="p-3.5">Anulado Por</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <template v-for="anu in anulaciones" :key="anu.id">
                                <tr
                                    class="hover:bg-rose-50/30 transition cursor-pointer"
                                    @click="acordeonAnulacion[anu.id] = !acordeonAnulacion[anu.id]"
                                >
                                    <td class="p-3.5 text-center">
                                        <button
                                            type="button"
                                            class="w-6 h-6 rounded-lg border border-rose-200 bg-rose-50 text-rose-800 font-bold text-xs inline-flex items-center justify-center"
                                        >
                                            {{ acordeonAnulacion[anu.id] ? '▾' : '▸' }}
                                        </button>
                                    </td>
                                    <td class="p-3.5 font-mono font-bold text-stone-900">#{{ anu.numero_factura }}</td>
                                    <td class="p-3.5 font-medium text-stone-800">{{ anu.proveedor }}</td>
                                    <td class="p-3.5 font-mono text-stone-600">{{ anu.fecha_compra }}</td>
                                    <td class="p-3.5 font-mono text-stone-600">{{ anu.anulado_at || '—' }}</td>
                                    <td class="p-3.5 text-rose-800 font-medium">{{ anu.motivo_anulacion || '—' }}</td>
                                    <td class="p-3.5 font-mono font-bold text-rose-700">${{ Number(anu.total || 0).toLocaleString() }}</td>
                                    <td class="p-3.5 text-stone-600">{{ anu.anulado_por || anu.usuario }}</td>
                                </tr>

                                <tr v-if="acordeonAnulacion[anu.id]" class="bg-rose-50/30 border-b border-rose-100">
                                    <td colspan="8" class="p-4">
                                        <div class="bg-white rounded-xl border border-rose-200 p-3 space-y-2">
                                            <div class="font-bold text-rose-900 text-xs">
                                                🚫 Líneas revertidas de la Factura #{{ anu.numero_factura }}
                                            </div>
                                            <table class="w-full text-[11px]">
                                                <thead>
                                                    <tr class="text-stone-400 uppercase border-b border-stone-100">
                                                        <th class="py-1 text-left">Descripción</th>
                                                        <th class="py-1 text-right">Cantidad</th>
                                                        <th class="py-1 text-right">Costo Unitario</th>
                                                        <th class="py-1 text-right">Total Línea</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-stone-100">
                                                    <tr v-for="item in anu.items" :key="item.id">
                                                        <td class="py-1.5 font-semibold text-stone-800">{{ item.descripcion }}</td>
                                                        <td class="py-1.5 text-right font-mono">{{ item.cantidad }}</td>
                                                        <td class="py-1.5 text-right font-mono">${{ Number(item.costo_unitario || 0).toLocaleString() }}</td>
                                                        <td class="py-1.5 text-right font-mono font-bold text-rose-700">${{ Number(item.total || 0).toLocaleString() }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="anulaciones.length === 0">
                                <td colspan="8" class="p-8 text-center text-stone-400">
                                    No hay compras anuladas para los filtros seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PESTAÑA 4: BITÁCORA DE COMPRAS CON ACORDEÓN -->
            <div v-if="tabActiva === 'bitacora'" class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-stone-100 border-b border-stone-200 text-stone-600 uppercase tracking-wider font-semibold">
                                <th class="p-3.5 w-10">Detalle</th>
                                <th class="p-3.5">Fecha y Hora</th>
                                <th class="p-3.5">Evento</th>
                                <th class="p-3.5">Descripción de Auditoría</th>
                                <th class="p-3.5">Usuario Responsable</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <template v-for="bit in bitacoraCompras" :key="bit.id">
                                <tr
                                    class="hover:bg-stone-50 transition cursor-pointer"
                                    @click="acordeonBitacora[bit.id] = !acordeonBitacora[bit.id]"
                                >
                                    <td class="p-3.5 text-center">
                                        <button
                                            type="button"
                                            class="w-6 h-6 rounded-lg border border-stone-200 bg-stone-50 text-stone-700 font-bold text-xs inline-flex items-center justify-center"
                                        >
                                            {{ acordeonBitacora[bit.id] ? '▾' : '▸' }}
                                        </button>
                                    </td>
                                    <td class="p-3.5 font-mono text-stone-600 whitespace-nowrap">{{ bit.fecha }}</td>
                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-stone-200 text-stone-800">
                                            {{ bit.evento }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 font-medium text-stone-800">{{ bit.descripcion }}</td>
                                    <td class="p-3.5 text-stone-600 font-semibold">{{ bit.usuario }}</td>
                                </tr>

                                <tr v-if="acordeonBitacora[bit.id]" class="bg-stone-50 border-b border-stone-200">
                                    <td colspan="5" class="p-4">
                                        <pre class="bg-stone-900 text-stone-100 rounded-xl p-3 text-[11px] font-mono overflow-x-auto">{{ JSON.stringify(bit.propiedades, null, 2) }}</pre>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="bitacoraCompras.length === 0">
                                <td colspan="5" class="p-8 text-center text-stone-400">
                                    No se encontraron registros en la bitácora de compras.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Registrar Compra (RF-COM-02) -->
        <div v-if="modalCompraAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl border border-stone-200 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Registrar Compra a Proveedor
                    </h3>
                    <button @click="modalCompraAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <form @submit.prevent="ejecutarRegistroCompra" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="proveedor_id" class="block font-semibold text-stone-700 mb-1">Proveedor *</label>
                            <select
                                id="proveedor_id"
                                v-model="formCompra.proveedor_id"
                                class="w-full rounded-xl border border-stone-300 p-2 text-xs text-stone-800"
                                required
                            >
                                <option :value="null">Seleccionar proveedor</option>
                                <option v-for="p in proveedores" :key="p.id" :value="p.id">
                                    {{ p.razon_social }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="num_fac" class="block font-semibold text-stone-700 mb-1">Nº Factura Proveedor *</label>
                            <input
                                id="num_fac"
                                type="text"
                                v-model="formCompra.numero_factura"
                                placeholder="FAC-12345"
                                class="w-full rounded-xl border border-stone-300 p-2 text-xs text-stone-800"
                                required
                            />
                        </div>

                        <div>
                            <label for="fecha_compra" class="block font-semibold text-stone-700 mb-1">Fecha de Compra *</label>
                            <input
                                id="fecha_compra"
                                type="date"
                                v-model="formCompra.fecha_compra"
                                class="w-full rounded-xl border border-stone-300 p-2 text-xs text-stone-800"
                                required
                            />
                        </div>
                    </div>

                    <!-- Configuración de Impuestos -->
                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 space-y-2">
                        <span class="font-semibold text-stone-700 block">Impuesto de Compra:</span>
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center space-x-1.5 cursor-pointer">
                                <input type="radio" :value="0" v-model.number="formCompra.impuesto_porcentaje" class="text-orange-600" />
                                <span>Sin impuesto (0%)</span>
                            </label>
                            <label class="flex items-center space-x-1.5 cursor-pointer">
                                <input type="radio" :value="19" v-model.number="formCompra.impuesto_porcentaje" class="text-orange-600" />
                                <span>IVA General (19%)</span>
                            </label>
                            <label class="flex items-center space-x-1.5 cursor-pointer">
                                <input type="radio" :value="5" v-model.number="formCompra.impuesto_porcentaje" class="text-orange-600" />
                                <span>IVA Reducido (5%)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Tabla de Líneas de Ítems -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-stone-800">Líneas de la Compra</span>
                            <button
                                type="button"
                                @click="agregarLinea"
                                class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg font-semibold text-[11px]"
                            >
                                + Agregar Producto
                            </button>
                        </div>

                        <div v-for="(linea, idx) in formCompra.items" :key="idx" class="p-3 border rounded-xl grid grid-cols-1 sm:grid-cols-5 gap-2 items-center bg-stone-50/50">
                            <div class="sm:col-span-2">
                                <label class="text-[10px] text-stone-500 block mb-0.5">Vincular a producto de menú</label>
                                <select
                                    v-model="linea.item_menu_id"
                                    @change="onItemSeleccionado(linea)"
                                    class="w-full rounded-lg border border-stone-300 p-1.5 text-xs bg-white"
                                >
                                    <option :value="null">Insumo general / Sin vinculación</option>
                                    <option v-for="prod in productos" :key="prod.id" :value="prod.id">
                                        {{ prod.nombre }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-[10px] text-stone-500 block mb-0.5">Cantidad</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    v-model.number="linea.cantidad"
                                    class="w-full rounded-lg border border-stone-300 p-1.5 text-xs bg-white font-mono"
                                    required
                                />
                            </div>

                            <div>
                                <label class="text-[10px] text-stone-500 block mb-0.5">Costo Unitario ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model.number="linea.costo_unitario"
                                    class="w-full rounded-lg border border-stone-300 p-1.5 text-xs bg-white font-mono"
                                    required
                                />
                            </div>

                            <div class="flex items-center space-x-1 pt-3 sm:pt-0">
                                <span class="font-mono font-bold text-stone-800 text-xs flex-1 text-right">
                                    ${{ (linea.cantidad * linea.costo_unitario).toLocaleString() }}
                                </span>
                                <button
                                    v-if="formCompra.items.length > 1"
                                    type="button"
                                    @click="eliminarLinea(idx)"
                                    class="p-1 text-rose-500 hover:text-rose-700"
                                    title="Quitar línea"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Total Estimado -->
                    <div class="p-3 bg-stone-100 rounded-xl text-right font-mono text-xs">
                        <span>Total de la Compra: </span>
                        <strong class="text-sm text-stone-900">${{ calcularTotalCompra().toLocaleString() }}</strong>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalCompraAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50 font-medium">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formCompra.processing" class="px-5 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold transition">
                            {{ formCompra.processing ? 'Registrando...' : 'Guardar Compra' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Anular Compra (RF-COM-06) -->
        <div v-if="modalAnularAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">
                        Anular Factura #{{ compraSeleccionada?.numero_factura }}
                    </h3>
                    <button @click="modalAnularAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarAnulacion" class="space-y-4 text-xs">
                    <p class="text-stone-600">
                        Esta operación descontará automáticamente del kárdex las existencias previamente sumadas.
                    </p>
                    <div>
                        <label for="motivo_anular" class="block font-semibold text-stone-700 mb-1">Motivo de Anulación *</label>
                        <textarea
                            id="motivo_anular"
                            v-model="formAnular.motivo"
                            rows="3"
                            placeholder="Ej. Factura duplicada, error en precios..."
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800"
                            required
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalAnularAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formAnular.processing" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold transition">
                            {{ formAnular.processing ? 'Anulando...' : 'Confirmar Anulación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Devolver Compra (RF-COM-07, Item 4) -->
        <div v-if="modalDevolverAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <div>
                        <h3 class="font-black text-base text-stone-900">
                            Devolución Factura #{{ compraSeleccionada?.numero_factura }}
                        </h3>
                        <p class="text-[11px] text-stone-500">Proveedor: {{ compraSeleccionada?.proveedor }}</p>
                    </div>
                    <button @click="modalDevolverAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarDevolucion" class="space-y-4 text-xs">
                    <!-- Selector de Tipo de Devolución -->
                    <div class="space-y-1">
                        <label class="block font-bold text-stone-700">Modalidad de Devolución:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="tipoDevolucion = 'total'"
                                :class="[
                                    'py-2 px-3 rounded-xl border font-bold text-xs transition text-center',
                                    tipoDevolucion === 'total'
                                        ? 'bg-amber-600 text-white border-amber-600 shadow-xs'
                                        : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100'
                                ]"
                            >
                                📦 Devolver Orden Completa
                            </button>
                            <button
                                type="button"
                                @click="tipoDevolucion = 'parcial'"
                                :class="[
                                    'py-2 px-3 rounded-xl border font-bold text-xs transition text-center',
                                    tipoDevolucion === 'parcial'
                                        ? 'bg-amber-600 text-white border-amber-600 shadow-xs'
                                        : 'bg-stone-50 text-stone-700 border-stone-200 hover:bg-stone-100'
                                ]"
                            >
                                🔍 Devolver Productos Específicos
                            </button>
                        </div>
                    </div>

                    <!-- Lista de productos si es parcial -->
                    <div v-if="tipoDevolucion === 'parcial'" class="p-3 bg-stone-50 rounded-2xl border border-stone-200 space-y-2">
                        <label class="block font-bold text-stone-800">Selecciona los productos y unidades a devolver:</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            <div
                                v-for="item in itemsParaDevolver"
                                :key="item.compra_item_id"
                                class="bg-white p-2.5 rounded-xl border border-stone-200 flex items-center justify-between gap-3"
                            >
                                <div class="flex-1 min-w-0">
                                    <span class="font-bold text-stone-900 block truncate">{{ item.descripcion }}</span>
                                    <span class="text-[10px] text-stone-500">Comprado: {{ item.cantidad_original }} unid.</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-[11px] text-stone-600 font-medium">Devolver:</label>
                                    <input
                                        type="number"
                                        v-model.number="item.cantidad"
                                        min="0"
                                        :max="item.cantidad_original"
                                        step="0.1"
                                        class="w-20 rounded-lg border border-stone-300 p-1 text-xs font-mono font-bold text-right"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="motivo_devolver" class="block font-semibold text-stone-700 mb-1">Motivo de la Devolución *</label>
                        <textarea
                            id="motivo_devolver"
                            v-model="formDevolver.motivo"
                            rows="2"
                            placeholder="Ej. Producto en mal estado, vencimiento próximo o error en remisión..."
                            class="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800"
                            required
                        ></textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalDevolverAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formDevolver.processing" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold transition shadow-xs">
                            {{ formDevolver.processing ? 'Devolviendo...' : 'Confirmar Devolución' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Nuevo Proveedor -->
        <div v-if="modalProveedorAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="font-bold text-base text-stone-900">Registrar Proveedor</h3>
                    <button @click="modalProveedorAbierto = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>
                <form @submit.prevent="ejecutarRegistroProveedor" class="space-y-3 text-xs">
                    <div>
                        <label for="razon_social" class="block font-semibold text-stone-700 mb-1">Razón Social *</label>
                        <input id="razon_social" type="text" v-model="formProveedor.razon_social" class="w-full rounded-xl border border-stone-300 p-2 text-xs" required />
                    </div>
                    <div>
                        <label for="nit_prov" class="block font-semibold text-stone-700 mb-1">NIT / RUT</label>
                        <input id="nit_prov" type="text" v-model="formProveedor.nit" placeholder="900.123.456-7" class="w-full rounded-xl border border-stone-300 p-2 text-xs" />
                    </div>
                    <div>
                        <label for="tel_prov" class="block font-semibold text-stone-700 mb-1">Teléfono</label>
                        <input id="tel_prov" type="text" v-model="formProveedor.telefono" class="w-full rounded-xl border border-stone-300 p-2 text-xs" />
                    </div>
                    <div>
                        <label for="email_prov" class="block font-semibold text-stone-700 mb-1">Correo Electrónico</label>
                        <input id="email_prov" type="email" v-model="formProveedor.email" class="w-full rounded-xl border border-stone-300 p-2 text-xs" />
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-stone-100">
                        <button type="button" @click="modalProveedorAbierto = false" class="px-4 py-2 rounded-xl border border-stone-200 text-stone-600 hover:bg-stone-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formProveedor.processing" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold transition">
                            Guardar Proveedor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    compras: { type: Object, required: true },
    devoluciones: { type: Array, default: () => [] },
    anulaciones: { type: Array, default: () => [] },
    bitacoraCompras: { type: Array, default: () => [] },
    proveedores: { type: Array, default: () => [] },
    productos: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
    sucursales: { type: Array, default: () => [] },
    sucursal_id: { type: Number, required: true },
});

const sucursalSeleccionada = ref(props.sucursal_id);
const tabActiva = ref('compras');

// Estado de acordeones desplegables
const acordeonCompra = reactive({});
const acordeonDevolucion = reactive({});
const acordeonAnulacion = reactive({});
const acordeonBitacora = reactive({});

const toggleAcordeonCompra = (id) => {
    acordeonCompra[id] = !acordeonCompra[id];
};

// Filtros
const filtroForm = reactive({
    buscar: props.filtros?.buscar || '',
    fecha_desde: props.filtros?.fecha_desde || '',
    fecha_hasta: props.filtros?.fecha_hasta || '',
    estado: props.filtros?.estado || '',
});

const queryParamsString = computed(() => {
    const params = new URLSearchParams();
    params.set('sucursal_id', String(sucursalSeleccionada.value));
    if (filtroForm.buscar) params.set('buscar', filtroForm.buscar);
    if (filtroForm.fecha_desde) params.set('fecha_desde', filtroForm.fecha_desde);
    if (filtroForm.fecha_hasta) params.set('fecha_hasta', filtroForm.fecha_hasta);
    if (filtroForm.estado) params.set('estado', filtroForm.estado);
    return params.toString();
});

const urlExportarExcel = computed(() => `/admin/compras/exportar/excel?${queryParamsString.value}`);
const urlExportarPdf = computed(() => `/admin/compras/exportar/pdf?${queryParamsString.value}`);

const aplicarFiltros = () => {
    router.get('/admin/compras', {
        sucursal_id: sucursalSeleccionada.value,
        buscar: filtroForm.buscar || undefined,
        fecha_desde: filtroForm.fecha_desde || undefined,
        fecha_hasta: filtroForm.fecha_hasta || undefined,
        estado: filtroForm.estado || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const limpiarFiltros = () => {
    filtroForm.buscar = '';
    filtroForm.fecha_desde = '';
    filtroForm.fecha_hasta = '';
    filtroForm.estado = '';
    aplicarFiltros();
};

// Modal Compra
const modalCompraAbierto = ref(false);
const formCompra = useForm({
    sucursal_id: props.sucursal_id,
    proveedor_id: null,
    numero_factura: '',
    fecha_compra: new Date().toISOString().split('T')[0],
    incluye_impuesto: false,
    impuesto_porcentaje: 0,
    items: [
        { item_menu_id: null, descripcion: 'Insumos varios', cantidad: 10, costo_unitario: 5000, factor_conversion: 1.0 },
    ],
});

const abrirModalCompra = () => {
    formCompra.reset();
    formCompra.sucursal_id = sucursalSeleccionada.value;
    formCompra.fecha_compra = new Date().toISOString().split('T')[0];
    modalCompraAbierto.value = true;
};

const agregarLinea = () => {
    formCompra.items.push({
        item_menu_id: null,
        descripcion: 'Insumo nuevo',
        cantidad: 1,
        costo_unitario: 0,
        factor_conversion: 1.0,
    });
};

const eliminarLinea = (idx) => {
    formCompra.items.splice(idx, 1);
};

const onItemSeleccionado = (linea) => {
    if (linea.item_menu_id) {
        const prod = props.productos.find(p => p.id === linea.item_menu_id);
        if (prod) {
            linea.descripcion = prod.nombre;
        }
    }
};

const calcularTotalCompra = () => {
    const subtotal = formCompra.items.reduce((acc, it) => acc + (it.cantidad * it.costo_unitario), 0);
    const imp = subtotal * (formCompra.impuesto_porcentaje / 100);
    return subtotal + imp;
};

const ejecutarRegistroCompra = () => {
    formCompra.impuesto_porcentaje = Math.max(0, Number(formCompra.impuesto_porcentaje) || 0);
    formCompra.items = formCompra.items.map(it => ({
        ...it,
        cantidad: Math.max(0.01, Number(it.cantidad) || 0.01),
        costo_unitario: Math.max(0, Number(it.costo_unitario) || 0),
    }));
    formCompra.post('/admin/compras', {
        preserveScroll: true,
        onSuccess: () => { modalCompraAbierto.value = false; },
    });
};

// Modal Anular
const modalAnularAbierto = ref(false);
const compraSeleccionada = ref(null);
const formAnular = useForm({ motivo: '' });

const abrirModalAnular = (compra) => {
    compraSeleccionada.value = compra;
    formAnular.reset();
    modalAnularAbierto.value = true;
};

const ejecutarAnulacion = () => {
    if (!compraSeleccionada.value) return;
    formAnular.post(`/admin/compras/${compraSeleccionada.value.id}/anular`, {
        preserveScroll: true,
        onSuccess: () => { modalAnularAbierto.value = false; },
    });
};

// Modal Devolver
const modalDevolverAbierto = ref(false);
const tipoDevolucion = ref('total');
const itemsParaDevolver = ref([]);
const formDevolver = useForm({
    motivo: '',
    tipo: 'total',
    items: [],
});

const abrirModalDevolver = (compra) => {
    compraSeleccionada.value = compra;
    tipoDevolucion.value = 'total';
    itemsParaDevolver.value = (compra.items || []).map(it => ({
        compra_item_id: it.id,
        descripcion: it.descripcion,
        cantidad_original: it.cantidad,
        cantidad: it.cantidad,
    }));
    formDevolver.reset();
    formDevolver.tipo = 'total';
    formDevolver.items = [];
    modalDevolverAbierto.value = true;
};

const ejecutarDevolucion = () => {
    if (!compraSeleccionada.value) return;

    formDevolver.tipo = tipoDevolucion.value;
    formDevolver.items = tipoDevolucion.value === 'parcial'
        ? itemsParaDevolver.value.filter(it => it.cantidad > 0)
        : [];

    formDevolver.post(`/admin/compras/${compraSeleccionada.value.id}/devolver`, {
        preserveScroll: true,
        onSuccess: () => { modalDevolverAbierto.value = false; },
    });
};

// Modal Proveedor
const modalProveedorAbierto = ref(false);
const formProveedor = useForm({
    sucursal_id: props.sucursal_id,
    razon_social: '',
    nit: '',
    telefono: '',
    email: '',
});

const abrirModalProveedor = () => {
    formProveedor.reset();
    formProveedor.sucursal_id = sucursalSeleccionada.value;
    modalProveedorAbierto.value = true;
};

const ejecutarRegistroProveedor = () => {
    formProveedor.post('/admin/compras/proveedores', {
        preserveScroll: true,
        onSuccess: () => { modalProveedorAbierto.value = false; },
    });
};

const badgeEstado = (estado) => {
    return {
        'registrada': 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        'anulada': 'bg-rose-100 text-rose-800 border border-rose-200',
        'devuelta': 'bg-amber-100 text-amber-800 border border-amber-200',
        'devuelta_parcial': 'bg-amber-50 text-amber-700 border border-amber-200',
    }[estado] || 'bg-stone-100 text-stone-700';
};
</script>
