<?php

use App\Http\Controllers\Admin\BitacoraController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MeseroController;
use App\Http\Controllers\Admin\PapeleraController;
use App\Http\Controllers\Admin\PromocionController;
use App\Http\Controllers\Auth\InvitacionController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\Cliente\DashboardController as ClienteDashboardController;
use App\Http\Controllers\Cliente\PerfilController;
use App\Http\Controllers\Mesero\DashboardController as MeseroDashboardController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\SuperAdmin\AdministradorController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SucursalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (Visitante)
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/sucursal/{slug}', HomeController::class)->name('home.sucursal');

// Registro voluntario desde el home (RF-USR-14, SC-01)
Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:6,1')->name('registro.store');

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
});

/*
|--------------------------------------------------------------------------
| Rutas del Mesero
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:mesero'])->prefix('mesero')->name('mesero.')->group(function () {
    Route::get('/', MeseroDashboardController::class)->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Rutas del Administrador de Sucursal
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin|super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    // CRUD de Meseros de la sucursal (RF-USR-09)
    Route::get('/meseros', [MeseroController::class, 'index'])->name('meseros.index');
    Route::post('/meseros', [MeseroController::class, 'store'])->name('meseros.store');
    Route::put('/meseros/{mesero}', [MeseroController::class, 'update'])->name('meseros.update');
    Route::delete('/meseros/{mesero}', [MeseroController::class, 'destroy'])->name('meseros.destroy');

    // Gestión del Menú y Combos (RF-MEN-01..11)
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::post('/menu/categorias', [MenuController::class, 'storeCategoria'])->name('menu.categorias.store');
    Route::put('/menu/categorias/{categoria}', [MenuController::class, 'updateCategoria'])->name('menu.categorias.update');
    Route::delete('/menu/categorias/{categoria}', [MenuController::class, 'destroyCategoria'])->name('menu.categorias.destroy');
    Route::post('/menu/items', [MenuController::class, 'storeItem'])->name('menu.items.store');
    Route::put('/menu/items/{itemMenu}', [MenuController::class, 'updateItem'])->name('menu.items.update');
    Route::delete('/menu/items/{itemMenu}', [MenuController::class, 'destroyItem'])->name('menu.items.destroy');
    Route::post('/menu/items/{itemMenu}/disponibilidad', [MenuController::class, 'toggleDisponibilidad'])->name('menu.items.toggle');
    Route::post('/menu/copiar', [MenuController::class, 'copiarMenu'])->name('menu.copiar');

    // Gestión de Promociones (RF-PRO-01..05)
    Route::get('/promociones', [PromocionController::class, 'index'])->name('promociones.index');
    Route::post('/promociones', [PromocionController::class, 'store'])->name('promociones.store');
    Route::put('/promociones/{promocion}', [PromocionController::class, 'update'])->name('promociones.update');
    Route::delete('/promociones/{promocion}', [PromocionController::class, 'destroy'])->name('promociones.destroy');

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

    // Gestión de Sucursales (RF-CFG-01)
    Route::get('/sucursales', [SucursalController::class, 'index'])->name('sucursales.index');
    Route::post('/sucursales', [SucursalController::class, 'store'])->name('sucursales.store');
    Route::put('/sucursales/{sucursal}', [SucursalController::class, 'update'])->name('sucursales.update');
    Route::delete('/sucursales/{sucursal}', [SucursalController::class, 'destroy'])->name('sucursales.destroy');
});
