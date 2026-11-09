<?php

use App\Http\Controllers\Admin\BitacoraController;
use App\Http\Controllers\Admin\CajaController;
use App\Http\Controllers\Admin\CompraController;
use App\Http\Controllers\Admin\ComprobanteController as AdminComprobanteController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EspacioController;
use App\Http\Controllers\Admin\InformeController;
use App\Http\Controllers\Admin\InventarioController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MeseroController;
use App\Http\Controllers\Admin\PapeleraController;
use App\Http\Controllers\Admin\PedidoController as AdminPedidoController;
use App\Http\Controllers\Admin\PlantillaCorreoController;
use App\Http\Controllers\Admin\PromocionController;
use App\Http\Controllers\Admin\ReservaController as AdminReservaController;
use App\Http\Controllers\Auth\InvitacionController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\Cajero\CajeroController;
use App\Http\Controllers\Cliente\ComprobanteController as ClienteComprobanteController;
use App\Http\Controllers\Cliente\DashboardController as ClienteDashboardController;
use App\Http\Controllers\Cliente\PedidoController as ClientePedidoController;
use App\Http\Controllers\Cliente\PerfilController;
use App\Http\Controllers\Cliente\ReservaController as ClienteReservaController;
use App\Http\Controllers\Cocina\CocinaController;
use App\Http\Controllers\Mesero\DashboardController as MeseroDashboardController;
use App\Http\Controllers\Mesero\PedidoController as MeseroPedidoController;
use App\Http\Controllers\Public\ComprobantePublicoController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PedidoPublicoController;
use App\Http\Controllers\Public\ReservaPublicaController;
use App\Http\Controllers\SuperAdmin\AdministradorController;
use App\Http\Controllers\SuperAdmin\ConfiguracionController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SucursalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (Visitante)
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/sucursal/{slug}', HomeController::class)->name('home.sucursal');
Route::post('/pedidos', [PedidoPublicoController::class, 'store'])->name('pedidos.publico.store');
Route::get('/pedidos/confirmado/{numero}', [PedidoPublicoController::class, 'confirmado'])->name('pedidos.publico.confirmado');

// Reservas Públicas (RF-PUB-07, Prompt Maestro I-4)
Route::get('/reservar', [ReservaPublicaController::class, 'create'])->name('reservas.publico.create');
Route::get('/reservas/franjas', [ReservaPublicaController::class, 'franjas'])->name('reservas.publico.franjas');
Route::post('/reservas', [ReservaPublicaController::class, 'store'])->name('reservas.publico.store');
Route::get('/reservas/confirmada/{codigo}', [ReservaPublicaController::class, 'confirmada'])->name('reservas.publico.confirmada');

// Comprobantes de Venta Públicos (SC-03, RF-FAC-04)
Route::get('/comprobante/verificar/{cuv}', [ComprobantePublicoController::class, 'verificar'])->name('comprobantes.publico.verificar');
Route::get('/comprobantes/{cuv}', [ComprobantePublicoController::class, 'verificar'])->name('comprobantes.publico.verificar.alt');
Route::get('/comprobante/{cuv}/tirilla', [ComprobantePublicoController::class, 'tirilla'])->name('comprobantes.publico.tirilla');
Route::get('/comprobantes/{cuv}/tirilla', [ComprobantePublicoController::class, 'tirilla'])->name('comprobantes.publico.tirilla.alt');

// Registro voluntario desde el home (RF-USR-14, SC-01)
Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:6,1')->name('registro.store');
});

// Enlace firmado de 72 horas para definir contraseña / activar cuenta (DE-06, RF-USR-03)
Route::get('/invitacion/aceptar/{user}', [InvitacionController::class, 'show'])->name('invitacion.aceptar');
Route::post('/invitacion/aceptar/{user}', [InvitacionController::class, 'update'])->name('invitacion.actualizar');

