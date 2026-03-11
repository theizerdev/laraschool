<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Matricula;
use App\Models\PaymentSchedule;

class VerificarMatriculasPendientesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matriculas:verificar-pendientes {estudiante? : Documento de identidad del estudiante}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica qué matrículas tienen cuotas pendientes para un estudiante';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documento = $this->argument('estudiante');

        if ($documento) {
            // Buscar estudiante específico
            $matriculas = Matricula::with(['estudiante', 'programa', 'periodo', 'paymentSchedules'])
                ->whereHas('estudiante', function($q) use ($documento) {
                    $q->where('documento_identidad', $documento);
                })
                ->whereHas('paymentSchedules', function($q) {
                    $q->where('estado', 'pendiente');
                })
                ->get();

            if ($matriculas->isEmpty()) {
                $this->info("No se encontraron matrículas con cuotas pendientes para el estudiante con documento: {$documento}");
                return;
            }

            $this->info("Matrículas con cuotas pendientes para el estudiante {$documento}:");
            $this->newLine();

            foreach ($matriculas as $matricula) {
                $this->mostrarInfoMatricula($matricula);
            }
        } else {
            // Mostrar todas las matrículas con cuotas pendientes (limitado a 20)
            $matriculas = Matricula::with(['estudiante', 'programa', 'periodo', 'paymentSchedules'])
                ->whereHas('paymentSchedules', function($q) {
                    $q->where('estado', 'pendiente');
                })
                ->limit(20)
                ->get();

            if ($matriculas->isEmpty()) {
                $this->info("No se encontraron matrículas con cuotas pendientes.");
                return;
            }

            $this->info("Matrículas con cuotas pendientes (mostrando primeras 20):");
            $this->newLine();

            foreach ($matriculas as $matricula) {
                $this->mostrarInfoMatricula($matricula);
            }
        }
    }

    private function mostrarInfoMatricula($matricula)
    {
        $estudiante = $matricula->estudiante;
        $cuotasPendientes = $matricula->paymentSchedules->where('estado', 'pendiente');
        $totalPendiente = $cuotasPendientes->sum('saldo_pendiente');

        $this->line("Matrícula ID: {$matricula->id}");
        $this->line("Estudiante: {$estudiante->nombres} {$estudiante->apellidos}");
        $this->line("Documento: {$estudiante->documento_identidad}");
        $this->line("Programa: {$matricula->programa->nombre}");
        $this->line("Período: {$matricula->periodo->nombre}");
        $this->line("Cuotas pendientes: {$cuotasPendientes->count()}");
        $this->line("Total pendiente: $ {$totalPendiente}");
        $this->line("Estado matrícula: {$matricula->estado}");
        $this->line("Solvente: " . ($matricula->solvente ? 'SI' : 'NO'));
        $this->newLine();
    }
}