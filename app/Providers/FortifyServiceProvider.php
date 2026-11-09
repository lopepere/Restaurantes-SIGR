<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Validación de credenciales, estado activo y bloqueo estricto de doble sesión simultánea
        Fortify::authenticateUsing(function (Request $request) {
            /** @var User|null $user */
            $user = User::where(Fortify::username(), $request->input(Fortify::username()))->first();

            if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            if (! $user->activo) {
                throw ValidationException::withMessages([
                    Fortify::username() => ['Esta cuenta de usuario se encuentra deshabilitada.'],
                ]);
            }

            if ($user->tieneSesionActiva($request->session()->getId())) {
                if ($request->boolean('forzar_cierre_previa')) {
                    $user->liberarSesionActiva();
                } else {
                    throw ValidationException::withMessages([
                        Fortify::username() => [
                            'Este usuario ya tiene una sesión activa en la plataforma. No se permite iniciar sesión más de una vez simultáneamente; cierra primero la sesión abierta.',
                        ],
                    ]);
                }
            }

            return $user;
        });

        // Al cerrar sesión, liberar inmediatamente el bloqueo de sesión única
        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                $event->user->liberarSesionActiva();
            }
        });

        // Vistas de Autenticación con Inertia.js (A.2, A.3)
        Fortify::loginView(fn () => Inertia::render('Auth/Login'));
        Fortify::registerView(fn () => Inertia::render('Auth/Register'));
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/ForgotPassword'));
        Fortify::resetPasswordView(fn ($request) => Inertia::render('Auth/ResetPassword', [
            'token' => $request->route('token'),
            'email' => $request->email,
        ]));
        Fortify::verifyEmailView(fn () => Inertia::render('Auth/VerifyEmail'));

        // Limitación de 5 intentos por minuto en login (RF-USR-06, RNF-SEG-03)
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
