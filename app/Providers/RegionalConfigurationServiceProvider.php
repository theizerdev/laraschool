<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Services\RegionalConfigurationService;
use App\Models\Empresa;
use App\Models\Pais;

class RegionalConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RegionalConfigurationService::class, function ($app) {
            return new RegionalConfigurationService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar helpers globales
        $this->registerGlobalHelpers();

        // Registrar eventos para cambios de empresa
        $this->registerEvents();
    }

    /**
     * Registrar helpers globales para configuración regional
     */
    private function registerGlobalHelpers(): void
    {
        // Helper para obtener configuración actual
        if (!function_exists(__NAMESPACE__ . '\\current_regional_config')) {
            function current_regional_config() {
                return \App\Services\RegionalConfigurationService::getCurrentConfiguration();
            }
        }

        // Helper para obtener el país actual
        if (!function_exists(__NAMESPACE__ . '\\current_pais')) {
            function current_pais() {
                $config = \App\Services\RegionalConfigurationService::getCurrentConfiguration();
                return $config['pais'] ?? null;
            }
        }

        // Helper para obtener la moneda actual
        if (!function_exists(__NAMESPACE__ . '\\current_currency')) {
            function current_currency() {
                $config = \App\Services\RegionalConfigurationService::getCurrentConfiguration();
                return $config['currency'] ?? config('app.currency', 'USD');
            }
        }

        // Helper para obtener el símbolo de moneda actual
        if (!function_exists(__NAMESPACE__ . '\\current_currency_symbol')) {
            function current_currency_symbol() {
                $config = \App\Services\RegionalConfigurationService::getCurrentConfiguration();
                return $config['currency_symbol'] ?? '$';
            }
        }
    }

    /**
     * Registrar eventos para cambios de configuración
     */
    private function registerEvents(): void
    {
        // Evento cuando se cambia la empresa seleccionada
        \Illuminate\Support\Facades\Event::listen(
            'empresa.changed',
            function ($empresa) {
                RegionalConfigurationService::setRegionalConfiguration($empresa);
            }
        );
    }
}
