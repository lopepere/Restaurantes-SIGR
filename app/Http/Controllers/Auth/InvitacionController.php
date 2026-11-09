<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitacionController extends Controller
{
    /**
     * Muestra la vista para aceptar la invitación con URL firmada de 72 horas (DE-06, RF-USR-03).
     */
    public function show(Request $request, User $user): Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'El enlace de invitación ha vencido o es inválido.');
        }

        return Inertia::render('Auth/AceptarInvitacion', [
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Define la contraseña del cliente activando su cuenta.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'El enlace de invitación ha vencido o es inválido.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'invitacion_aceptada_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'activo' => true,
        ]);

        Auth::login($user);

        return redirect('/cliente')->with('success', '¡Cuenta activada exitosamente! Ya puedes gestionar tus pedidos y reservas.');
    }
}
