<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Evaluation;
use App\Models\EvaluationType;
use App\Models\EvaluationPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

class EvaluationPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Evaluation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $empresa = Empresa::first();
        if (!$empresa) {
            $this->command->error('No se encontró ninguna empresa. Ejecute primero el seeder de empresas.');
            return;
        }

        $sucursal = $empresa->sucursales()->first();
        if (!$sucursal) {
            $this->command->error('No se encontró ninguna sucursal para la empresa. Ejecute su seeder.');
            return;
        }

        // Eliminar la búsqueda de un profesor aleatorio global

        // Correctly fetch subjects via programs associated with the empresa, and eager load teachers
        $subjects = Subject::with('teachers')->whereHas('program', function ($query) use ($empresa) {
            $query->where('empresa_id', $empresa->id);
        })->get();

        $evaluationPeriods = EvaluationPeriod::where('empresa_id', $empresa->id)->get();
        $evaluationTypes = EvaluationType::where('empresa_id', $empresa->id)->get()->pluck('id', 'name');

        if ($subjects->isEmpty()) {
            $this->command->warn('No se encontraron materias para la empresa. Saltando EvaluationPlanSeeder.');
            return;
        }
        if ($evaluationPeriods->isEmpty()) {
            $this->command->warn('No se encontraron períodos de evaluación para la empresa. Saltando EvaluationPlanSeeder.');
            return;
        }
        if ($evaluationTypes->isEmpty()) {
            $this->command->warn('No se encontraron tipos de evaluación para la empresa. Saltando EvaluationPlanSeeder.');
            return;
        }

        $defaultPlan = [
            ['type' => 'Parcial 1', 'weight' => 20],
            ['type' => 'Parcial 2', 'weight' => 20],
            ['type' => 'Parcial 3', 'weight' => 20],
            ['type' => 'Examen Final', 'weight' => 40],
        ];

        foreach ($subjects as $subject) {
            // Obtener el primer profesor asignado a esta materia específica
            $teacher = $subject->teachers->first();

            // Si no hay profesor asignado a esta materia, se notifica y se salta.
            if (!$teacher) {
                $this->command->warn('La materia ' . $subject->name . ' (ID: ' . $subject->id . ') no tiene profesores asignados. Saltando la creación de su plan de evaluación.');
                continue; // Pasar a la siguiente materia
            }

            foreach ($evaluationPeriods as $period) {
                foreach ($defaultPlan as $planItem) {
                    if (isset($evaluationTypes[$planItem['type']])) {
                        Evaluation::updateOrCreate(
                            [
                                'empresa_id' => $empresa->id,
                                'subject_id' => $subject->id,
                                'evaluation_period_id' => $period->id,
                                'evaluation_type_id' => $evaluationTypes[$planItem['type']],
                            ],
                            [
                                'sucursal_id' => $sucursal->id,
                                'teacher_id' => $teacher->id, // Ahora $teacher está definida
                                'name' => $planItem['type'] . ' - ' . $subject->name,
                                'description' => 'Evaluación de ' . $planItem['type'] . ' para la materia ' . $subject->name,
                                'weight' => $planItem['weight'],
                                'evaluation_date' => $this->getEvaluationDate($period, $planItem['type']),
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Determina una fecha de evaluación basada en el tipo y el período.
     */
    private function getEvaluationDate(EvaluationPeriod $period, string $type): string
    {
        switch ($type) {
            case 'Parcial 1':
                return (new \DateTime($period->start_date))->modify('+1 month')->format('Y-m-d');
            case 'Parcial 2':
                return (new \DateTime($period->start_date))->modify('+2 months')->format('Y-m-d');
            case 'Parcial 3':
                return (new \DateTime($period->start_date))->modify('+3 months')->format('Y-m-d');
            case 'Examen Final':
                return (new \DateTime($period->end_date))->format('Y-m-d');
            default:
                return (new \DateTime($period->start_date))->modify('+1 week')->format('Y-m-d');
        }
    }
}
