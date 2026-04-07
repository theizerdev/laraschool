<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

class SubjectTeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Desactivar la revisión de claves foráneas para truncar la tabla pivote
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('subject_teacher')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $empresa = Empresa::first();
        if (!$empresa) {
            $this->command->error('No se encontró ninguna empresa. Ejecute primero el seeder de empresas.');
            return;
        }

        $subjects = Subject::whereHas('program', function ($query) use ($empresa) {
            $query->where('empresa_id', $empresa->id);
        })->get();

        $teachers = Teacher::where('empresa_id', $empresa->id)->get();

        if ($teachers->isEmpty() || $subjects->isEmpty()) {
            $this->command->warn('No se encontraron materias o profesores para la empresa. Saltando SubjectTeacherSeeder.');
            return;
        }

        // Asignar cada materia a al menos un profesor de forma aleatoria
        foreach ($subjects as $subject) {
            // El método 'random()' puede devolver un solo modelo o una colección si se le pasa un número.
            // Nos aseguramos de tener al menos un profesor y luego lo asociamos.
            $randomTeacher = $teachers->random();

            // El método attach() es ideal para añadir registros a una tabla pivote.
            // No necesitamos verificar si ya existe la relación porque hemos truncado la tabla.
            $subject->teachers()->attach($randomTeacher->id, [
                'assigned_date' => now(),
                'academic_period' => '2024-2025', // Puedes hacerlo más dinámico si es necesario
                'is_primary' => true
            ]);
        }

        $this->command->info(count($subjects) . ' materias han sido asignadas a profesores.');
    }
}
