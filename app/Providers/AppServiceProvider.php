<?php

namespace App\Providers;

use App\Contracts\FacturacionElectronicaGateway;
use App\Services\Facturacion\NullFacturacionElectronicaGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            FacturacionElectronicaGateway::class,
            NullFacturacionElectronicaGateway::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Regla RNF-SEG-02: contraseñas de mínimo 8 caracteres con letras y números
        Password::defaults(function () {
            return Password::min(8)->letters()->numbers();
        });
    }
}
