<?php

namespace App\Http\Middleware;

use App\Models\Sucursal;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->nombre,
                    'email' => $user->email,
                    'telefono' => $user->telefono,
                    'direccion' => $user->direccion,
                    'roles' => $user->getRoleNames(),
                    'dashboard_url' => $user->dashboardUrl(),
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'sucursal_activa_id' => fn () => $request->session()->get('sucursal_id', $request->session()->get('sucursal_activa_id')),
            'sucursales_usuario' => fn () => $user ? (
                $user->hasRole('super_admin')
                    ? Sucursal::where('activa', true)->orderBy('nombre')->get(['id', 'nombre', 'slug', 'acepta_domicilios', 'acepta_para_llevar', 'acepta_en_sitio'])
                    : $user->sucursales()->where('activa', true)->orderBy('nombre')->get(['sucursales.id', 'sucursales.nombre', 'sucursales.slug'])
            ) : [],
            'locale' => fn () => app()->getLocale(),
        ];
    }
}
