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
        if (Schema::hasTable('study_plan_subjects')) {
            return;
        }

        Schema::create('study_plan_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('study_plan_id'); // Plan de estudio
            $table->unsignedBigInteger('subject_id'); // Materia
            $table->integer('semester')->default(1); // Semestre en el que se cursa
            $table->integer('year')->default(1); // Año en el que se cursa
            $table->enum('subject_type', ['mandatory', 'elective', 'optional'])->default('mandatory'); // Tipo de materia
            $table->integer('order')->default(0); // Orden dentro del semestre
            $table->boolean('is_active')->default(true); // Estado de la asignación
            $table->timestamps();
            
            // Índices
            $table->index('study_plan_id');
            $table->index('subject_id');
            $table->index('semester');
            $table->index('year');
            $table->index('subject_type');
            $table->index('is_active');
            
            // Claves foráneas
            $table->foreign('study_plan_id')->references('id')->on('study_plans')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            
            // Índice único para evitar duplicados
            $table->unique(['study_plan_id', 'subject_id'], 'unique_study_plan_subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_plan_subjects');
    }
};
