<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Matricula;
use App\Models\Pago;
use Carbon\Carbon;

class VerificarMorosidad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'morosidad:verificar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar si el reporte de morosidad está mostrando los datos correctos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE MOROSIDAD ===');
        
        // Obtener todas las matrículas activas
        $matriculas = Matricula::with(['student', 'cronogramaPagos', 'pagos'])
            ->where('estado', 'activo')
            ->get();

        $this->info('Total de matrículas activas: ' . $matriculas->count());
        
        $fechaCorte = Carbon::now();
        $this->info('Fecha de corte para morosidad: ' . $fechaCorte->format('d/m/Y'));
        $this->newLine();

        $morosos = [];
        $noMorosos = [];
        $totalMontoVencido = 0;

        foreach ($matriculas as $matricula) {
            $this->line('Matrícula ID: ' . $matricula->id);
            $this->line('Estudiante: ' . ($matricula->student->nombres ?? 'N/A') . ' ' . ($matricula->student->apellidos ?? 'N/A'));
            
            // Obtener cuotas vencidas
            $cuotasVencidas = $matricula->cronogramaPagos
                ->where('fecha_vencimiento', '<=', $fechaCorte)
                ->where('estado', 'pendiente');
            
            $this->line('Cuotas totales: ' . $matricula->cronogramaPagos->count());
            $this->line('Cuotas vencidas (fecha <= ' . $fechaCorte->format('d/m/Y') . '): ' . $cuotasVencidas->count());
            
            $montoVencido = $cuotasVencidas->sum('monto');
            $this->line('Monto vencido: $' . number_format($montoVencido, 2));
            
            // Obtener pagos aprobados
            $totalPagado = Pago::where('matricula_id', $matricula->id)
                ->where('estado', 'aprobado')
                ->sum('total');
            
            $this->line('Total pagado: $' . number_format($totalPagado, 2));
            $this->line('Costo total de matrícula: $' . number_format($matricula->costo ?? 0, 2));
            
            $saldoPendiente = ($matricula->costo ?? 0) - $totalPagado;
            $this->line('Saldo pendiente total: $' . number_format($saldoPendiente, 2));
            
            // Verificar si es moroso
            $esMoroso = $montoVencido > 0;
            $this->line('¿Es moroso? ' . ($esMoroso ? 'SÍ' : 'NO'));
            
            if ($esMoroso) {
                $morosos[] = $matricula;
                $totalMontoVencido += $montoVencido;
                $this->info('✅ Agregado a morosos');
            } else {
                $noMorosos[] = $matricula;
                $this->info('❌ No es moroso');
            }
            
            $this->line('---');
            $this->newLine();
        }

        $this->info('=== RESUMEN ===');
        $this->info('Total de morosos: ' . count($morosos));
        $this->info('Total de no morosos: ' . count($noMorosos));
        $this->info('Porcentaje de morosidad: ' . ($matriculas->count() > 0 ? round((count($morosos) / $matriculas->count() * 100), 2) : 0) . '%');
        $this->info('Monto total vencido: $' . number_format($totalMontoVencido, 2));

        // Mostrar detalles de algunos morosos
        if (count($morosos) > 0) {
            $this->newLine();
            $this->info('=== EJEMPLOS DE MOROSOS ===');
            $ejemplos = array_slice($morosos, 0, 3);
            foreach ($ejemplos as $matricula) {
                $this->line('Matrícula ID: ' . $matricula->id);
                $this->line('Estudiante: ' . ($matricula->student->nombres ?? 'N/A') . ' ' . ($matricula->student->apellidos ?? 'N/A'));
                
                $cuotasVencidas = $matricula->cronogramaPagos
                    ->where('fecha_vencimiento', '<=', Carbon::now())
                    ->where('estado', 'pendiente');
                
                $this->line('Cuotas vencidas: ' . $cuotasVencidas->count());
                $this->line('Monto vencido: $' . number_format($cuotasVencidas->sum('monto'), 2));
                $this->line('---');
            }
        }

        return Command::SUCCESS;
    }
}