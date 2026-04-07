<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Grade;
use App\Observers\GradeObserver;

class SyncAcademicRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-academic-records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los registros académicos a partir de las calificaciones existentes.';

    /**
     * Execute the console command.
     */
    public function handle(GradeObserver $observer)
    {
        $this->info('Iniciando la sincronización de registros académicos...');

        $grades = Grade::all();
        $count = $grades->count();

        if ($count === 0) {
            $this->info('No hay calificaciones para sincronizar.');
            return;
        }

        $this->info("Se encontraron {$count} calificaciones para procesar.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($grades as $grade) {
            $observer->updated($grade);
            $bar->advance();
        }

        $bar->finish();
        $this->info('\nSincronización completada con éxito.');
    }
}
