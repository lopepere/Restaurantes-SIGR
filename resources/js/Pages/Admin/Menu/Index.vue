<template>
    <AdminLayout :titulo="'Gestión del Menú y Productos'">
        <div class="space-y-6">
            <!-- Barra Superior y Acciones Principales -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-stone-200 shadow-xs">
                <div>
                    <h1 class="text-xl font-bold text-stone-900 tracking-tight">Catálogo de Menú</h1>
                    <p class="text-xs text-stone-500 mt-0.5">Administra categorías, platillos, bebidas, combos y disponibilidad en tiempo real.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Botón Importar CSV/Excel y Descargar Plantilla (Item 1) -->
                    <button
                        type="button"
                        @click="modalImportarVisible = true"
                        class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold rounded-xl border border-emerald-200 transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>📥</span>
                        <span>Importar CSV / Excel y Plantilla</span>
                    </button>

                    <!-- Botón Copiar Menú (CA-25, RF-MEN-09) -->
                    <button
                        v-if="otrasSucursales.length > 0"
                        @click="abrirModalCopiar"
                        class="px-3.5 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-semibold rounded-xl border border-stone-300 transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>📋</span>
                        <span>Copiar Menú de Otra Sede</span>
                    </button>

                    <button
                        @click="abrirModalCategoria()"
                        class="px-3.5 py-2 bg-stone-800 hover:bg-stone-900 text-white text-xs font-semibold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>+</span>
                        <span>Nueva Categoría</span>
                    </button>

                    <button
                        @click="abrirModalItem()"
                        class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>+</span>
                        <span>Nuevo Producto / Combo</span>
                    </button>
                </div>
            </div>

            <!-- Selector de Pestañas: Categorías vs Productos -->
            <div class="flex items-center border-b border-stone-200 gap-6">
                <button
                    type="button"
                    @click="tabActiva = 'items'"
                    :class="[
                        'pb-3 text-sm font-bold border-b-2 transition flex items-center gap-2 cursor-pointer',
                        tabActiva === 'items' ? 'border-orange-600 text-orange-600' : 'border-transparent text-stone-500 hover:text-stone-800'
                    ]"
                >
                    <span>🍲 Productos y Combos</span>
                    <span class="bg-stone-100 text-stone-700 text-xs px-2 py-0.5 rounded-full">{{ items.total }}</span>
                </button>
                <button
                    type="button"
                    @click="tabActiva = 'categorias'"
                    :class="[
                        'pb-3 text-sm font-bold border-b-2 transition flex items-center gap-2 cursor-pointer',
                        tabActiva === 'categorias' ? 'border-orange-600 text-orange-600' : 'border-transparent text-stone-500 hover:text-stone-800'
                    ]"
                >
                    <span>📂 Categorías</span>
                    <span class="bg-stone-100 text-stone-700 text-xs px-2 py-0.5 rounded-full">{{ categorias.length }}</span>
                </button>
            </div>

            <!-- Pestaña 1: Productos y Combos -->
            <div v-if="tabActiva === 'items'" class="space-y-4">
                <!-- Filtros de búsqueda -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-xl border border-stone-200">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <select
                            v-model="filtroCategoriaId"
                            @change="aplicarFiltros"
                            class="text-xs bg-stone-50 border border-stone-300 rounded-xl p-2 text-stone-800 focus:ring-orange-500"
                        >
                            <option :value="''">Todas las categorías</option>
                            <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                        </select>
                    </div>

                    <div class="w-full sm:w-72">
                        <input
                            type="text"
                            v-model="filtroBuscar"
                            @keyup.enter="aplicarFiltros"
                            placeholder="Buscar por nombre o descripción..."
                            class="w-full text-xs bg-stone-50 border border-stone-300 rounded-xl p-2 text-stone-800"
                        />
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 font-semibold uppercase tracking-wider">
                                <th class="p-3.5 pl-5">Producto / Combo</th>
                                <th class="p-3.5">Categoría</th>
                                <th class="p-3.5">Precio</th>
                                <th class="p-3.5">Impuesto</th>
                                <th class="p-3.5">Inventario</th>
                                <th class="p-3.5">Disponible</th>
                                <th class="p-3.5 pr-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-if="items.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-stone-400">
                                    No hay productos registrados en esta sede o filtro.
                                </td>
                            </tr>
                            <tr v-for="item in items.data" :key="item.id" class="hover:bg-stone-50/70 transition">
                                <td class="p-3.5 pl-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            v-if="item.imagen"
                                            class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl overflow-hidden border border-stone-200 bg-stone-100 shrink-0"
                                        >
                                            <img
                                                :src="item.imagen"
                                                :alt="item.nombre"
                                                class="w-full h-full object-cover"
                                                loading="lazy"
                                            />
                                        </div>
                                        <span v-if="item.es_combo" class="bg-purple-100 text-purple-800 text-[10px] font-bold px-2 py-0.5 rounded-md uppercase shrink-0">
                                            Combo
                                        </span>
                                        <div class="min-w-0">
                                            <span class="font-bold text-stone-900 block truncate">{{ item.nombre }}</span>
                                            <span class="text-[11px] text-stone-400 line-clamp-1">{{ item.descripcion }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3.5 text-stone-600 font-medium">
                                    {{ item.categoria ? item.categoria.nombre : '—' }}
                                </td>
                                <td class="p-3.5 font-bold text-stone-900">
                                    $ {{ Number(item.precio).toLocaleString('es-CO', { minimumFractionDigits: 2 }) }}
                                </td>
                                <td class="p-3.5 text-stone-600">
                                    {{ item.impuesto_tipo }} ({{ item.impuesto_porcentaje }}%)
                                </td>
                                <td class="p-3.5">
                                    <span v-if="item.controla_inventario" :class="[
                                        'font-bold',
                                        Number(item.existencia) <= Number(item.existencia_minima) ? 'text-red-600' : 'text-stone-700'
                                    ]">
                                        {{ Number(item.existencia) }} disp.
                                    </span>
                                    <span v-else class="text-stone-400 italic">No controla</span>
                                </td>
                                <td class="p-3.5">
                                    <button
                                        type="button"
                                        @click="alternarDisponibilidad(item)"
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition',
                                            item.disponible ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-stone-200 text-stone-600 hover:bg-stone-300'
                                        ]"
                                    >
                                        {{ item.disponible ? '✓ Disponible' : '✕ No disponible' }}
                                    </button>
                                </td>
                                <td class="p-3.5 pr-5 text-right space-x-2">
                                    <button
                                        @click="abrirModalItem(item)"
                                        class="text-orange-600 hover:text-orange-800 font-semibold"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        @click="eliminarItem(item)"
                                        class="text-rose-600 hover:text-rose-800 font-semibold"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pestaña 2: Categorías -->
            <div v-if="tabActiva === 'categorias'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-200 text-stone-500 font-semibold uppercase tracking-wider">
                                <th class="p-3.5 pl-5">Orden</th>
                                <th class="p-3.5">Nombre</th>
                                <th class="p-3.5">Descripción</th>
                                <th class="p-3.5">Productos</th>
                                <th class="p-3.5">Estado</th>
                                <th class="p-3.5 pr-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="cat in categorias" :key="cat.id" class="hover:bg-stone-50/70 transition">
                                <td class="p-3.5 pl-5 font-bold text-stone-400">{{ cat.orden }}</td>
                                <td class="p-3.5 font-bold text-stone-900">
                                    <span>{{ cat.nombre }}</span>
                                    <span
                                        v-if="cat.es_sistema || cat.slug === 'sin-categorizar'"
                                        class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-200 text-stone-700"
                                    >
                                        🔒 Por defecto del sistema
                                    </span>
                                </td>
                                <td class="p-3.5 text-stone-500">{{ cat.descripcion || '—' }}</td>
                                <td class="p-3.5 font-semibold text-stone-700">{{ cat.items_count || 0 }} productos</td>
                                <td class="p-3.5">
                                    <span :class="[
                                        'px-2.5 py-0.5 rounded-full text-[10px] font-bold',
                                        cat.activa ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'
                                    ]">
                                        {{ cat.activa ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="p-3.5 pr-5 text-right space-x-2">
                                    <template v-if="cat.es_sistema || cat.slug === 'sin-categorizar'">
                                        <span class="text-[11px] text-stone-400 italic font-medium">
                                            🔒 No editable ni borrable
                                        </span>
                                    </template>
                                    <template v-else>
                                        <button
                                            @click="abrirModalCategoria(cat)"
                                            class="text-orange-600 hover:text-orange-800 font-semibold"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            @click="eliminarCategoria(cat)"
                                            class="text-rose-600 hover:text-rose-800 font-semibold"
                                        >
                                            Eliminar
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Categoría -->
        <div v-if="modalCategoriaVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-stone-200">
                <h3 class="text-lg font-bold text-stone-900 mb-4">
                    {{ categoriaEnEdicion ? 'Editar Categoría' : 'Nueva Categoría' }}
                </h3>
                <form @submit.prevent="guardarCategoria" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Nombre</label>
                        <input type="text" v-model="formCategoria.nombre" required class="w-full border rounded-xl p-2.5 text-stone-900" />
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Descripción</label>
                        <textarea v-model="formCategoria.descripcion" rows="2" class="w-full border rounded-xl p-2.5 text-stone-900"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Orden</label>
                            <input type="number" v-model.number="formCategoria.orden" min="0" class="w-full border rounded-xl p-2.5 text-stone-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Estado</label>
                            <select v-model="formCategoria.activa" class="w-full border rounded-xl p-2.5 text-stone-900">
                                <option :value="true">Activa</option>
                                <option :value="false">Inactiva</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="modalCategoriaVisible = false" class="px-4 py-2 border rounded-xl text-stone-600">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white font-bold rounded-xl">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Producto / Combo -->
        <div v-if="modalItemVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/50 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-stone-200 my-8">
                <h3 class="text-lg font-bold text-stone-900 mb-4">
                    {{ itemEnEdicion ? 'Editar Producto / Combo' : 'Nuevo Producto / Combo' }}
                </h3>
                <form @submit.prevent="guardarItem" class="space-y-3.5 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block font-bold text-stone-700 mb-1">Nombre</label>
                            <input type="text" v-model="formItem.nombre" required class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Categoría</label>
                            <select v-model="formItem.categoria_id" required class="w-full border rounded-xl p-2 text-stone-900">
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Precio ($ COP)</label>
                            <input type="number" step="100" min="0" v-model.number="formItem.precio" required class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Tipo Impuesto</label>
                            <select v-model="formItem.impuesto_tipo" class="w-full border rounded-xl p-2 text-stone-900">
                                <option v-for="tipo in tiposImpuesto" :key="tipo" :value="tipo">{{ tipo }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">% Impuesto</label>
                            <input type="number" step="0.5" min="0" max="100" v-model.number="formItem.impuesto_porcentaje" class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                    </div>

                    <!-- Selector rápido de Catálogo Dinámico de Impuestos (Item 1) -->
                    <div v-if="catalogoImpuestos && catalogoImpuestos.length > 0" class="p-2.5 bg-amber-50/50 rounded-xl border border-amber-200/80">
                        <label class="block text-xs font-bold text-stone-700 mb-1">
                            ⚖️ Seleccionar Tarifa del Catálogo de Impuestos:
                        </label>
                        <select
                            @change="seleccionarTarifaImpuesto($event.target.value)"
                            class="w-full text-xs border border-stone-300 rounded-lg p-1.5 bg-white text-stone-800"
                        >
                            <option value="">-- Tarifas configuradas por SuperAdmin --</option>
                            <option
                                v-for="imp in catalogoImpuestos.filter(i => i.activo)"
                                :key="imp.id"
                                :value="imp.id"
                            >
                                {{ imp.nombre }} ({{ imp.porcentaje }}%)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Descripción</label>
                        <textarea v-model="formItem.descripcion" rows="2" class="w-full border rounded-xl p-2 text-stone-900"></textarea>
                    </div>

                    <!-- Imagen Opcional del Producto (Item 1) -->
                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-stone-800 text-xs">
                                🖼️ Imagen del Producto <span class="text-stone-400 font-normal">(Opcional)</span>
                            </label>
                            <span class="text-[10px] text-stone-500 font-mono">Máx. 5 MB &bull; .jpg, .jpeg, .png, .webp</span>
                        </div>
                        <p class="text-[11px] text-stone-500">
                            Dimensiones sugeridas: <strong>250x250</strong>, <strong>300x300</strong> o <strong>500x500 – 600x600 px</strong>. El contenedor ajusta automáticamente la imagen en móvil y escritorio.
                        </p>

                        <div class="flex items-center gap-3">
                            <div
                                v-if="vistaPreviaImagen || (formItem.imagen && !formItem.quitar_imagen)"
                                class="w-16 h-16 rounded-xl overflow-hidden border border-stone-300 bg-white shrink-0 relative"
                            >
                                <img
                                    :src="vistaPreviaImagen || formItem.imagen"
                                    alt="Vista previa"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="flex-1 space-y-1.5">
                                <input
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    @change="seleccionarArchivoImagen"
                                    class="block w-full text-xs text-stone-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-100 file:text-orange-800 hover:file:bg-orange-200 cursor-pointer"
                                />
                                <button
                                    v-if="vistaPreviaImagen || (formItem.imagen && !formItem.quitar_imagen)"
                                    type="button"
                                    @click="limpiarImagenProducto"
                                    class="text-[11px] text-rose-600 hover:underline font-semibold"
                                >
                                    Quitar imagen asociada
                                </button>
                                <p v-if="errorImagenProducto" class="text-[11px] text-rose-600 font-bold">
                                    {{ errorImagenProducto }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 p-3 bg-stone-50 rounded-xl border border-stone-200">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="formItem.es_combo" class="rounded text-orange-600" />
                            <span class="font-bold text-stone-800">¿Es un Combo?</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="formItem.controla_inventario" class="rounded text-orange-600" />
                            <span class="font-bold text-stone-800">Controla Inventario</span>
                        </label>
                    </div>

                    <!-- Exclusión de descuento de categoría (Item 8) -->
                    <div class="p-3 bg-amber-50/60 rounded-xl border border-amber-200">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="formItem.excluir_descuentos_categoria"
                                class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4"
                            />
                            <div>
                                <span class="font-bold text-stone-800 block text-xs">Excluir de descuentos de categoría</span>
                                <span class="text-[11px] text-stone-500">Si se activa, las promociones aplicadas a la categoría no rebajarán este producto/combo.</span>
                            </div>
                        </label>
                    </div>

                    <!-- Sección de Armado de Combo con Selección y Descuento de Inventario -->
                    <div v-if="formItem.es_combo" class="p-3.5 bg-purple-50/70 border border-purple-200 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-black uppercase tracking-wider text-purple-900 flex items-center gap-1.5">
                                <span>🍔🍟</span>
                                <span>Productos que conforman este Combo</span>
                            </label>
                            <span class="text-[10px] text-purple-700 font-bold bg-purple-100 px-2 py-0.5 rounded-full">
                                {{ (formItem.combo_items || []).length }} asociados
                            </span>
                        </div>

                        <!-- Buscador y Selector de Ítems Existentes -->
                        <div class="space-y-2">
                            <input
                                type="text"
                                v-model="busquedaProductoCombo"
                                placeholder="🔍 Buscar plato o bebida por nombre..."
                                class="w-full bg-white border border-purple-200 text-stone-800 text-xs rounded-xl px-3 py-1.5 focus:ring-1 focus:ring-purple-500"
                            />

                            <div class="flex gap-2 items-center">
                                <select
                                    v-model="comboProductoSeleccionadoId"
                                    class="flex-1 bg-white border border-purple-200 text-stone-800 text-xs rounded-xl p-2 focus:ring-1 focus:ring-purple-500"
                                >
                                    <option value="">-- Seleccionar producto existente --</option>
                                    <option v-for="pi in itemsComboFiltrados" :key="pi.id" :value="pi.id">
                                        {{ pi.nombre }} (Stock: {{ pi.existencia }})
                                    </option>
                                </select>
                                <div class="w-20">
                                    <input
                                        type="number"
                                        min="1"
                                        v-model.number="comboCantidad"
                                        class="w-full bg-white border border-purple-200 text-stone-800 text-xs rounded-xl p-2 text-center"
                                        placeholder="Cant."
                                        title="Cantidad incluida en el combo"
                                    />
                                </div>
                                <button
                                    type="button"
                                    @click="agregarProductoAlCombo"
                                    :disabled="!comboProductoSeleccionadoId"
                                    class="px-3 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer"
                                >
                                    + Añadir
                                </button>
                            </div>
                        </div>

                        <!-- Lista de Ítems Asociados -->
                        <div v-if="formItem.combo_items && formItem.combo_items.length > 0" class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                            <div
                                v-for="(ci, cIdx) in formItem.combo_items"
                                :key="ci.item_id"
                                class="flex items-center justify-between bg-white border border-purple-100 px-3 py-1.5 rounded-xl text-xs"
                            >
                                <span class="font-bold text-stone-800 truncate pr-2">
                                    {{ ci.nombre }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs bg-purple-100 text-purple-900 font-bold px-2 py-0.5 rounded-md">
                                        {{ ci.cantidad }} unid.
                                    </span>
                                    <button
                                        type="button"
                                        @click="removerProductoDelCombo(cIdx)"
                                        class="text-rose-500 hover:text-rose-700 font-bold px-1 cursor-pointer"
                                        title="Quitar componente"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-2 text-stone-400 text-[11px]">
                            Selecciona los platos o bebidas que conforman este combo.
                        </div>

                        <div class="p-2.5 bg-purple-100/70 rounded-xl text-[11px] text-purple-950 leading-relaxed border border-purple-200/60">
                            💡 <strong>Descuento automático de inventario:</strong> Cada venta de este combo descontará automáticamente del inventario la cantidad asociada de cada producto que lo conforma (1 unidad o la especificada). El campo de existencias a continuación puede utilizarse si el combo no depende de platos asociados o para inventario propio.
                        </div>
                    </div>

                    <div v-if="formItem.controla_inventario" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Existencia Actual</label>
                            <input type="number" v-model.number="formItem.existencia" min="0" class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-stone-700 mb-1">Alerta Mínima</label>
                            <input type="number" v-model.number="formItem.existencia_minima" min="0" class="w-full border rounded-xl p-2 text-stone-900" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="modalItemVisible = false" class="px-4 py-2 border rounded-xl text-stone-600">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white font-bold rounded-xl">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Copiar Menú (CA-25) -->
        <div v-if="modalCopiarVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-stone-200">
                <h3 class="text-lg font-bold text-stone-900 mb-2">📋 Copiar Menú de Otra Sede</h3>
                <p class="text-xs text-stone-500 mb-4">Esta acción clonará las categorías, productos y combos de la sede seleccionada hacia esta sede.</p>

                <form @submit.prevent="ejecutarCopiarMenu" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Sede de Origen</label>
                        <select v-model="sucursalOrigenId" required class="w-full border rounded-xl p-2.5 text-stone-900">
                            <option value="">Selecciona la sede origen...</option>
                            <option v-for="suc in otrasSucursales" :key="suc.id" :value="suc.id">{{ suc.nombre }}</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button type="button" @click="modalCopiarVisible = false" class="px-4 py-2 border rounded-xl text-stone-600">Cancelar</button>
                        <button type="submit" :disabled="!sucursalOrigenId" class="px-4 py-2 bg-orange-600 disabled:opacity-50 text-white font-bold rounded-xl">Copiar Menú Ahora</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Importar Productos CSV/Excel y Descargar Plantilla (Item 1) -->
        <div v-if="modalImportarVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/50 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-stone-200 space-y-4">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <h3 class="text-base font-black text-stone-900 flex items-center gap-2">
                        <span>📥</span>
                        <span>Importación de Productos (.CSV / Excel)</span>
                    </h3>
                    <button type="button" @click="modalImportarVisible = false" class="text-stone-400 hover:text-stone-700">✕</button>
                </div>

                <!-- Mensaje importante requerido para el usuario -->
                <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-950 space-y-1.5 leading-relaxed">
                    <p class="font-black flex items-center gap-1.5">
                        <span>⚠️</span>
                        <span>Importante antes de importar tus productos:</span>
                    </p>
                    <p>
                        Previamente deben existir las <strong>categorías</strong> en el sistema para asociar los productos a dichas categorías.
                        Si se cargan productos y no hay categorías creadas o asociadas en el archivo, se agruparán automáticamente por defecto en
                        <strong>«Sin categorizar»</strong> (parámetro del sistema inmutable, no editable ni borrable) para evitar errores.
                    </p>
                </div>

                <!-- Paso 1: Descargar plantilla con 6 productos de prueba -->
                <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                    <div>
                        <span class="font-bold text-stone-900 block">1. Plantilla Inicial con 6 Productos de Prueba</span>
                        <span class="text-[11px] text-stone-500">Incluye las columnas listas para abrir y editar en Excel o CSV.</span>
                    </div>
                    <a
                        href="/admin/menu/plantilla"
                        class="px-3.5 py-2 rounded-xl bg-stone-900 hover:bg-stone-800 text-white font-bold text-xs text-center shrink-0 transition"
                    >
                        📄 Descargar Plantilla (6 Productos)
                    </a>
                </div>

                <!-- Paso 2: Subir archivo CSV o Excel -->
                <form @submit.prevent="ejecutarImportacion" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-stone-800 mb-1">
                            2. Seleccionar Archivo (.csv o Excel .xlsx / .xls) *
                        </label>
                        <input
                            type="file"
                            accept=".csv,.txt,.xls,.xlsx"
                            @change="archivoImportar = $event.target.files?.[0] || null"
                            required
                            class="block w-full text-xs text-stone-700 border border-stone-300 rounded-xl p-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-stone-100">
                        <button type="button" @click="modalImportarVisible = false" class="px-4 py-2 border rounded-xl text-stone-600">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="!archivoImportar || importandoArchivo"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-xl transition cursor-pointer"
                        >
                            {{ importandoArchivo ? 'Importando...' : '🚀 Importar Productos' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    categorias: Array,
    items: Object,
    itemsParaCombo: {
        type: Array,
        default: () => [],
    },
    otrasSucursales: Array,
    filtros: Object,
    tiposImpuesto: Array,
    catalogoImpuestos: {
        type: Array,
        default: () => [],
    },
});

const tabActiva = ref('items');
const filtroCategoriaId = ref(props.filtros.categoria_id || '');
const filtroBuscar = ref(props.filtros.buscar || '');

function aplicarFiltros() {
    router.get('/admin/menu', {
        categoria_id: filtroCategoriaId.value || undefined,
        buscar: filtroBuscar.value || undefined,
    }, { preserveState: true });
}

// Modal Categorías
const modalCategoriaVisible = ref(false);
const categoriaEnEdicion = ref(null);
const formCategoria = ref({ nombre: '', descripcion: '', orden: 0, icono: '', activa: true });

function abrirModalCategoria(cat = null) {
    categoriaEnEdicion.value = cat;
    if (cat) {
        formCategoria.value = { ...cat };
    } else {
        formCategoria.value = { nombre: '', descripcion: '', orden: props.categorias.length + 1, icono: '', activa: true };
    }
    modalCategoriaVisible.value = true;
}

function guardarCategoria() {
    if (categoriaEnEdicion.value) {
        router.put(`/admin/menu/categorias/${categoriaEnEdicion.value.id}`, formCategoria.value, {
            onSuccess: () => modalCategoriaVisible.value = false,
        });
    } else {
        router.post('/admin/menu/categorias', formCategoria.value, {
            onSuccess: () => modalCategoriaVisible.value = false,
        });
    }
}

function eliminarCategoria(cat) {
    if (confirm(`¿Estás seguro de enviar la categoría "${cat.nombre}" a la papelera?`)) {
        router.delete(`/admin/menu/categorias/${cat.id}`);
    }
}

// Modal Productos
const modalItemVisible = ref(false);
const itemEnEdicion = ref(null);
const vistaPreviaImagen = ref(null);
const errorImagenProducto = ref('');

// Modal Importar CSV / Excel
const modalImportarVisible = ref(false);
const archivoImportar = ref(null);
const importandoArchivo = ref(false);

function ejecutarImportacion() {
    if (!archivoImportar.value) return;
    importandoArchivo.value = true;
    router.post('/admin/menu/importar', {
        archivo: archivoImportar.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            modalImportarVisible.value = false;
            archivoImportar.value = null;
        },
        onFinish: () => {
            importandoArchivo.value = false;
        },
    });
}

const formItem = ref({
    categoria_id: '',
    nombre: '',
    descripcion: '',
    precio: 0,
    imagen: null,
    imagen_archivo: null,
    quitar_imagen: false,
    impuesto_tipo: 'INC',
    impuesto_porcentaje: 8.0,
    es_combo: false,
    excluir_descuentos_categoria: false,
    controla_inventario: true,
    existencia: 0,
    existencia_minima: 5,
    disponible: true,
    activo: true,
    orden: 0,
    combo_items: [],
});

function seleccionarArchivoImagen(event) {
    errorImagenProducto.value = '';
    const file = event.target.files?.[0];
    if (!file) return;

    const permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!permitidos.includes(file.type)) {
        errorImagenProducto.value = 'Formato no válido. Usa únicamente .jpg, .jpeg, .png o .webp.';
        event.target.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        errorImagenProducto.value = 'La imagen supera el tamaño máximo permitido de 5 MB.';
        event.target.value = '';
        return;
    }

    formItem.value.imagen_archivo = file;
    formItem.value.quitar_imagen = false;
    vistaPreviaImagen.value = URL.createObjectURL(file);
}

function limpiarImagenProducto() {
    formItem.value.imagen_archivo = null;
    formItem.value.quitar_imagen = true;
    vistaPreviaImagen.value = null;
    errorImagenProducto.value = '';
}

function seleccionarTarifaImpuesto(id) {
    if (!id) return;
    const imp = (props.catalogoImpuestos || []).find(i => i.id === id);
    if (imp) {
        formItem.value.impuesto_porcentaje = Number(imp.porcentaje);
        if (imp.codigo.startsWith('IVA')) {
            formItem.value.impuesto_tipo = 'IVA';
        } else if (imp.codigo.startsWith('INC')) {
            formItem.value.impuesto_tipo = 'INC';
        } else {
            formItem.value.impuesto_tipo = 'EXENTO';
        }
    }
}

// Selección y filtrado de componentes para combos
const comboProductoSeleccionadoId = ref('');
const comboCantidad = ref(1);
const busquedaProductoCombo = ref('');

const itemsComboFiltrados = computed(() => {
    const list = props.itemsParaCombo || [];
    if (!busquedaProductoCombo.value.trim()) return list;
    const term = busquedaProductoCombo.value.toLowerCase().trim();
    return list.filter(i => i.nombre.toLowerCase().includes(term));
});

function agregarProductoAlCombo() {
    if (!comboProductoSeleccionadoId.value) return;
    const prod = (props.itemsParaCombo || []).find(i => i.id === Number(comboProductoSeleccionadoId.value));
    if (!prod) return;

    const cantSegura = Math.max(1, Number(comboCantidad.value) || 1);

    if (!formItem.value.combo_items) {
        formItem.value.combo_items = [];
    }

    const existe = formItem.value.combo_items.find(ci => ci.item_id === prod.id);
    if (existe) {
        existe.cantidad += cantSegura;
    } else {
        formItem.value.combo_items.push({
            item_id: prod.id,
            nombre: prod.nombre,
            cantidad: cantSegura,
        });
    }

    comboProductoSeleccionadoId.value = '';
    comboCantidad.value = 1;
    busquedaProductoCombo.value = '';
}

function removerProductoDelCombo(idx) {
    if (formItem.value.combo_items) {
        formItem.value.combo_items.splice(idx, 1);
    }
}

function abrirModalItem(item = null) {
    itemEnEdicion.value = item;
    comboProductoSeleccionadoId.value = '';
    comboCantidad.value = 1;
    busquedaProductoCombo.value = '';
    vistaPreviaImagen.value = null;
    errorImagenProducto.value = '';

    if (item) {
        formItem.value = {
            ...item,
            imagen_archivo: null,
            quitar_imagen: false,
            excluir_descuentos_categoria: Boolean(item.excluir_descuentos_categoria),
            combo_items: item.items_del_combo ? item.items_del_combo.map(c => ({
                item_id: c.id,
                nombre: c.nombre,
                cantidad: c.pivot ? Number(c.pivot.cantidad) : 1,
            })) : [],
        };
    } else {
        formItem.value = {
            categoria_id: props.categorias[0]?.id || '',
            nombre: '',
            descripcion: '',
            precio: 0,
            imagen: null,
            imagen_archivo: null,
            quitar_imagen: false,
            impuesto_tipo: 'INC',
            impuesto_porcentaje: 8.0,
            es_combo: false,
            excluir_descuentos_categoria: false,
            controla_inventario: true,
            existencia: 0,
            existencia_minima: 5,
            disponible: true,
            activo: true,
            orden: 0,
            combo_items: [],
        };
    }
    modalItemVisible.value = true;
}

function guardarItem() {
    const payload = {
        ...formItem.value,
        precio: Math.max(0, Number(formItem.value.precio) || 0),
        impuesto_porcentaje: Math.max(0, Number(formItem.value.impuesto_porcentaje) || 0),
        existencia: Math.max(0, Number(formItem.value.existencia) || 0),
        existencia_minima: Math.max(0, Number(formItem.value.existencia_minima) || 0),
        orden: Math.max(0, Number(formItem.value.orden) || 0),
        combo_items: (formItem.value.combo_items || []).map(ci => ({
            item_id: ci.item_id,
            cantidad: Math.max(1, Number(ci.cantidad) || 1),
        })),
    };

    const tieneArchivo = Boolean(payload.imagen_archivo);

    if (itemEnEdicion.value) {
        if (tieneArchivo) {
            router.post(`/admin/menu/items/${itemEnEdicion.value.id}`, {
                ...payload,
                _method: 'PUT',
            }, {
                forceFormData: true,
                onSuccess: () => modalItemVisible.value = false,
            });
        } else {
            router.put(`/admin/menu/items/${itemEnEdicion.value.id}`, payload, {
                onSuccess: () => modalItemVisible.value = false,
            });
        }
    } else {
        router.post('/admin/menu/items', payload, {
            forceFormData: tieneArchivo,
            onSuccess: () => modalItemVisible.value = false,
        });
    }
}

function alternarDisponibilidad(item) {
    router.post(`/admin/menu/items/${item.id}/disponibilidad`, {}, { preserveScroll: true });
}

function eliminarItem(item) {
    if (confirm(`¿Estás seguro de enviar "${item.nombre}" a la papelera?`)) {
        router.delete(`/admin/menu/items/${item.id}`);
    }
}

// Modal Copiar Menú (CA-25)
const modalCopiarVisible = ref(false);
const sucursalOrigenId = ref('');

function abrirModalCopiar() {
    sucursalOrigenId.value = '';
    modalCopiarVisible.value = true;
}

function ejecutarCopiarMenu() {
    if (confirm('¿Confirmas copiar el menú completo de la sede seleccionada?')) {
        router.post('/admin/menu/copiar', {
            sucursal_origen_id: sucursalOrigenId.value,
        }, {
            onSuccess: () => modalCopiarVisible.value = false,
        });
    }
}
</script>
