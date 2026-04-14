<?php

namespace Database\Seeders;

use App\Models\Pago;
use App\Models\PagoDetalle;
use App\Models\Matricula;
use App\Models\ConceptoPago;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class PagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete all records from the tables to avoid duplicates
        DB::table('pago_detalles')->delete();
        DB::table('pagos')->delete();

        // Get required data
        $matriculas = Matricula::all();
        $conceptos = ConceptoPago::all();

        if ($matriculas->isEmpty() || $conceptos->isEmpty()) {
            $this->command->warn('No hay suficientes datos para crear pagos. Verifica que existan matrículas y conceptos de pago.');
            return;
        }

        // Get specific concepts
        $conceptoMatricula = $conceptos->where('nombre', 'Matrícula')->first();
        $conceptoCuotaInicial = $conceptos->where('nombre', 'Cuota Inicial')->first();
        $conceptoMensualidad = $conceptos->where('nombre', 'Mensualidad')->first();

        // Obtener usuario admin para asignar a los pagos
        $userId = DB::table('users')->where('email', 'admin@example.com')->value('id') ?? 1;

        // Create payments for each matricula
        foreach ($matriculas as $matricula) {
            // Create a single payment for the matricula
            $pago = Pago::create([
                'matricula_id' => $matricula->id,
                'user_id' => $userId,
                'serie' => '001',
                'numero' => str_pad($matricula->id, 8, '0', STR_PAD_LEFT),
                'tipo_pago' => Pago::TIPO_RECIBO,
                'fecha' => $matricula->fecha_matricula,
                'subtotal' => 0,
                'descuento' => 0,
                'total' => 0, // The total amount will be calculated from the details
                'metodo_pago' => 'efectivo',
                'referencia' => 'PAGO-' . strtoupper(uniqid()),
                'estado' => Pago::ESTADO_APROBADO, // Initial state
                'observaciones' => 'Pago generado por seeder',
                'empresa_id' => $matricula->empresa_id ?? 1,
                'sucursal_id' => $matricula->sucursal_id ?? 1,
            ]);

            $totalMonto = 0;

            // Create matricula payment detail
            if ($conceptoMatricula) {
                $montoMatricula = 50.00;
                PagoDetalle::create([
                    'pago_id' => $pago->id,
                    'concepto_pago_id' => $conceptoMatricula->id,
                    'descripcion' => 'Pago de Matrícula',
                    'cantidad' => 1,
                    'precio_unitario' => $montoMatricula,
                    'subtotal' => $montoMatricula,
                ]);
                $totalMonto += $montoMatricula;
            }

            // Create initial fee payment detail
            if ($conceptoCuotaInicial) {
                $montoCuotaInicial = $matricula->cuota_inicial;
                PagoDetalle::create([
                    'pago_id' => $pago->id,
                    'concepto_pago_id' => $conceptoCuotaInicial->id,
                    'descripcion' => 'Pago de Cuota Inicial',
                    'cantidad' => 1,
                    'precio_unitario' => $montoCuotaInicial,
                    'subtotal' => $montoCuotaInicial,
                ]);
                $totalMonto += $montoCuotaInicial;
            }

            // Create monthly payments (3 random payments)
            if ($conceptoMensualidad) {
                $montoMensualidad = $this->calculateMonthlyFee($matricula);
                for ($i = 1; $i <= 3; $i++) {
                    PagoDetalle::create([
                        'pago_id' => $pago->id,
                        'concepto_pago_id' => $conceptoMensualidad->id,
                        'descripcion' => "Pago de Mensualidad #{$i}",
                        'cantidad' => 1,
                        'precio_unitario' => $montoMensualidad,
                        'subtotal' => $montoMensualidad,
                    ]);
                    $totalMonto += $montoMensualidad;
                }
            }

            // Update the total amount of the payment
            $pago->update(['subtotal' => $totalMonto, 'total' => $totalMonto]);
        }
    }

    /**
     * Calculate the monthly fee
     */
    private function calculateMonthlyFee($matricula)
    {
        // Calculate the monthly fee based on total cost minus initial fee,
        // divided by the number of installments
        if ($matricula->numero_cuotas > 0) {
            return round(($matricula->costo - $matricula->cuota_inicial) / $matricula->numero_cuotas, 2);
        }

        return 0;
    }
}