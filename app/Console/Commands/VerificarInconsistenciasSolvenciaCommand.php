<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Matricula;

class VerificarInconsistenciasSolvenciaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'solvencia:verificar-inconsistencias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar matrículas con inconsistencias entre estado de solvencia y cuotas reales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando inconsistencias en el estado de solvencia...');

        $matriculas = Matricula::with(['student', 'programa', 'paymentSchedules'])->get();
        $inconsistencias = [];

        $bar = $this->output->createProgressBar($matriculas->count());
        $bar->start();

        foreach ($matriculas as $matricula) {
            $cuotasVencidas = $matricula->paymentSchedules->where('estado', 'vencido')->count();
            $cuotasPendientes = $matricula->paymentSchedules->where('estado', 'pendiente')->count();
            $montoVencido = $matricula->paymentSchedules->where('estado', 'vencido')->sum('saldo_pendiente');
            $montoPendiente = $matricula->paymentSchedules->whereIn('estado', ['pendiente', 'vencido'])->sum('saldo_pendiente');

            // Verificar inconsistencias
            $tieneInconsistencia = false;
            $razones = [];

            if ($cuotasVencidas > 0 && $matricula->solvente) {
                $tieneInconsistencia = true;
                $razones[] = "Tiene {$cuotasVencidas} cuota(s) vencida(s)";
            }

            if ($montoVencido > 0 && $matricula->solvente) {
                $tieneInconsistencia = true;
                $razones[] = "Tiene monto vencido: " . number_format($montoVencido, 2);
            }

            if ($montoPendiente > 0 && $matricula->solvente) {
                $tieneInconsistencia = true;
                $razones[] = "Tiene monto pendiente: " . number_format($montoPendiente, 2);
            }

            if ($tieneInconsistencia) {
                $inconsistencias[] = [
                    'matricula' => $matricula,
                    'razones' => $razones,
                    'cuotas_vencidas' => $cuotasVencidas,
                    'cuotas_pendientes' => $cuotasPendientes,
                    'monto_vencido' => $montoVencido,
                    'monto_pendiente' => $montoPendiente
                ];
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);

        if (empty($inconsistencias)) {
            $this->info('✅ No se encontraron inconsistencias. Todas las matrículas tienen el estado de solvencia correcto.');
            return 0;
        }

        $this->warn("⚠️  Se encontraron " . count($inconsistencias) . " matrículas con inconsistencias:");
        $this->newLine();

        // Mostrar tabla con las inconsistencias
        $headers = ['ID Matrícula', 'Estudiante', 'Programa', 'Estado Actual', 'Cuotas Vencidas', 'Monto Vencido', 'Razones'];
        $rows = [];

        foreach ($inconsistencias as $inconsistencia) {
            $matricula = $inconsistencia['matricula'];
            $rows[] = [
                $matricula->id,
                $matricula->student->nombres . ' ' . $matricula->student->apellidos,
                $matricula->programa->nombre,
                $matricula->solvente ? 'Solvente' : 'No solvente',
                $inconsistencia['cuotas_vencidas'],
                number_format($inconsistencia['monto_vencido'], 2),
                implode('; ', $inconsistencia['razones'])
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Para corregir estas inconsistencias, ejecuta:');
        $this->info('php artisan solvencia:recalcular --todas');

        return 0;
    }
}
