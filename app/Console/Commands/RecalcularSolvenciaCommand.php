<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Matricula;

class RecalcularSolvenciaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'solvencia:recalcular
                            {matricula_id? : ID específico de matrícula a recalcular}
                            {--todas : Recalcular solvencia de todas las matrículas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcular el estado de solvencia de las matrículas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando recálculo de solvencia...');

        $matriculaId = $this->argument('matricula_id');
        $todas = $this->option('todas');

        if ($matriculaId) {
            // Recalcular una matrícula específica
            $matricula = Matricula::find($matriculaId);
            if (!$matricula) {
                $this->error("Matrícula con ID {$matriculaId} no encontrada.");
                return 1;
            }

            $this->info("Recalculando solvencia para matrícula ID: {$matriculaId}");
            $this->info("Estudiante: {$matricula->estudiante->nombres} {$matricula->estudiante->apellidos}");

            $antes = $matricula->solvente ? 'Solvente' : 'No solvente';
            $matricula->updateSolvencia();
            $despues = $matricula->solvente ? 'Solvente' : 'No solvente';

            $this->info("Estado anterior: {$antes}");
            $this->info("Estado actual: {$despues}");

            if ($antes !== $despues) {
                $this->warn("⚠️  El estado de solvencia cambió!");
            }

        } elseif ($todas) {
            // Recalcular todas las matrículas
            $matriculas = Matricula::with(['estudiante', 'paymentSchedules'])->get();
            $total = $matriculas->count();

            if ($total === 0) {
                $this->warn('No hay matrículas para procesar.');
                return 0;
            }

            $this->info("Se procesarán {$total} matrículas.");

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $cambiadas = 0;
            $errores = 0;

            foreach ($matriculas as $matricula) {
                try {
                    $antes = $matricula->solvente;
                    $matricula->updateSolvencia();
                    $despues = $matricula->solvente;

                    if ($antes !== $despues) {
                        $cambiadas++;
                    }
                } catch (\Exception $e) {
                    $this->error("\nError procesando matrícula {$matricula->id}: " . $e->getMessage());
                    $errores++;
                }

                $bar->advance();
            }

            $bar->finish();

            $this->newLine(2);
            $this->info("Recálculo completado:");
            $this->info("- Total procesadas: {$total}");
            $this->info("- Solvencia cambiada: {$cambiadas}");
            $this->info("- Errores: {$errores}");

        } else {
            $this->warn('Debe especificar un ID de matrícula o usar la opción --todas');
            return 1;
        }

        $this->info('Recálculo de solvencia finalizado.');
        return 0;
    }
}
