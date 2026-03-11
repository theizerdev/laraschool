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
        if (Schema::hasTable('sections')) {
            return;
        }

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->unsignedBigInteger('sucursal_id')->nullable();
            $table->unsignedBigInteger('nivel_educativo_id');
            $table->unsignedBigInteger('programa_id');
            $table->string('nombre'); // Ej: "A", "B", "Unica"
            $table->string('codigo')->unique(); // Ej: "1A-2024", "SEC-A-2024"
            $table->text('descripcion')->nullable();
            $table->integer('capacidad_maxima')->default(30);
            $table->unsignedBigInteger('aula_asignada')->nullable();
            $table->unsignedBigInteger('turno_id')->nullable();
            $table->unsignedBigInteger('periodo_escolar_id');
            $table->unsignedBigInteger('profesor_guia_id')->nullable(); // Profesor tutor/guía
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('empresa_id');
            $table->index('sucursal_id');
            $table->index('nivel_educativo_id');
            $table->index('programa_id');
            $table->index('periodo_escolar_id');
            $table->index('turno_id');
            $table->index('profesor_guia_id');
            $table->index('is_active');
            $table->index('codigo');

            // Claves foráneas
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('set null');
            $table->foreign('nivel_educativo_id')->references('id')->on('niveles_educativos');
            $table->foreign('programa_id')->references('id')->on('programas');
            $table->foreign('aula_asignada')->references('id')->on('classrooms')->onDelete('set null');
            $table->foreign('turno_id')->references('id')->on('turnos')->onDelete('set null');
            $table->foreign('periodo_escolar_id')->references('id')->on('school_periods');
            $table->foreign('profesor_guia_id')->references('id')->on('teachers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
