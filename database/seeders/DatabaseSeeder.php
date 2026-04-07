<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        $this->call([
            RolesAndPermissionsSeeder::class,
            WhatsAppPermissionsSeeder::class, // Agregar permisos específicos de WhatsApp
            PaisSeeder::class, // Agregar países antes que empresas
            EmpresaSeeder::class,
            SucursalSeeder::class,
            ShiftSeeder::class,
            EducationalLevelSeeder::class,
            SchoolPeriodSeeder::class,
            EvaluationPeriodSeeder::class, // Agregar seeder de lapsos de evaluación
            StudentSeeder::class,
            ProgramaSeeder::class,
            SubjectsSeeder::class,
            StudyPlansSeeder::class,
            ConceptoPagoMejoradoSeeder::class,
            BibliotecaCategoriasSeeder::class,
            BibliotecaArchivosSeeder::class,
            MensajeriaSeeder::class,
            UsersTableSeeder::class,
            TeacherSeeder::class, // Agregar seeder de profesores
            SubjectTeacherSeeder::class, // Asigna profesores a materias
            SerieSeeder::class,
            MatriculaSeeder::class,
            SubjectPrerequisitesSeeder::class,  //Agregar prerrequisitos de ejemplo
            EvaluationTypeSeeder::class,
            RecoveryPeriodSeeder::class,
            EvaluationPlanSeeder::class,
            GradesSeeder::class,
            //PagoSeeder::class,
        ]);
    }
}
