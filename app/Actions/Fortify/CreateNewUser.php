<?php

namespace App\Actions\Fortify;

use App\Actions\Usuarios\RegistrarClienteAction;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        protected RegistrarClienteAction $registrarClienteAction
    ) {}

    /**
     * Valida y crea un nuevo usuario registrado voluntariamente (RF-USR-14, SC-01).
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'telefono' => ['required', 'string', 'max:30'],
            'direccion' => ['required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
            'politica_aceptada' => ['required', 'accepted'],
        ], [
            'politica_aceptada.accepted' => 'Debes aceptar la política de tratamiento de datos personales.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'direccion.required' => 'La dirección es obligatoria.',
        ])->validate();

        $nombre = $input['nombre'] ?? $input['name'] ?? 'Cliente';

        return $this->registrarClienteAction->handle([
            'nombre' => (string) $nombre,
            'email' => (string) $input['email'],
            'telefono' => (string) $input['telefono'],
            'direccion' => (string) $input['direccion'],
            'password' => (string) $input['password'],
            'politica_aceptada' => (bool) $input['politica_aceptada'],
        ]);
    }
}
