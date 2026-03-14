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
            //ShiftSeeder::class,
            //EducationalLevelSeeder::class,
            //SchoolPeriodSeeder::class,
            //StudentSeeder::class,
            //ProgramaSeeder::class,
            //SubjectsSeeder::class,
            //StudyPlansSeeder::class,
           // ConceptoPagoMejoradoSeeder::class,
            //BibliotecaCategoriasSeeder::class,
            //BibliotecaArchivosSeeder::class,
            //MensajeriaSeeder::class,
            UsersTableSeeder::class,
            SerieSeeder::class,
            //MatriculaSeeder::class,
            //SubjectPrerequisitesSeeder::class, // Agregar prerrequisitos de ejemplo
            //PagoSeeder::class,
        ]);
    }
}