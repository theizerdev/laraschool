<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EvaluationType;
use Illuminate\Support\Facades\DB;

class EvaluationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        EvaluationType::truncate();
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

        $evaluationTypes = [
            ['name' => 'Parcial 1', 'code' => 'P1', 'description' => 'Primer examen parcial del período.'],
            ['name' => 'Parcial 2', 'code' => 'P2', 'description' => 'Segundo examen parcial del período.'],
            ['name' => 'Parcial 3', 'code' => 'P3', 'description' => 'Tercer examen parcial del período.'],
            ['name' => 'Examen Final', 'code' => 'EX_FIN', 'description' => 'Examen final que comprende toda la materia.'],
            ['name' => 'Actividad en Clase', 'code' => 'AC', 'description' => 'Evaluación continua basada en la participación y trabajos en clase.'],
            ['name' => 'Tarea', 'code' => 'TAREA', 'description' => 'Tareas y trabajos asignados para realizar fuera de clase.'],
        ];

        foreach ($evaluationTypes as $type) {
            EvaluationType::updateOrCreate(
                ['name' => $type['name'], 'empresa_id' => $empresa->id],
                array_merge($type, ['empresa_id' => $empresa->id, 'sucursal_id' => $sucursal->id])
            );
        }
    }
}
