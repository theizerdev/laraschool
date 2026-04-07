<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Matricula;
use App\Models\PaymentSchedule;

class VerificarCuotasMatriculaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cuotas:verificar
                            {matricula_id : ID de la matrícula a verificar}
                            {--detalle : Mostrar detalle de cada cuota}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar el estado de las cuotas de una matrícula específica';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $matriculaId = $this->argument('matricula_id');
        $mostrarDetalle = $this->option('detalle');

        $matricula = Matricula::with(['student', 'programa', 'paymentSchedules'])->find($matriculaId);

        if (!$matricula) {
            $this->error("Matrícula con ID {$matriculaId} no encontrada.");
            return 1;
        }

        $this->info("=== INFORMACIÓN DE LA MATRÍCULA ===");
        $this->info("ID: {$matricula->id}");
        $this->info("Estudiante: {$matricula->student->nombres} {$matricula->student->apellidos}");
        $this->info("Programa: {$matricula->programa->nombre}");
        $this->info("Estado: {$matricula->estado}");
        $this->info("Solvente: " . ($matricula->solvente ? 'Sí' : 'No'));
        $this->info("Fecha de matrícula: {$matricula->fecha_matricula->format('d/m/Y')}");

        $this->newLine();
        $this->info("=== RESUMEN DE CUOTAS ===");

        $totalCuotas = $matricula->paymentSchedules->count();
        $cuotasPendientes = $matricula->paymentSchedules->where('estado', 'pendiente')->count();
        $cuotasVencidas = $matricula->paymentSchedules->where('estado', 'vencido')->count();
        $cuotasPagadas = $matricula->paymentSchedules->where('estado', 'pagado')->count();

        $this->info("Total de cuotas: {$totalCuotas}");
        $this->info("Cuotas pagadas: {$cuotasPagadas}");
        $this->info("Cuotas pendientes: {$cuotasPendientes}");
        $this->info("Cuotas vencidas: {$cuotasVencidas}");

        // Calcular montos
        $montoTotal = $matricula->paymentSchedules->sum('monto');
        $montoPagado = $matricula->paymentSchedules->sum('monto_pagado');
        $montoPendiente = $matricula->paymentSchedules->whereIn('estado', ['pendiente', 'vencido'])->sum('saldo_pendiente');
        $montoVencido = $matricula->paymentSchedules->where('estado', 'vencido')->sum('saldo_pendiente');

        $this->newLine();
        $this->info("=== RESUMEN FINANCIERO ===");
        $this->info("Monto total: " . number_format($montoTotal, 2));
        $this->info("Monto pagado: " . number_format($montoPagado, 2));
        $this->info("Monto pendiente: " . number_format($montoPendiente, 2));
        $this->info("Monto vencido: " . number_format($montoVencido, 2));

        if ($mostrarDetalle) {
            $this->newLine();
            $this->info("=== DETALLE DE CUOTAS ===");

            $headers = ['#', 'Vencimiento', 'Monto', 'Pagado', 'Pendiente', 'Estado'];
            $rows = [];

            foreach ($matricula->paymentSchedules->sortBy('numero_cuota') as $cuota) {
                $rows[] = [
                    $cuota->numero_cuota,
                    $cuota->fecha_vencimiento->format('d/m/Y'),
                    number_format($cuota->monto, 2),
                    number_format($cuota->monto_pagado, 2),
                    number_format($cuota->saldo_pendiente, 2),
                    strtoupper($cuota->estado)
                ];
            }

            $this->table($headers, $rows);
        }

        // Verificar si hay inconsistencias
        $this->newLine();
        $this->info("=== VERIFICACIÓN DE INCONSISTENCIAS ===");

        $inconsistencias = [];

        if ($cuotasVencidas > 0 && $matricula->solvente) {
            $inconsistencias[] = "⚠️  La matrícula tiene cuotas vencidas pero aparece como solvente";
        }

        if ($montoVencido > 0 && $matricula->solvente) {
            $inconsistencias[] = "⚠️  Hay monto vencido (" . number_format($montoVencido, 2) . ") pero la matrícula aparece como solvente";
        }

        if ($montoPendiente > 0 && $matricula->solvente) {
            $inconsistencias[] = "⚠️  Hay monto pendiente (" . number_format($montoPendiente, 2) . ") pero la matrícula aparece como solvente";
        }

        if (empty($inconsistencias)) {
            $this->info("✅ No se detectaron inconsistencias");
        } else {
            foreach ($inconsistencias as $inconsistencia) {
                $this->error($inconsistencia);
            }
        }

        return 0;
    }
}
