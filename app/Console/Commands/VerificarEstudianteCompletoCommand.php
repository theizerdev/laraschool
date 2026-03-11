<?php

namespace App\Console\Commands;

use App\Models\Matricula;
use Illuminate\Console\Command;

class VerificarEstudianteCompletoCommand extends Command
{
    protected $signature = 'estudiante:verificar-completo {documento : Documento de identidad del estudiante}';
    
    protected $description = 'Verifica el estado completo de todas las matrículas de un estudiante incluyendo sus cuotas';

    public function handle()
    {
        $documento = $this->argument('documento');
        
        $this->info("🔍 VERIFICACIÓN COMPLETA DEL ESTUDIANTE: {$documento}");
        $this->info("═══════════════════════════════════════════════════════════════");
        $this->newLine();
        
        // Buscar todas las matrículas del estudiante
        $matriculas = Matricula::with([
            'estudiante', 
            'programa', 
            'periodo', 
            'paymentSchedules' => function($query) {
                $query->orderBy('fecha_vencimiento');
            }
        ])
        ->whereHas('estudiante', function($q) use ($documento) {
            $q->where('documento_identidad', $documento);
        })
        ->orderBy('id')
        ->get();
        
        if ($matriculas->isEmpty()) {
            $this->error("No se encontraron matrículas para el estudiante con documento: {$documento}");
            return;
        }
        
        foreach ($matriculas as $matricula) {
            $this->info("📋 MATRÍCULA #{$matricula->id}");
            $this->info("├─ Estudiante: {$matricula->estudiante->nombre_completo}");
            $this->info("├─ Programa: {$matricula->programa->nombre}");
            $this->info("├─ Periodo: {$matricula->periodo->nombre}");
            $this->info("├─ Fecha: {$matricula->fecha_matricula}");
            $this->info("├─ Estado: {$matricula->estado}");
            $this->info("├─ Es Solvente: " . ($matricula->solvente ? '✅ SÍ' : '❌ NO'));
            $this->newLine();
            
            // Verificar cuotas
            $cuotas = $matricula->paymentSchedules;
            
            if ($cuotas->isEmpty()) {
                $this->warn("⚠️  No hay cuotas asociadas a esta matrícula");
            } else {
                $this->info("💳 CUOTAS:");
                
                foreach ($cuotas as $cuota) {
                    $estado = $cuota->estado === 'pagado' ? '✅ Pagado' : '❌ Pendiente';
                    $this->info("├─ Cuota #{$cuota->id}: {$cuota->concepto} - $ {$cuota->monto} - Vence: {$cuota->fecha_vencimiento} - {$estado}");
                }
                
                $cuotasPendientes = $cuotas->where('estado', 'pendiente');
                $this->info("│");
                $this->info("├─ Total cuotas: {$cuotas->count()}");
                $this->info("├─ Pendientes: {$cuotasPendientes->count()}");
                $this->info("└─ Total pendiente: $ {$cuotasPendientes->sum('saldo_pendiente')}");
            }
            
            $this->newLine();
            $this->info("═══════════════════════════════════════════════════════════════");
            $this->newLine();
        }
        
        // Resumen final
        $totalMatriculas = $matriculas->count();
        $matriculasNoSolventes = $matriculas->where('solvente', false)->count();
        $matriculasConCuotasPendientes = $matriculas->filter(function ($matricula) {
            return $matricula->paymentSchedules->where('estado', 'pendiente')->isNotEmpty();
        })->count();
        
        $this->info("📊 RESUMEN GENERAL:");
        $this->info("├─ Total de matrículas: {$totalMatriculas}");
        $this->info("├─ Matrículas no solventes: {$matriculasNoSolventes}");
        $this->info("├─ Matrículas con cuotas pendientes: {$matriculasConCuotasPendientes}");
        $this->info("└─ ¿Debería aparecer en pagos? " . ($matriculasConCuotasPendientes > 0 ? '✅ SÍ' : '❌ NO'));
    }
}