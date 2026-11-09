<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PlantillaPruebaMail;
use App\Models\PlantillaCorreo;
use App\Models\Sucursal;
use App\Services\PlantillaRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PlantillaCorreoController extends Controller
{
    /**
     * Editor visual de plantillas de correo con variables disponibles (RF-CFG-04..09, CA-34..36).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $esSuperAdmin = $user->hasRole('super_admin');

        $sucursales = $esSuperAdmin
            ? Sucursal::where('activa', true)->orderBy('nombre')->get(['id', 'nombre'])
            : $user->sucursales()->where('activa', true)->orderBy('nombre')->get(['sucursales.id', 'sucursales.nombre']);

        $primeraSucursal = $sucursales->first();
        $primerId = $primeraSucursal ? $primeraSucursal->id : 1;
        $sucursalId = (int) $request->input('sucursal_id', session('sucursal_activa_id', $primerId));

        $plantillas = PlantillaCorreo::where(function ($q) use ($sucursalId) {
            $q->where('sucursal_id', $sucursalId)->orWhereNull('sucursal_id');
        })->get()->map(fn ($p) => [
            'id' => $p->id,
            'clave' => $p->clave,
            'nombre' => $p->nombre,
            'asunto' => $p->asunto,
            'cuerpo_html' => $p->cuerpo_html,
            'variables_disponibles' => $p->variables_disponibles,
            'activa' => $p->activa,
        ]);

        return Inertia::render('Admin/PlantillasCorreo/Index', [
            'plantillas' => $plantillas,
            'sucursales' => $sucursales,
            'sucursal_id' => $sucursalId,
        ]);
    }

    /**
     * Actualiza una plantilla de correo saneando el HTML con HTMLPurifier (sin compilar Blade).
     */
    public function update(Request $request, PlantillaCorreo $plantilla): RedirectResponse
    {
        $validated = $request->validate([
            'asunto' => ['required', 'string', 'max:255'],
            'cuerpo_html' => ['required', 'string'],
        ]);

        // Sanitización con HTMLPurifier
        $cuerpoLimpio = PlantillaRenderer::sanitizar($validated['cuerpo_html']);

        $plantilla->update([
            'asunto' => $validated['asunto'],
            'cuerpo_html' => $cuerpoLimpio,
        ]);

        return back()->with('success', "Plantilla '{$plantilla->nombre}' guardada exitosamente.");
    }

    /**
     * Retorna la vista previa HTML renderizada con variables de prueba.
     */
    public function vistaPrevia(PlantillaCorreo $plantilla): JsonResponse
    {
        $variables = PlantillaRenderer::variablesPrueba($plantilla->clave);
        $renderizado = PlantillaRenderer::render($plantilla->cuerpo_html, $variables);

        return response()->json([
            'asunto' => PlantillaRenderer::render($plantilla->asunto, $variables),
            'html' => $renderizado,
        ]);
    }

    /**
     * Envía un correo electrónico de prueba al usuario administrador actual.
     */
    public function enviarPrueba(Request $request, PlantillaCorreo $plantilla): RedirectResponse
    {
        $user = $request->user();
        $variables = PlantillaRenderer::variablesPrueba($plantilla->clave);
        $html = PlantillaRenderer::render($plantilla->cuerpo_html, $variables);
        $asunto = '[PRUEBA] '.PlantillaRenderer::render($plantilla->asunto, $variables);

        Mail::to($user->email)->send(new PlantillaPruebaMail($asunto, $html));

        return back()->with('success', "Correo de prueba enviado a {$user->email}.");
    }
}
