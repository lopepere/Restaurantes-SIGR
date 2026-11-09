<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Comun\EliminarConValidacionAction;
use App\Actions\Menu\CopiarMenuAction;
use App\Enums\TipoImpuesto;
use App\Events\MenuActualizado;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\ComboItem;
use App\Models\ItemMenu;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ConfiguracionService;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class MenuController extends Controller
{
    public function __construct(
        protected MenuService $menuService,
        protected CopiarMenuAction $copiarMenuAction,
        protected EliminarConValidacionAction $eliminarConValidacionAction
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Categoria::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        // Asegurar que exista siempre la categoría inmutable "Sin categorizar" en la sede
        Categoria::asegurarSinCategorizar($sucursalId);

        $categorias = Categoria::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->whereNull('deleted_at')
            ->withCount('items')
            ->orderBy('orden')
            ->get();

        $items = ItemMenu::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->whereNull('deleted_at')
            ->with(['categoria', 'itemsDelCombo'])
            ->when($request->input('categoria_id'), fn ($q, $cat) => $q->where('categoria_id', $cat))
            ->when($request->input('buscar'), function ($q, $buscar) {
                $term = "%{$buscar}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('nombre', 'like', $term)
                        ->orWhere('descripcion', 'like', $term);
                });
            })
            ->orderBy('orden')
            ->paginate(25);

        // Ítems individuales de la sucursal disponibles para armar combos
        $itemsParaCombo = ItemMenu::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->whereNull('deleted_at')
            ->where('es_combo', false)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'precio', 'existencia', 'controla_inventario']);

        // Otras sucursales para el modal de copiar menú
        $otrasSucursales = Sucursal::where('id', '!=', $sucursalId)
            ->where('activa', true)
            ->get(['id', 'nombre']);

        return Inertia::render('Admin/Menu/Index', [
            'categorias' => $categorias,
            'items' => $items,
            'itemsParaCombo' => $itemsParaCombo,
            'otrasSucursales' => $otrasSucursales,
            'filtros' => $request->only(['buscar', 'categoria_id']),
            'tiposImpuesto' => array_column(TipoImpuesto::cases(), 'value'),
            'catalogoImpuestos' => ConfiguracionService::catalogoImpuestos(),
        ]);
    }

    public function storeCategoria(Request $request): RedirectResponse
    {
        Gate::authorize('create', Categoria::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'icono' => ['nullable', 'string', 'max:50'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['nombre']);

        if ($slug === 'sin-categorizar') {
            throw ValidationException::withMessages([
                'nombre' => ['La categoría "Sin categorizar" ya existe como parámetro reservado del sistema.'],
            ]);
        }

        Categoria::create([
            'sucursal_id' => $sucursalId,
            'nombre' => $validated['nombre'],
            'slug' => $slug,
            'descripcion' => $validated['descripcion'] ?? null,
            'orden' => $validated['orden'] ?? 0,
            'icono' => $validated['icono'] ?? null,
            'activa' => $validated['activa'] ?? true,
        ]);

        event(new MenuActualizado($sucursalId, 'categoria', 'Categoría creada.'));

        return back()->with('success', 'Categoría creada exitosamente.');
    }

    public function updateCategoria(Request $request, Categoria $categoria): RedirectResponse
    {
        Gate::authorize('update', $categoria);

        if ($categoria->es_sistema || $categoria->slug === 'sin-categorizar') {
            throw ValidationException::withMessages([
                'nombre' => ['La categoría por defecto del sistema "Sin categorizar" no es editable.'],
            ]);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'orden' => ['required', 'integer', 'min:0'],
            'icono' => ['nullable', 'string', 'max:50'],
            'activa' => ['required', 'boolean'],
        ]);

        $categoria->update([
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
            'orden' => $validated['orden'],
            'icono' => $validated['icono'] ?? null,
            'activa' => $validated['activa'],
        ]);

        event(new MenuActualizado($categoria->sucursal_id, 'categoria', 'Categoría actualizada.'));

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroyCategoria(Categoria $categoria): RedirectResponse
    {
        Gate::authorize('delete', $categoria);

        if ($categoria->es_sistema || $categoria->slug === 'sin-categorizar') {
            throw ValidationException::withMessages([
                'categoria' => ['La categoría por defecto del sistema "Sin categorizar" no se puede borrar.'],
            ]);
        }

        // Valida que no tenga ítems asociados (RF-AUD-05, RN-GEN-04)
        $this->eliminarConValidacionAction->handle($categoria, ['items']);

        event(new MenuActualizado($categoria->sucursal_id, 'categoria', 'Categoría eliminada.'));

        return back()->with('success', 'Categoría enviada a la papelera.');
    }

    public function storeItem(Request $request): RedirectResponse
    {
        Gate::authorize('create', ItemMenu::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $validated = $request->validate([
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'precio' => ['required', 'numeric', 'min:0'],
            'impuesto_tipo' => ['required', new Enum(TipoImpuesto::class)],
            'impuesto_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'es_combo' => ['nullable', 'boolean'],
            'excluir_descuentos_categoria' => ['nullable', 'boolean'],
            'controla_inventario' => ['nullable', 'boolean'],
            'existencia' => ['nullable', 'numeric', 'min:0'],
            'existencia_minima' => ['nullable', 'numeric', 'min:0'],
            'disponible' => ['nullable', 'boolean'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'imagen_archivo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'combo_items' => ['nullable', 'array'],
            'combo_items.*.item_id' => ['required_with:combo_items', 'integer', 'exists:items_menu,id'],
            'combo_items.*.cantidad' => ['required_with:combo_items', 'numeric', 'min:1'],
        ]);

        $categoriaId = ! empty($validated['categoria_id'])
            ? (int) $validated['categoria_id']
            : Categoria::asegurarSinCategorizar($sucursalId)->id;

        $rutaImagen = null;
        if ($request->hasFile('imagen_archivo')) {
            $path = $request->file('imagen_archivo')->store('productos', 'public');
            $rutaImagen = '/storage/'.$path;
        }

        $item = ItemMenu::create([
            'sucursal_id' => $sucursalId,
            'categoria_id' => $categoriaId,
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
            'precio' => $validated['precio'],
            'imagen' => $rutaImagen,
            'impuesto_tipo' => $validated['impuesto_tipo'],
            'impuesto_porcentaje' => $validated['impuesto_porcentaje'],
            'es_combo' => $validated['es_combo'] ?? false,
            'excluir_descuentos_categoria' => $validated['excluir_descuentos_categoria'] ?? false,
            'controla_inventario' => $validated['controla_inventario'] ?? true,
            'existencia' => $validated['existencia'] ?? 0,
            'existencia_minima' => $validated['existencia_minima'] ?? 5,
            'disponible' => $validated['disponible'] ?? true,
            'activo' => true,
            'orden' => $validated['orden'] ?? 0,
        ]);

        if (! empty($validated['es_combo']) && ! empty($validated['combo_items'])) {
            foreach ($validated['combo_items'] as $cItem) {
                ComboItem::create([
                    'combo_id' => $item->id,
                    'item_id' => $cItem['item_id'],
                    'cantidad' => $cItem['cantidad'],
                ]);
            }
        }

        event(new MenuActualizado($sucursalId, 'item', "Producto {$item->nombre} creado."));

        return back()->with('success', 'Producto agregado al menú.');
    }

    public function updateItem(Request $request, ItemMenu $itemMenu): RedirectResponse
    {
        Gate::authorize('update', $itemMenu);

        $validated = $request->validate([
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'precio' => ['required', 'numeric', 'min:0'],
            'impuesto_tipo' => ['required', new Enum(TipoImpuesto::class)],
            'impuesto_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'es_combo' => ['required', 'boolean'],
            'excluir_descuentos_categoria' => ['nullable', 'boolean'],
            'controla_inventario' => ['required', 'boolean'],
            'existencia' => ['nullable', 'numeric', 'min:0'],
            'existencia_minima' => ['nullable', 'numeric', 'min:0'],
            'disponible' => ['required', 'boolean'],
            'activo' => ['required', 'boolean'],
            'orden' => ['required', 'integer', 'min:0'],
            'imagen_archivo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'quitar_imagen' => ['nullable', 'boolean'],
            'combo_items' => ['nullable', 'array'],
            'combo_items.*.item_id' => ['required_with:combo_items', 'integer', 'exists:items_menu,id'],
            'combo_items.*.cantidad' => ['required_with:combo_items', 'numeric', 'min:1'],
        ]);

        $rutaImagen = $itemMenu->imagen;
        if ($request->boolean('quitar_imagen')) {
            $rutaImagen = null;
        }
        if ($request->hasFile('imagen_archivo')) {
            $path = $request->file('imagen_archivo')->store('productos', 'public');
            $rutaImagen = '/storage/'.$path;
        }

        $itemMenu->update([
            'categoria_id' => $validated['categoria_id'],
            'nombre' => $validated['nombre'],
            'slug' => Str::slug($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
            'precio' => $validated['precio'],
            'imagen' => $rutaImagen,
            'impuesto_tipo' => $validated['impuesto_tipo'],
            'impuesto_porcentaje' => $validated['impuesto_porcentaje'],
            'es_combo' => $validated['es_combo'],
            'excluir_descuentos_categoria' => $validated['excluir_descuentos_categoria'] ?? false,
            'controla_inventario' => $validated['controla_inventario'],
            'existencia' => $validated['existencia'] ?? 0,
            'existencia_minima' => $validated['existencia_minima'] ?? 5,
            'disponible' => $validated['disponible'],
            'activo' => $validated['activo'],
            'orden' => $validated['orden'],
        ]);

        if ($validated['es_combo']) {
            ComboItem::where('combo_id', $itemMenu->id)->delete();
            if (! empty($validated['combo_items'])) {
                foreach ($validated['combo_items'] as $cItem) {
                    ComboItem::create([
                        'combo_id' => $itemMenu->id,
                        'item_id' => $cItem['item_id'],
                        'cantidad' => $cItem['cantidad'],
                    ]);
                }
            }
        }

        event(new MenuActualizado($itemMenu->sucursal_id, 'item', "Producto {$itemMenu->nombre} actualizado."));

        return back()->with('success', 'Producto actualizado.');
    }

    public function destroyItem(ItemMenu $itemMenu): RedirectResponse
    {
        Gate::authorize('delete', $itemMenu);

        // Valida que el ítem no participe como ingrediente en ningún combo activo
        $this->eliminarConValidacionAction->handle($itemMenu, ['combosDondeAparece']);

        event(new MenuActualizado($itemMenu->sucursal_id, 'item', "Producto {$itemMenu->nombre} eliminado."));

        return back()->with('success', 'Producto enviado a la papelera.');
    }

    public function toggleDisponibilidad(ItemMenu $itemMenu): RedirectResponse
    {
        Gate::authorize('update', $itemMenu);

        $this->menuService->alternarDisponibilidad($itemMenu);

        return back()->with('success', "Disponibilidad de {$itemMenu->nombre} modificada.");
    }

    public function copiarMenu(Request $request): RedirectResponse
    {
        Gate::authorize('create', ItemMenu::class);

        /** @var User $user */
        $user = $request->user();
        $sucursalDestinoId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $validated = $request->validate([
            'sucursal_origen_id' => [
                'required',
                'integer',
                'exists:sucursales,id',
                Rule::notIn([$sucursalDestinoId]),
            ],
        ]);

        $resultado = $this->copiarMenuAction->handle($validated['sucursal_origen_id'], $sucursalDestinoId);

        return back()->with('success', "Menú copiado exitosamente: {$resultado['categorias_copiadas']} categorías, {$resultado['items_copiados']} productos y {$resultado['combos_copiados']} combos.");
    }

    /**
     * Descarga la plantilla inicial CSV/Excel con 6 productos de prueba.
     */
    public function descargarPlantilla(): StreamedResponse
    {
        $filename = 'plantilla_productos_sigr.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // BOM UTF-8 para compatibilidad directa con Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'nombre',
                'categoria',
                'descripcion',
                'precio',
                'impuesto_tipo',
                'impuesto_porcentaje',
                'controla_inventario',
                'existencia',
                'existencia_minima',
            ], ';');

            $productosPrueba = [
                ['Empanadas Tradicionales x4', 'Entradas', 'Cuatro empanadas crocantes con ají casero', 12000, 'INC', 8, 'SI', 40, 5],
                ['Patacones con Hogao', 'Entradas', 'Patacones de plátano verde con hogao criollo', 11500, 'INC', 8, 'SI', 35, 5],
                ['Bandeja Especial de la Casa', 'Platos Fuertes', 'Plato fuerte tradicional con guarniciones', 34000, 'INC', 8, 'SI', 25, 5],
                ['Pechuga a la Plancha', 'Platos Fuertes', 'Suprema de pollo asada con ensalada fresca y papas', 28000, 'INC', 8, 'SI', 30, 5],
                ['Limonada Natural de Coco', 'Bebidas', 'Bebida refrescante preparada al instante', 10500, 'INC', 8, 'SI', 50, 10],
                ['Flan Artesanal de Caramelo', 'Postres', 'Postre casero con salsa de caramelo', 9500, 'INC', 8, 'SI', 20, 5],
            ];

            foreach ($productosPrueba as $fila) {
                fputcsv($handle, $fila, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Importa productos desde un archivo .csv o Excel (.xlsx), agrupando en "Sin categorizar" si no existe la categoría.
     */
    public function importarProductos(Request $request): RedirectResponse
    {
        Gate::authorize('create', ItemMenu::class);

        $request->validate([
            'archivo' => ['required', 'file', 'max:5120'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $sucursalId = (int) (session('sucursal_id') ?? ($user->sucursalIds()[0] ?? 1));

        $archivo = $request->file('archivo');
        $extension = strtolower((string) $archivo->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'txt', 'xls', 'xlsx'], true)) {
            throw ValidationException::withMessages([
                'archivo' => ['El archivo debe estar en formato .csv o Excel (.xlsx / .xls).'],
            ]);
        }

        $sinCategorizar = Categoria::asegurarSinCategorizar($sucursalId);

        $categoriasMapa = Categoria::withoutGlobalScopes()
            ->where('sucursal_id', $sucursalId)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy(fn (Categoria $c) => Str::lower(trim($c->nombre)));

        $filas = $extension === 'xlsx'
            ? $this->leerFilasXlsx($archivo->getRealPath())
            : $this->leerFilasCsv($archivo->getRealPath());

        if (count($filas) <= 1) {
            throw ValidationException::withMessages([
                'archivo' => ['El archivo no contiene filas de productos para importar.'],
            ]);
        }

        // Quitar fila de encabezados si la primera columna es 'nombre'
        $primeraFila = $filas[0];
        $primerValor = Str::lower(trim((string) ($primeraFila[0] ?? '')));
        $inicio = in_array($primerValor, ['nombre', 'producto', 'plato', 'name'], true) ? 1 : 0;

        $importados = 0;
        $agrupadosSinCategorizar = 0;

        for ($i = $inicio; $i < count($filas); $i++) {
            $fila = $filas[$i];
            $nombre = trim((string) ($fila[0] ?? ''));
            if ($nombre === '') {
                continue;
            }

            $nombreCat = trim((string) ($fila[1] ?? ''));
            $descripcion = trim((string) ($fila[2] ?? ''));
            $precio = max(0.0, (float) str_replace([',', '$', ' '], ['.', '', ''], (string) ($fila[3] ?? 0)));
            $impuestoTipoRaw = strtolower(trim((string) ($fila[4] ?? 'inc')));
            $impuestoTipo = in_array($impuestoTipoRaw, ['inc', 'iva', 'otro'], true) ? $impuestoTipoRaw : 'inc';
            $impuestoPorcentaje = isset($fila[5]) && $fila[5] !== '' ? (float) $fila[5] : ($impuestoTipoRaw === 'exento' ? 0.0 : 8.0);
            $controlaInvRaw = strtoupper(trim((string) ($fila[6] ?? 'SI')));
            $controlaInventario = ! in_array($controlaInvRaw, ['NO', '0', 'FALSE'], true);
            $existencia = isset($fila[7]) && $fila[7] !== '' ? max(0.0, (float) $fila[7]) : 20.0;
            $existenciaMinima = isset($fila[8]) && $fila[8] !== '' ? max(0.0, (float) $fila[8]) : 5.0;

            $claveCat = Str::lower($nombreCat);
            if ($claveCat !== '' && $categoriasMapa->has($claveCat)) {
                $categoriaId = $categoriasMapa->get($claveCat)->id;
            } else {
                $categoriaId = $sinCategorizar->id;
                $agrupadosSinCategorizar++;
            }

            ItemMenu::withoutGlobalScopes()->updateOrCreate(
                [
                    'sucursal_id' => $sucursalId,
                    'slug' => Str::slug($nombre),
                ],
                [
                    'categoria_id' => $categoriaId,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion ?: null,
                    'precio' => $precio,
                    'impuesto_tipo' => $impuestoTipo,
                    'impuesto_porcentaje' => $impuestoPorcentaje,
                    'es_combo' => false,
                    'controla_inventario' => $controlaInventario,
                    'existencia' => $existencia,
                    'existencia_minima' => $existenciaMinima,
                    'disponible' => true,
                    'activo' => true,
                    'orden' => $importados + 1,
                ]
            );

            $importados++;
        }

        event(new MenuActualizado($sucursalId, 'importacion', "{$importados} productos importados."));

        $notaSinCat = $agrupadosSinCategorizar > 0
            ? " ({$agrupadosSinCategorizar} agrupado(s) automáticamente en 'Sin categorizar')"
            : '';

        return back()->with('success', "Importación completada: {$importados} producto(s) procesados exitosamente{$notaSinCat}.");
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function leerFilasCsv(string $rutaArchivo): array
    {
        $contenido = (string) file_get_contents($rutaArchivo);
        // Quitar BOM UTF-8 si existe
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido) ?? $contenido;
        $lineas = preg_split('/\r\n|\r|\n/', trim($contenido)) ?: [];

        if (empty($lineas)) {
            return [];
        }

        // Detectar delimitador (; o , o \t)
        $primera = $lineas[0];
        $delimitador = ';';
        if (substr_count($primera, ',') > substr_count($primera, ';')) {
            $delimitador = ',';
        } elseif (substr_count($primera, "\t") > substr_count($primera, ';')) {
            $delimitador = "\t";
        }

        $filas = [];
        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }
            $filas[] = array_map(fn ($v) => (string) $v, str_getcsv($linea, $delimitador));
        }

        return $filas;
    }

    /**
     * Lector ligero de archivos Excel .xlsx nativo con ZipArchive + SimpleXML.
     *
     * @return array<int, array<int, string>>
     */
    private function leerFilasXlsx(string $rutaArchivo): array
    {
        if (! class_exists(ZipArchive::class)) {
            return $this->leerFilasCsv($rutaArchivo);
        }

        $zip = new ZipArchive;
        if ($zip->open($rutaArchivo) !== true) {
            return $this->leerFilasCsv($rutaArchivo);
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sx = @simplexml_load_string($sharedXml);
            if ($sx && isset($sx->si)) {
                foreach ($sx->si as $si) {
                    $sharedStrings[] = (string) ($si->t ?? '');
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = @simplexml_load_string($sheetXml);
        if (! $sheet || ! isset($sheet->sheetData->row)) {
            return [];
        }

        $filas = [];
        foreach ($sheet->sheetData->row as $row) {
            $fila = [];
            foreach ($row->c as $celda) {
                $tipo = (string) ($celda['t'] ?? '');
                $valor = (string) ($celda->v ?? '');
                if ($tipo === 's' && isset($sharedStrings[(int) $valor])) {
                    $valor = $sharedStrings[(int) $valor];
                } elseif ($tipo === 'inlineStr') {
                    $valor = (string) ($celda->is->t ?? '');
                }
                $fila[] = $valor;
            }
            $filas[] = $fila;
        }

        return $filas;
    }
}
