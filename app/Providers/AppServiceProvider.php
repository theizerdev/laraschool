<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar MorosidadCalculationService
        $this->app->singleton(
            \App\Services\MorosidadCalculationService::class,
            \App\Services\MorosidadCalculationService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar observers
        \App\Models\Empresa::observe(\App\Observers\EmpresaObserver::class);
        \App\Models\Grade::observe(\App\Observers\GradeObserver::class);
        // Configurar vista de paginación personalizada para Livewire
        Paginator::defaultView('livewire.pagination');
        Paginator::defaultSimpleView('livewire.pagination');
    }
}
