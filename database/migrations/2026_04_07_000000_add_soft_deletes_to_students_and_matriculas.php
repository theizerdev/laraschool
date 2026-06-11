<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar deleted_at a la tabla de estudiantes
        Schema::table('students', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Agregar deleted_at a la tabla de matrículas
        Schema::table('matriculas', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar deleted_at de la tabla de estudiantes
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Eliminar deleted_at de la tabla de matrículas
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
