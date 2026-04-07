<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RecoveryPeriod;
use Illuminate\Support\Facades\DB;

class RecoveryPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        RecoveryPeriod::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $empresa = \App\Models\Empresa::first();
        if (!$empresa) {
            $this->command->error('No se encontró ninguna empresa. Ejecute primero el seeder de empresas.');
            return;
        }

        $sucursal = $empresa->sucursales()->first();
        if (!$sucursal) {
            $this->command->error('No se encontró ninguna sucursal para la empresa. Ejecute primero el seeder de sucursales.');
            return;
        }

        $schoolPeriod = \App\Models\SchoolPeriod::where('empresa_id', $empresa->id)->first();
        if (!$schoolPeriod) {
            $this->command->error('No se encontró ningún período escolar. Ejecute primero el seeder de períodos escolares.');
            return;
        }

        $recoveryPeriods = [
            [
                'name' => 'Primer Período de Recuperación',
                'description' => 'Período de recuperación para estudiantes que no alcanzaron la nota mínima en el primer intento.',
                'start_date' => now()->addMonths(6)->startOfMonth(),
                'end_date' => now()->addMonths(6)->endOfMonth(),
                'registration_start_date' => now()->addMonths(6)->startOfMonth()->subWeek(),
                'registration_end_date' => now()->addMonths(6)->startOfMonth()->subDay(),
            ],
            [
                'name' => 'Segundo Período de Recuperación',
                'description' => 'Período de recuperación para estudiantes que no alcanzaron la nota mínima en el segundo intento.',
                'start_date' => now()->addYear()->startOfMonth(),
                'end_date' => now()->addYear()->endOfMonth(),
                'registration_start_date' => now()->addYear()->startOfMonth()->subWeek(),
                'registration_end_date' => now()->addYear()->startOfMonth()->subDay(),
            ],
        ];

        foreach ($recoveryPeriods as $period) {
            RecoveryPeriod::updateOrCreate(
                ['name' => $period['name'], 'empresa_id' => $empresa->id],
                array_merge($period, [
                    'empresa_id' => $empresa->id,
                    'sucursal_id' => $sucursal->id,
                    'school_period_id' => $schoolPeriod->id,
                ])
            );
        }
    }
}
