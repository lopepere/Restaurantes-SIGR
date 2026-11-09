<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\ModoInventario;
use App\Http\Controllers\Controller;
use App\Services\ConfiguracionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends Controller
{
    /**
     * Vista de parámetros generales del sistema.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('SuperAdmin/Configuracion/Index', [
            'configuraciones' => [
                'modo_inventario' => ConfiguracionService::obtener('inventario.modo', null, 'AUTONOMO'),
                'ventana_edicion' => (int) ConfiguracionService::obtener('ventana_edicion', null, 5),
                'cancelacion_automatica' => (bool) ConfiguracionService::obtener('cancelacion_automatica', null, false),
                'ocultar_agotados' => (bool) ConfiguracionService::obtener('ocultar_agotados', null, false),
                'propina_preseleccionar' => (bool) ConfiguracionService::obtener('propina.preseleccionar', null, false),
                'reserva_duracion_minutos' => (int) ConfiguracionService::obtener('reserva.duracion_minutos', null, 120),
                'reserva_aprobacion_automatica' => (bool) ConfiguracionService::obtener('reserva.aprobacion_automatica', null, false),
            ],
        ]);
    }

    /**
     * Vista de configuración tributaria e impuestos (IVA / INC 8%).
     */
    public function impuestos(Request $request): Response
    {
        return Inertia::render('SuperAdmin/Configuracion/Impuestos', [
            'impuestos' => [
                'inc_porcentaje' => (float) ConfiguracionService::obtener('impuestos.inc_porcentaje', null, 8.00),
                'iva_porcentaje' => (float) ConfiguracionService::obtener('impuestos.iva_porcentaje', null, 19.00),
                'regimen' => (string) ConfiguracionService::obtener('impuestos.regimen', null, 'Responsable de IVA / INC'),
                'precios_con_impuesto' => (bool) ConfiguracionService::obtener('impuestos.precios_con_impuesto', null, true),
            ],
        ]);
    }

    /**
     * Guarda o actualiza los parámetros globales de configuración.
     */
    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'parametros' => 'required|array',
            'parametros.*' => 'nullable',
        ]);

        foreach ($datos['parametros'] as $clave => $valor) {
            if ($clave === 'modo_inventario') {
                $modoEnum = ModoInventario::tryFrom((string) $valor) ?? ModoInventario::AUTONOMO;
                ConfiguracionService::set('inventario.modo', $modoEnum->value);

                continue;
            }

            ConfiguracionService::set($clave, $valor);
        }

        return back()->with('success', 'Configuración del sistema actualizada correctamente.');
    }
}