/*
|--------------------------------------------------------------------------
| Rutas del Cliente (Requiere verificación de email - MustVerifyEmail)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:cliente'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/', ClienteDashboardController::class)->name('dashboard');
    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::post('/perfil/avatar', [PerfilController::class, 'regenerarAvatar'])->name('perfil.avatar');

    // Pedidos del cliente en tiempo real
    Route::get('/pedidos', [ClientePedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{pedido}', [ClientePedidoController::class, 'show'])->name('pedidos.show');
    Route::put('/pedidos/{pedido}/notas', [ClientePedidoController::class, 'editarNotas'])->name('pedidos.notas.update');
    Route::post('/pedidos/{pedido}/cancelar', [ClientePedidoController::class, 'solicitarCancelacion'])->name('pedidos.cancelar');
    Route::post('/pedidos/{pedido}/repetir', [ClientePedidoController::class, 'repetir'])->name('pedidos.repetir');

    // Reservas del cliente (RF-RES-10, CA-23)
    Route::get('/reservas', [ClienteReservaController::class, 'index'])->name('reservas.index');
    Route::post('/reservas/{reserva}/cancelar', [ClienteReservaController::class, 'cancelar'])->name('reservas.cancelar');

    // Comprobantes del cliente (SC-03, RF-FAC-05, CA-31)
    Route::get('/comprobantes', [ClienteComprobanteController::class, 'index'])->name('comprobantes.index');
    Route::get('/comprobantes/{comprobante}/descargar', [ClienteComprobanteController::class, 'descargar'])->name('comprobantes.descargar');
    Route::post('/comprobantes/{comprobante}/reenviar', [ClienteComprobanteController::class, 'reenviar'])->name('comprobantes.reenviar');
});

/*
|--------------------------------------------------------------------------
| Rutas del Mesero
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:mesero'])->prefix('mesero')->name('mesero.')->group(function () {
    Route::get('/', MeseroDashboardController::class)->name('dashboard');

    // Comandas en tiempo real
    Route::get('/pedidos', [MeseroPedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/crear', [MeseroPedidoController::class, 'create'])->name('pedidos.create');
    Route::post('/pedidos', [MeseroPedidoController::class, 'store'])->name('pedidos.store');
    Route::put('/pedidos/{pedido}', [MeseroPedidoController::class, 'editar'])->name('pedidos.update');
    Route::patch('/pedidos/{pedido}/mesa', [MeseroPedidoController::class, 'cambiarMesa'])->name('pedidos.mesa');
    Route::post('/pedidos/{pedido}/cancelar', [MeseroPedidoController::class, 'solicitarCancelacion'])->name('pedidos.cancelar');
});

/*
|--------------------------------------------------------------------------
| Rutas del Cajero de Turno (Item 6)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:cajero|admin|super_admin'])->prefix('cajero')->name('cajero.')->group(function () {
    Route::get('/', [CajeroController::class, 'index'])->name('index');
    Route::post('/apertura', [CajaController::class, 'apertura'])->name('apertura');
    Route::post('/pagos/{pedido}', [CajaController::class, 'pago'])->name('pago');
    Route::post('/cierre/{sesionCaja}', [CajaController::class, 'cierre'])->name('cierre');
    Route::post('/pedidos/{pedido}/items/{item}/anular', [CajaController::class, 'anularItem'])->name('items.anular');
    Route::get('/buscar-factura', [CajeroController::class, 'buscarFactura'])->name('buscar-factura');
    Route::post('/comprobantes/{comprobante}/reenviar', [CajeroController::class, 'reenviarFactura'])->name('comprobantes.reenviar');
});

/*
|--------------------------------------------------------------------------
| Rutas de Cocina / Pantalla KDS (Item 6)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:cocinero|admin|super_admin'])->prefix('cocina')->name('cocina.')->group(function () {
    Route::get('/', [CocinaController::class, 'index'])->name('index');
    Route::post('/pedidos/{pedido}/estado', [CocinaController::class, 'cambiarEstado'])->name('pedidos.estado');
});

/*
|--------------------------------------------------------------------------
| Rutas del Administrador de Sucursal
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin|super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::post('/seleccionar-sucursal', function (Request $request) {
        $val = $request->input('sucursal_id');
        $id = ($val === 'todas') ? 'todas' : (int) $val;
        session(['sucursal_id' => $id, 'sucursal_activa_id' => $id]);

        return back()->with('success', 'Sucursal activa seleccionada.');
    })->name('seleccionar-sucursal');

    // Monitor de Pedidos en Tiempo Real (RF-PED-01..22, Prompt A.6)
    Route::get('/pedidos', [AdminPedidoController::class, 'index'])->name('pedidos.index');
    Route::post('/pedidos/{pedido}/estado', [AdminPedidoController::class, 'cambiarEstado'])->name('pedidos.estado');
    Route::post('/pedidos/{pedido}/resolver-cancelacion', [AdminPedidoController::class, 'resolverCancelacion'])->name('pedidos.resolver-cancelacion');

    // Gestión y Monitor de Reservas (RF-RES-01..13, CA-22)
    Route::get('/reservas', [AdminReservaController::class, 'index'])->name('reservas.index');
    Route::post('/reservas/{reserva}/aprobar', [AdminReservaController::class, 'aprobar'])->name('reservas.aprobar');
    Route::post('/reservas/{reserva}/cancelar', [AdminReservaController::class, 'cancelar'])->name('reservas.cancelar');
    Route::post('/reservas/{reserva}/reprogramar', [AdminReservaController::class, 'reprogramar'])->name('reservas.reprogramar');
    Route::post('/reservas/{reserva}/asistencia', [AdminReservaController::class, 'asistencia'])->name('reservas.asistencia');

    // Gestión y Mapa en Vivo de Espacios / Mesas (RF-RES-01, RF-ESP-01..05, CA-24)
    Route::get('/espacios', [EspacioController::class, 'index'])->name('espacios.index');
    Route::post('/espacios', [EspacioController::class, 'store'])->name('espacios.store');
    Route::put('/espacios/{espacio}', [EspacioController::class, 'update'])->name('espacios.update');
    Route::delete('/espacios/{espacio}', [EspacioController::class, 'destroy'])->name('espacios.destroy');

    // CRUD de Meseros de la sucursal (RF-USR-09)
    Route::get('/meseros', [MeseroController::class, 'index'])->name('meseros.index');
    Route::post('/meseros', [MeseroController::class, 'store'])->name('meseros.store');
    Route::put('/meseros/{mesero}', [MeseroController::class, 'update'])->name('meseros.update');
    Route::delete('/meseros/{mesero}', [MeseroController::class, 'destroy'])->name('meseros.destroy');

    // Gestión del Menú y Combos (RF-MEN-01..11)
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/plantilla', [MenuController::class, 'descargarPlantilla'])->name('menu.plantilla');
    Route::post('/menu/importar', [MenuController::class, 'importarProductos'])->name('menu.importar');
    Route::post('/menu/categorias', [MenuController::class, 'storeCategoria'])->name('menu.categorias.store');
    Route::put('/menu/categorias/{categoria}', [MenuController::class, 'updateCategoria'])->name('menu.categorias.update');
    Route::delete('/menu/categorias/{categoria}', [MenuController::class, 'destroyCategoria'])->name('menu.categorias.destroy');
    Route::post('/menu/items', [MenuController::class, 'storeItem'])->name('menu.items.store');
    Route::put('/menu/items/{itemMenu}', [MenuController::class, 'updateItem'])->name('menu.items.update');
    Route::post('/menu/items/{itemMenu}', [MenuController::class, 'updateItem'])->name('menu.items.update.post');
    Route::delete('/menu/items/{itemMenu}', [MenuController::class, 'destroyItem'])->name('menu.items.destroy');
    Route::post('/menu/items/{itemMenu}/disponibilidad', [MenuController::class, 'toggleDisponibilidad'])->name('menu.items.toggle');
    Route::post('/menu/copiar', [MenuController::class, 'copiarMenu'])->name('menu.copiar');

    // Gestión de Promociones (RF-PRO-01..05)
    Route::get('/promociones', [PromocionController::class, 'index'])->name('promociones.index');
    Route::post('/promociones', [PromocionController::class, 'store'])->name('promociones.store');
    Route::put('/promociones/{promocion}', [PromocionController::class, 'update'])->name('promociones.update');
    Route::delete('/promociones/{promocion}', [PromocionController::class, 'destroy'])->name('promociones.destroy');

    // Operación: Inventario y Kárdex (RF-INV-01..09, CA-27, CA-28)
    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
    Route::post('/inventario/{itemMenu}/ajustar', [InventarioController::class, 'ajustar'])->name('inventario.ajustar');
    Route::post('/inventario/ajustar-lote', [InventarioController::class, 'ajustarLote'])->name('inventario.ajustar-lote');
    Route::post('/inventario/modo', [InventarioController::class, 'cambiarModo'])->name('inventario.modo');

    // Operación: Compras y Proveedores (RF-COM-01..08)
    Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
    Route::get('/compras/exportar/excel', [CompraController::class, 'exportarExcel'])->name('compras.exportar.excel');
    Route::get('/compras/exportar/pdf', [CompraController::class, 'exportarPdf'])->name('compras.exportar.pdf');
    Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
    Route::post('/compras/{compra}/anular', [CompraController::class, 'anular'])->name('compras.anular');
    Route::post('/compras/{compra}/devolver', [CompraController::class, 'devolver'])->name('compras.devolver');
    Route::post('/compras/proveedores', [CompraController::class, 'storeProveedor'])->name('compras.proveedores.store');

    // Operación: Caja y Turnos (RF-CAJ-01..08, CA-29, CA-30)
    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::post('/caja/apertura', [CajaController::class, 'apertura'])->name('caja.apertura');
    Route::post('/caja/pagos/{pedido}', [CajaController::class, 'pago'])->name('caja.pago');
    Route::post('/caja/cierre/{sesionCaja}', [CajaController::class, 'cierre'])->name('caja.cierre');
    Route::post('/caja/cajas', [CajaController::class, 'storeCaja'])->name('caja.cajas.store');
    Route::post('/caja/configurar-base', [CajaController::class, 'configurarBase'])->name('caja.configurar-base');
    Route::post('/caja/pedidos/{pedido}/items/{item}/anular', [CajaController::class, 'anularItem'])->name('caja.items.anular');

    // Operación: Comprobantes FAC (SC-03, RF-FAC-01..06, CA-31)
    Route::get('/comprobantes', [AdminComprobanteController::class, 'index'])->name('comprobantes.index');
    Route::post('/comprobantes/{comprobante}/reenviar', [AdminComprobanteController::class, 'reenviar'])->name('comprobantes.reenviar');

    // Operación: Plantillas de Correo (RF-CFG-04..09, CA-34..36)
    Route::get('/plantillas-correo', [PlantillaCorreoController::class, 'index'])->name('plantillas-correo.index');
    Route::put('/plantillas-correo/{plantilla}', [PlantillaCorreoController::class, 'update'])->name('plantillas-correo.update');
    Route::get('/plantillas-correo/{plantilla}/vista-previa', [PlantillaCorreoController::class, 'vistaPrevia'])->name('plantillas-correo.vista-previa');
    Route::post('/plantillas-correo/{plantilla}/enviar-prueba', [PlantillaCorreoController::class, 'enviarPrueba'])->name('plantillas-correo.enviar-prueba');

    // Operación: Informes y Estadísticas (RF-INF, CA-38..40)
    Route::get('/informes', [InformeController::class, 'index'])->name('informes.index');
    Route::get('/informes/exportar/excel', [InformeController::class, 'exportarExcel'])->name('informes.exportar.excel');
    Route::get('/informes/exportar/pdf', [InformeController::class, 'exportarPdf'])->name('informes.exportar.pdf');

    // Bitácora de Auditoría de solo lectura (RF-AUD-02, 03, CA-19)
    Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    Route::put('/bitacora/{id}', [BitacoraController::class, 'update'])->name('bitacora.update');
    Route::delete('/bitacora/{id}', [BitacoraController::class, 'destroy'])->name('bitacora.destroy');

    // Papelera general con restauración (RF-AUD-04, CA-21)
    Route::get('/papelera', [PapeleraController::class, 'index'])->name('papelera.index');
    Route::post('/papelera/{modulo}/{id}/restaurar', [PapeleraController::class, 'restore'])->name('papelera.restore');
});

/*
|--------------------------------------------------------------------------
| Rutas del Super Administrador (Global)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', SuperAdminDashboardController::class)->name('dashboard');

    // Gestión de Administradores (RF-USR-10, 11)
    Route::get('/administradores', [AdministradorController::class, 'index'])->name('administradores.index');
    Route::post('/administradores', [AdministradorController::class, 'store'])->name('administradores.store');
    Route::put('/administradores/{administrador}', [AdministradorController::class, 'update'])->name('administradores.update');
    Route::delete('/administradores/{administrador}', [AdministradorController::class, 'destroy'])->name('administradores.destroy');

    // Gestión de Sucursales, Cajas, Monitores de Cocina, Usuarios y Bases de Efectivo (RF-CFG-01)
    Route::get('/sucursales', [SucursalController::class, 'index'])->name('sucursales.index');
    Route::post('/sucursales', [SucursalController::class, 'store'])->name('sucursales.store');
    Route::put('/sucursales/{sucursal}', [SucursalController::class, 'update'])->name('sucursales.update');
    Route::post('/sucursales/{sucursal}/estado', [SucursalController::class, 'toggleEstado'])->name('sucursales.estado');
    Route::post('/sucursales/{sucursal}/modalidad', [SucursalController::class, 'toggleModalidad'])->name('sucursales.modalidad');
    Route::delete('/sucursales/{sucursal}', [SucursalController::class, 'destroy'])->name('sucursales.destroy');

    // Gestión de Cajas, Cocina, Usuarios y Bases por Sede Activa
    Route::post('/sucursales/cajas', [SucursalController::class, 'storeCaja'])->name('sucursales.cajas.store');
    Route::put('/sucursales/cajas/{caja}', [SucursalController::class, 'updateCaja'])->name('sucursales.cajas.update');
    Route::post('/sucursales/estaciones-cocina', [SucursalController::class, 'storeEstacionCocina'])->name('sucursales.cocina.store');
    Route::put('/sucursales/estaciones-cocina/{estacion}', [SucursalController::class, 'updateEstacionCocina'])->name('sucursales.cocina.update');
    Route::post('/sucursales/usuarios-operativos', [SucursalController::class, 'storeUsuarioOperativo'])->name('sucursales.usuarios.store');
    Route::put('/sucursales/usuarios-operativos/{usuario}', [SucursalController::class, 'updateUsuarioOperativo'])->name('sucursales.usuarios.update');
    Route::post('/sucursales/bases-caja', [SucursalController::class, 'storeBaseCaja'])
        ->withoutMiddleware('role:super_admin')
        ->middleware('role:admin|super_admin')
        ->name('sucursales.bases.store');
    Route::put('/sucursales/bases-caja/{asignacion}', [SucursalController::class, 'updateBaseCaja'])
        ->withoutMiddleware('role:super_admin')
        ->middleware('role:admin|super_admin')
        ->name('sucursales.bases.update');

    // Parámetros Globales e Impuestos (RF-CFG-02, 03)
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
    Route::get('/impuestos', [ConfiguracionController::class, 'impuestos'])->name('impuestos.index');
    Route::post('/impuestos/catalogo', [ConfiguracionController::class, 'guardarCatalogo'])->name('impuestos.catalogo');

    // Plantillas de Correo (Acceso directo desde SuperAdmin)
    Route::get('/plantillas', [PlantillaCorreoController::class, 'index'])->name('plantillas.index');

    // Informes Consolidados (Acceso directo desde SuperAdmin)
    Route::get('/informes', [InformeController::class, 'index'])->name('informes.index');
    Route::get('/informes/exportar/excel', [InformeController::class, 'exportarExcel'])->name('informes.exportar.excel');
    Route::get('/informes/exportar/pdf', [InformeController::class, 'exportarPdf'])->name('informes.exportar.pdf');
});
