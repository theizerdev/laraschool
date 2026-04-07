<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Programa;
use App\Models\EducationalLevel;

class SubjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener programas y niveles educativos existentes
        $programas = Programa::all();
        $educationalLevels = EducationalLevel::all();

        if ($programas->isEmpty() || $educationalLevels->isEmpty()) {
            $this->command->info('Se necesitan programas y niveles educativos creados para crear materias de ejemplo.');
            return;
        }

        $subjects = [
            [
                'code' => 'MAT101',
                'name' => 'Matemáticas Básica',
                'description' => 'Conceptos fundamentales de matemáticas, incluyendo álgebra básica, ecuaciones y funciones.',
                'credits' => 4,
                'hours_per_week' => 6,
                'program_id' => $programas->first()->id,
                'empresa_id' => $programas->first()->empresa_id,
                'sucursal_id' => $programas->first()->sucursal_id,
                'educational_level_id' => $educationalLevels->first()->id,
                'is_active' => true,
            ],
            [
                'code' => 'ALG101',
                'name' => 'Álgebra Lineal',
                'description' => 'Estudio de vectores, matrices, sistemas de ecuaciones lineales y espacios vectoriales.',
                'credits' => 3,
                'hours_per_week' => 4,
                 'program_id' => $programas->first()->id,
                'empresa_id' => $programas->first()->empresa_id,
                'sucursal_id' => $programas->first()->sucursal_id,
                'educational_level_id' => $educationalLevels->first()->id,
                'is_active' => true,
            ],
            [
                'code' => 'CAL101',
                'name' => 'Cálculo I',
                'description' => 'Introducción al cálculo diferencial, límites, derivadas y sus aplicaciones.',
                'credits' => 5,
                'hours_per_week' => 8,
                 'program_id' => $programas->first()->id,
                'empresa_id' => $programas->first()->empresa_id,
                'sucursal_id' => $programas->first()->sucursal_id,
                'educational_level_id' => $educationalLevels->first()->id,
                'is_active' => true,
            ],
            [
                'code' => 'CAL102',
                'name' => 'Cálculo II',
                'description' => 'Cálculo integral, técnicas de integración y aplicaciones del cálculo integral.',
                'credits' => 5,
                'hours_per_week' => 8,
                 'program_id' => $programas->first()->id,
                'empresa_id' => $programas->first()->empresa_id,
                'sucursal_id' => $programas->first()->sucursal_id,
                'educational_level_id' => $educationalLevels->first()->id,
                'is_active' => true,
            ],
            [
                'code' => 'FIS101',
                'name' => 'Física I',
                'description' => 'Mecánica clásica, cinemática, dinámica y leyes de conservación.',
                'credits' => 4,
                'hours_per_week' => 6,
                 'program_id' => $programas->first()->id,
                'empresa_id' => $programas->first()->empresa_id,
                'sucursal_id' => $programas->first()->sucursal_id,
                'educational_level_id' => $educationalLevels->first()->id,
                'is_active' => true,
            ],
            [
                'code' => 'PROG101',
                'name' => 'Programación I',
                'description' => 'Introducción a la programación, algoritmos básicos y estructuras de control.',
                'credits' => 4,
                'hours_per_week' => 6,
                 'program_id' => $programas->first()->id,
                'empresa_id' => $programas->first()->empresa_id,
                'sucursal_id' => $programas->first()->sucursal_id,
                'educational_level_id' => $educationalLevels->first()->id,
                'is_active' => true,
            ],
        ];

        foreach ($subjects as $subjectData) {
            Subject::firstOrCreate(
                ['code' => $subjectData['code']],
                $subjectData
            );
        }

        $this->command->info('Materias de ejemplo creadas exitosamente.');
    }
}
