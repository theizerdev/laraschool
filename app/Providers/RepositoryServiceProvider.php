<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\MatriculaRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\CronogramaPagoRepository;
use App\Services\MatriculaService;
use App\Repositories\Contracts\MatriculaRepositoryInterface;
use App\Repositories\Contracts\EstudianteRepositoryInterface;
use App\Repositories\Contracts\CronogramaPagoRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Registrar los repositorios como singleton
        $this->app->singleton(
            MatriculaRepositoryInterface::class,
            MatriculaRepository::class
        );
        
        $this->app->singleton(
            EstudianteRepositoryInterface::class,
            EstudianteRepository::class
        );
        
        $this->app->singleton(
            CronogramaPagoRepositoryInterface::class,
            CronogramaPagoRepository::class
        );
        
        // Registrar servicios
        $this->app->singleton(
            MatriculaService::class,
            function($app) {
                return new MatriculaService(
                    $app->make(MatriculaRepositoryInterface::class),
                    $app->make(EstudianteRepositoryInterface::class),
                    $app->make(CronogramaPagoRepositoryInterface::class)
                );
            }
        );
    }

    public function boot()
    {
        //
    }
}