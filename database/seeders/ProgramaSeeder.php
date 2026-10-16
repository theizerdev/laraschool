<?php

namespace Database\Seeders;

use App\Models\Programa;
use App\Models\EducationalLevel;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class ProgramaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete all records from the programas table to avoid duplicates
        DB::table('programas')->delete();

        // Get educational levels
        $niveles = EducationalLevel::all();

        if ($niveles->isEmpty()) {
            $this->command->warn('No hay niveles educativos disponibles. Ejecuta EducationalLevelSeeder primero.');
            return;
        }

        // Get specific levels
        $nivelInicial = $niveles->where('nombre', 'Educación Inicial')->first();
        $nivelPrimaria = $niveles->where('nombre', 'Primaria')->first();
        $nivelSecundaria = $niveles->where('nombre', 'Secundaria')->first();

        $programas = [
            [
                'nombre' => 'Educación Inicial - Mañana',
                'descripcion' => 'Programa de educación inicial para niños menores de 8 años en turno mañana',
                'nivel_educativo_id' => $nivelInicial ? $nivelInicial->id : 1,
                'costo_matricula' => 50.00,
                'costo_mensualidad' => 100.00,
                'activo' => true,
                'empresa_id' => 1,
                'sucursal_id' => 1,
            ],
            [
                'nombre' => 'Educación Inicial - Tarde',
                'descripcion' => 'Programa de educación inicial para niños menores de 8 años en turno tarde',
                'nivel_educativo_id' => $nivelInicial ? $nivelInicial->id : 1,
                'costo_matricula' => 50.00,
                'costo_mensualidad' => 100.00,
                'activo' => true,
                'empresa_id' => 1,
                'sucursal_id' => 1,
            ],
            [
                'nombre' => 'Primaria - Mañana',
                'descripcion' => 'Programa de educación primaria en turno mañana',
                'nivel_educativo_id' => $nivelPrimaria ? $nivelPrimaria->id : 2,
                'costo_matricula' => 50.00,
                'costo_mensualidad' => 125.00,
                'activo' => true,
                'empresa_id' => 1,
                'sucursal_id' => 1,
            ],
            [
                'nombre' => 'Primaria - Tarde',
                'descripcion' => 'Programa de educación primaria en turno tarde',
                'nivel_educativo_id' => $nivelPrimaria ? $nivelPrimaria->id : 2,
                'costo_matricula' => 50.00,
                'costo_mensualidad' => 125.00,
                'activo' => true,
                'empresa_id' => 1,
                'sucursal_id' => 1,
            ],
            [
                'nombre' => 'Secundaria - Mañana',
                'descripcion' => 'Programa de educación secundaria en turno mañana',
                'nivel_educativo_id' => $nivelSecundaria ? $nivelSecundaria->id : 3,
                'costo_matricula' => 50.00,
                'costo_mensualidad' => 150.00,
                'activo' => true,
                'empresa_id' => 1,
                'sucursal_id' => 1,
            ],
            [
                'nombre' => 'Secundaria - Tarde',
                'descripcion' => 'Programa de educación secundaria en turno tarde',
                 'nivel_educativo_id' => $nivelSecundaria ? $nivelSecundaria->id : 3,
               'costo_matricula' => 50.00,
               'costo_mensualidad' => 150.00,
                'activo' => true,
                'empresa_id' => 1,
                'sucursal_id' => 1,
            ],
        ];

        foreach ($programas as $programa) {
            Programa::create($programa);
        }
    }
}
