<?php

namespace App\Actions\Usuarios;

use App\Enums\OrigenUsuario;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\TelefonoService;
use Illuminate\Validation\ValidationException;

class CrearClienteAction
{
    public function __construct(
        protected InvitarClienteAction $invitarClienteAction
    ) {}

    /**
     * Crea la cuenta implícita de un cliente al completar su primer pedido o reserva sin cuenta (RF-USR-01, CA-01).
     *
     * @param  array{nombre: string, email: string, telefono: string, direccion: string, origen?: OrigenUsuario|string}  $datos
     */
    public function handle(array $datos): User
    {
        $email = strtolower(trim($datos['email']));
        $telefono = TelefonoService::normalizar($datos['telefono']);

        // Unicidad estricta incluyendo eliminados lógicamente (DE-05, RN-USR-02, CA-02, CA-03)
        if (User::withTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'El correo electrónico ya está registrado. Por favor inicia sesión.',
            ]);
        }

        if (User::withTrashed()->where('telefono', $telefono)->exists()) {
            throw ValidationException::withMessages([
                'telefono' => 'El teléfono ya está registrado.',
            ]);
        }

        $avatar = AvatarService::generar($datos['nombre']);

        $user = User::create([
            'nombre' => $datos['nombre'],
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $datos['direccion'],
            'password' => null, // Se define mediante la invitación firmada
            'avatar' => $avatar,
            'idioma' => 'es',
            'activo' => true,
            'origen' => $datos['origen'] ?? OrigenUsuario::PEDIDO,
            'politica_aceptada_at' => now(),
        ]);

        $user->assignRole('cliente');

        // Enviar invitación firmada de 72 horas (RF-USR-03, RN-USR-04)
        $this->invitarClienteAction->handle($user);

        return $user;
    }
}
