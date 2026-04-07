<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Matricula;
use App\Services\MorosidadCalculationService;

class RecalcularMorosidadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'morosidad:recalcular
                            {--matricula_id= : ID específico de matrícula a recalcular}
                            {--fecha_corte= : Fecha de corte para el cálculo (formato: Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcular el estado de morosidad de las matrículas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando recálculo de morosidad...');

        $matriculaId = $this->option('matricula_id');
        $fechaCorte = $this->option('fecha_corte') ? \Carbon\Carbon::parse($this->option('fecha_corte')) : now();

        if ($matriculaId) {
            // Recalcular una matrícula específica
            $matricula = Matricula::find($matriculaId);
            if (!$matricula) {
                $this->error("Matrícula con ID {$matriculaId} no encontrada.");
                return 1;
            }

            $this->info("Recalculando morosidad para matrícula ID: {$matriculaId}");
            $resultado = $matricula->updateMorosidad();

            if (!empty($resultado)) {
                $this->info("Morosidad actualizada:");
                $this->info("- Estado: {$resultado['estado']}");
                $this->info("- Saldo pendiente: {$resultado['saldo_pendiente_rango']}");
                $this->info("- Monto vencido: {$resultado['monto_vencido']}");
                $this->info("- Cuotas pendientes: {$resultado['cantidad_cuotas']}");
            } else {
                $this->warn("No se pudo calcular la morosidad para esta matrícula.");
            }
        } else {
            // Recalcular todas las matrículas
            $matriculas = Matricula::with(['estudiante', 'programa'])->get();
            $total = $matriculas->count();

            if ($total === 0) {
                $this->warn('No hay matrículas para procesar.');
                return 0;
            }

            $this->info("Se procesarán {$total} matrículas.");

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $procesadas = 0;
            $errores = 0;

            foreach ($matriculas as $matricula) {
                try {
                    $matricula->updateMorosidad();
                    $procesadas++;
                } catch (\Exception $e) {
                    $this->error("\nError procesando matrícula {$matricula->id}: " . $e->getMessage());
                    $errores++;
                }

                $bar->advance();
            }

            $bar->finish();

            $this->newLine(2);
            $this->info("Recálculo completado:");
            $this->info("- Procesadas: {$procesadas}");
            $this->info("- Errores: {$errores}");
        }

        $this->info('Recálculo de morosidad finalizado.');
        return 0;
    }
}
