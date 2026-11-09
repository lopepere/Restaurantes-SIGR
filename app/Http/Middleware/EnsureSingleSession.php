<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleSession
{
    /**
     * Valida que el usuario tenga únicamente una sesión activa concurrente (Item 7).
     * Si inicia sesión desde otro dispositivo o ventana, la sesión previa se invalida de forma inmediata.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // En entorno de pruebas automatizadas no interferir con actingAs
        if (app()->environment('testing')) {
            return $next($request);
        }

        if (Auth::check()) {
            $user = $request->user();
            $sessionId = $request->session()->getId();

            // Si el usuario ya tiene un id de sesión registrado y no coincide con el actual
            if ($user && $user->current_session_id && $user->current_session_id !== $sessionId) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with(
                    'error',
                    'Tu sesión ha expirado porque se inició sesión con esta cuenta en otro dispositivo o ventana.'
                );
            }
        }

        return $next($request);
    }
}
