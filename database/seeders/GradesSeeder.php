<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\Matricula;
use App\Models\Evaluation;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class GradesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Grade::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $empresa = \App\Models\Empresa::first();
        if (!$empresa) {
            $this->command->error('No se encontró ninguna empresa. Ejecute primero el seeder de empresas.');
            return;
        }

        $matriculas = Matricula::where('empresa_id', $empresa->id)->with('programa.subjects', 'student')->get();
        $teachers = Teacher::where('empresa_id', $empresa->id)->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No hay docentes (Teachers) en la base de datos. Asignando calificaciones sin docente.');
        }

        foreach ($matriculas as $matricula) {
            $subjects = $matricula->programa->subjects;
            $student = $matricula->student;

            foreach ($subjects as $subject) {
                $evaluations = Evaluation::where('subject_id', $subject->id)
                                        ->whereHas('evaluationPeriod', function ($query) use ($matricula) {
                                            $query->where('school_period_id', $matricula->periodo_id);
                                        })
                                        ->get();

                foreach ($evaluations as $evaluation) {
                    // Asignar un profesor aleatorio si hay disponibles
                    $teacherId = !$teachers->isEmpty() ? $teachers->random()->id : null;

                    Grade::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'evaluation_id' => $evaluation->id,
                        ],
                        [
                            'empresa_id' => $empresa->id,
                            'sucursal_id' => $matricula->sucursal_id,
                            'matricula_id' => $matricula->id,

                            'score' => rand(5, 20), // Calificación aleatoria entre 5 y 20
                            'observations' => 'Calificación generada por seeder.',
                        ]
                    );
                }
            }
        }
    }
}
