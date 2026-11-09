<?php

namespace App\Actions\Usuarios;

use App\Enums\OrigenUsuario;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\TelefonoService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegistrarClienteAction
{
    public function __construct(
        protected InvitarClienteAction $invitarClienteAction
    ) {}

    /**
     * Registro voluntario desde el home (RF-USR-14 a 17, SC-01, CA-32, CA-37).
     *
     * @param  array{nombre: string, email: string, telefono: string, direccion: string, password: string, politica_aceptada: bool}  $datos
     */
    public function handle(array $datos): User
    {
        if (empty($datos['politica_aceptada'])) {
            throw ValidationException::withMessages([
                'politica_aceptada' => 'Debes aceptar la política de tratamiento de datos personales para registrarte.',
            ]);
        }

        $email = strtolower(trim($datos['email']));
        $telefono = TelefonoService::normalizar($datos['telefono']);

        // Buscar si ya existe por email (incluyendo eliminados lógicamente, DE-05, RN-USR-02)
        $usuarioExistentePorEmail = User::withTrashed()
            ->where('email', $email)
            ->first();

        if ($usuarioExistentePorEmail) {
            // CA-37 / RN-USR-12: Cuenta creada por pedido o reserva y aún no activada (sin contraseña definida)
            if (empty($usuarioExistentePorEmail->password) && empty($usuarioExistentePorEmail->invitacion_aceptada_at)) {
                $this->invitarClienteAction->handle($usuarioExistentePorEmail);

                throw ValidationException::withMessages([
                    'email' => 'Ya existe una cuenta asociada a este correo creada desde un pedido. Hemos reenviado la invitación para definir tu contraseña.',
                ]);
            }

            // Cuenta activa existente (CA-02)
            throw ValidationException::withMessages([
                'email' => 'El correo electrónico ya se encuentra registrado. Por favor inicia sesión.',
            ]);
        }

        // Buscar si ya existe por teléfono (CA-03, RN-USR-02)
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
            'password' => Hash::make($datos['password']),
            'avatar' => $avatar,
            'idioma' => 'es',
            'activo' => true,
            'origen' => OrigenUsuario::REGISTRO,
            'politica_aceptada_at' => now(),
        ]);

        $user->assignRole('cliente');

        // Encolar correo de verificación de cuenta (RF-USR-15, CA-32)
        $user->sendEmailVerificationNotification();

        return $user;
    }
}
