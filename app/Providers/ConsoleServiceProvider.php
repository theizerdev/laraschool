<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    protected $commands = [
        \App\Console\Commands\CleanupOldReports::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands($this->commands);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}