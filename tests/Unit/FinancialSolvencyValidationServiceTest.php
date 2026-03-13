<?php

namespace Tests\Unit;

use App\Models\ConceptoPago;
use App\Models\EducationalLevel;
use App\Models\Empresa;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\PagoDetalle;
use App\Models\PaymentSchedule;
use App\Models\Programa;
use App\Models\SchoolPeriod;
use App\Models\Student;
use App\Models\Sucursal;
use App\Models\Turno;
use App\Models\User;
use App\Services\FinancialSolvencyValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialSolvencyValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function baseData(): array
    {
        $empresa = Empresa::create([
            'razon_social' => 'Demo',
            'documento' => 'J-00000000-0',
            'status' => true,
        ]);

        $sucursal = Sucursal::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Principal',
            'status' => true,
        ]);

        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
        ]);

        $nivel = EducationalLevel::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Primaria',
            'status' => true,
        ]);

        $programa = Programa::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Primaria A',
            'nivel_educativo_id' => $nivel->id,
            'activo' => true,
        ]);

        $turno = Turno::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Mañana',
            'descripcion' => 'Mañana',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '12:00:00',
            'status' => 1,
        ]);

        $periodoAnterior = SchoolPeriod::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'name' => '2025-2026',
            'start_date' => now()->subYear()->startOfMonth()->toDateString(),
            'end_date' => now()->subMonths(2)->endOfMonth()->toDateString(),
            'is_current' => false,
            'is_active' => true,
        ]);

        $periodoNuevo = SchoolPeriod::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'name' => '2026-2027',
            'start_date' => now()->addMonth()->startOfMonth()->toDateString(),
            'end_date' => now()->addYear()->endOfMonth()->toDateString(),
            'is_current' => true,
            'is_active' => true,
        ]);

        $student = Student::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'fecha_nacimiento' => now()->subYears(12)->toDateString(),
            'codigo' => 'STU00001',
            'documento_identidad' => 'V-12345678',
            'grado' => '1',
            'seccion' => 'A',
            'nivel_educativo_id' => $nivel->id,
            'turno_id' => $turno->id,
            'school_periods_id' => $periodoAnterior->id,
            'status' => true,
        ]);

        $matriculaAnterior = Matricula::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'estudiante_id' => $student->id,
            'programa_id' => $programa->id,
            'periodo_id' => $periodoAnterior->id,
            'fecha_matricula' => now()->subMonths(6)->toDateString(),
            'estado' => 'activo',
            'solvente' => true,
            'costo' => 100,
            'cuota_inicial' => 20,
            'numero_cuotas' => 4,
        ]);

        return compact('empresa', 'sucursal', 'user', 'nivel', 'programa', 'turno', 'periodoAnterior', 'periodoNuevo', 'student', 'matriculaAnterior');
    }

    public function test_estudiante_solvente_puede_matricular_siguiente_periodo(): void
    {
        $data = $this->baseData();

        PaymentSchedule::create([
            'matricula_id' => $data['matriculaAnterior']->id,
            'numero_cuota' => 0,
            'monto' => 20,
            'monto_pagado' => 20,
            'fecha_vencimiento' => now()->subMonths(6)->toDateString(),
            'estado' => 'pagado',
            'empresa_id' => $data['empresa']->id,
            'sucursal_id' => $data['sucursal']->id,
        ]);

        $service = app(FinancialSolvencyValidationService::class);
        $result = $service->validateForEnrollment(
            $data['student']->id,
            $data['periodoNuevo']->id,
            $data['empresa']->id,
            $data['sucursal']->id
        );

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['solvente']);
        $this->assertSame(0.0, (float) $result['total_adeudado']);
    }

    public function test_estudiante_con_deuda_parcial_no_puede_matricular(): void
    {
        $data = $this->baseData();

        PaymentSchedule::create([
            'matricula_id' => $data['matriculaAnterior']->id,
            'numero_cuota' => 1,
            'monto' => 100,
            'monto_pagado' => 50,
            'fecha_vencimiento' => now()->subDays(10)->toDateString(),
            'estado' => 'pendiente',
            'empresa_id' => $data['empresa']->id,
            'sucursal_id' => $data['sucursal']->id,
        ]);

        $service = app(FinancialSolvencyValidationService::class);
        $result = $service->validateForEnrollment(
            $data['student']->id,
            $data['periodoNuevo']->id,
            $data['empresa']->id,
            $data['sucursal']->id
        );

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['solvente']);
        $this->assertSame(50.0, (float) $result['total_adeudado']);
        $this->assertNotEmpty($result['detalles']);
    }

    public function test_estudiante_con_deuda_total_no_puede_matricular(): void
    {
        $data = $this->baseData();

        PaymentSchedule::create([
            'matricula_id' => $data['matriculaAnterior']->id,
            'numero_cuota' => 0,
            'monto' => 20,
            'monto_pagado' => 0,
            'fecha_vencimiento' => now()->subDays(30)->toDateString(),
            'estado' => 'pendiente',
            'empresa_id' => $data['empresa']->id,
            'sucursal_id' => $data['sucursal']->id,
        ]);

        PaymentSchedule::create([
            'matricula_id' => $data['matriculaAnterior']->id,
            'numero_cuota' => 2,
            'monto' => 80,
            'monto_pagado' => 0,
            'fecha_vencimiento' => now()->subDays(5)->toDateString(),
            'estado' => 'pendiente',
            'empresa_id' => $data['empresa']->id,
            'sucursal_id' => $data['sucursal']->id,
        ]);

        $service = app(FinancialSolvencyValidationService::class);
        $result = $service->validateForEnrollment(
            $data['student']->id,
            $data['periodoNuevo']->id,
            $data['empresa']->id,
            $data['sucursal']->id
        );

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['solvente']);
        $this->assertSame(100.0, (float) $result['total_adeudado']);
    }

    public function test_estudiante_con_pagos_pendientes_de_verificacion_no_puede_matricular(): void
    {
        $data = $this->baseData();

        $concepto = ConceptoPago::create([
            'empresa_id' => $data['empresa']->id,
            'sucursal_id' => $data['sucursal']->id,
            'nombre' => 'Biblioteca',
            'descripcion' => 'Biblioteca',
            'activo' => true,
        ]);

        $pago = Pago::create([
            'serie' => 'R001',
            'numero' => '00000001',
            'tipo_pago' => 'recibo',
            'fecha' => now()->toDateString(),
            'matricula_id' => $data['matriculaAnterior']->id,
            'user_id' => $data['user']->id,
            'subtotal' => 10,
            'descuento' => 0,
            'total' => 10,
            'metodo_pago' => 'transferencia',
            'referencia' => 'ABC',
            'estado' => 'pendiente',
            'observaciones' => null,
            'empresa_id' => $data['empresa']->id,
            'sucursal_id' => $data['sucursal']->id,
        ]);

        PagoDetalle::create([
            'pago_id' => $pago->id,
            'concepto_pago_id' => $concepto->id,
            'payment_schedule_id' => null,
            'descripcion' => 'Biblioteca',
            'cantidad' => 1,
            'precio_unitario' => 10,
        ]);

        $service = app(FinancialSolvencyValidationService::class);
        $result = $service->validateForEnrollment(
            $data['student']->id,
            $data['periodoNuevo']->id,
            $data['empresa']->id,
            $data['sucursal']->id
        );

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['solvente']);
        $this->assertSame(10.0, (float) $result['total_adeudado']);
        $this->assertNotEmpty($result['detalles']);
    }
}

