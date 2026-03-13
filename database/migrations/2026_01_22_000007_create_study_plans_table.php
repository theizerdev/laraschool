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
        if (Schema::hasTable('study_plans')) {
            return;
        }

        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del plan de estudio
            $table->string('code')->unique(); // Código único del plan
            $table->text('description')->nullable(); // Descripción del plan
            $table->unsignedBigInteger('program_id'); // Programa al que pertenece
            $table->unsignedBigInteger('educational_level_id'); // Nivel educativo
            $table->integer('total_credits')->default(0); // Créditos totales del plan
            $table->integer('total_hours')->default(0); // Horas totales del plan
            $table->integer('duration_years')->default(0); // Duración en años
            $table->integer('duration_semesters')->default(0); // Duración en semestres
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft'); // Estado del plan
            $table->date('effective_date')->nullable(); // Fecha de vigencia
            $table->date('expiration_date')->nullable(); // Fecha de expiración
            $table->boolean('is_default')->default(false); // Si es el plan por defecto para el programa
            $table->unsignedBigInteger('created_by')->nullable(); // Usuario que creó el plan
            $table->unsignedBigInteger('updated_by')->nullable(); // Usuario que actualizó el plan
            $table->timestamps();
            
            // Índices
            $table->index('program_id');
            $table->index('educational_level_id');
            $table->index('status');
            $table->index('is_default');
            $table->index('code');
            
            // Claves foráneas
            $table->foreign('program_id')->references('id')->on('programas')->onDelete('cascade');
            $table->foreign('educational_level_id')->references('id')->on('niveles_educativos')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_plans');
    }
};
