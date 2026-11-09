<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Usuarios\RegistrarClienteAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegistroController extends Controller
{
    public function __construct(
        protected RegistrarClienteAction $registrarClienteAction
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
            'direccion' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed'],
            'politica_aceptada' => ['required', 'accepted'],
        ], [
            'politica_aceptada.accepted' => 'Debes aceptar la política de tratamiento de datos personales para continuar.',
        ]);

        $user = $this->registrarClienteAction->handle([
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'direccion' => $validated['direccion'],
            'password' => $validated['password'],
            'politica_aceptada' => (bool) $validated['politica_aceptada'],
        ]);

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
