<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Redirige al dashboard según el rol del usuario autenticado (CA-04, RF-USR-04).
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        /** @var User $user */
        $user = $request->user();

        // Fijar la primera sucursal activa en la sesión si tiene alguna asignada
        $sucursales = $user->sucursalIds();
        if (! empty($sucursales)) {
            session(['sucursal_id' => $sucursales[0]]);
        }

        return redirect()->intended($user->dashboardUrl());
    }
}
