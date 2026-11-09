<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\TelefonoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Cliente/Perfil', [
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'direccion' => $user->direccion,
                'avatar' => $user->avatar,
                'idioma' => $user->idioma,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $telefono = TelefonoService::normalizar($request->input('telefono'));
        $request->merge(['telefono' => $telefono]);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'telefono')->ignore($user->id),
            ],
            'direccion' => ['required', 'string', 'max:255'],
            'idioma' => ['required', 'string', 'in:es,en'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ]);

        $datos = [
            'nombre' => $validated['nombre'],
            'telefono' => $validated['telefono'],
            'direccion' => $validated['direccion'],
            'idioma' => $validated['idioma'],
        ];

        if (! empty($validated['password'])) {
            $datos['password'] = Hash::make($validated['password']);
        }

        $user->update($datos);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function regenerarAvatar(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $nuevoAvatar = AvatarService::regenerar($user->nombre, $user->avatar);
        $user->update(['avatar' => $nuevoAvatar]);

        return back()->with('success', 'Avatar regenerado correctamente.');
    }
}
