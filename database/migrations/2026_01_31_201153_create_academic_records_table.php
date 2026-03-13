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
        if (Schema::hasTable('academic_records')) {
            return;
        }

        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('school_period_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('educational_level_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            
            // Información académica
            $table->integer('grade')->nullable();
            $table->string('section', 10)->nullable();
            $table->string('status', 50)->default('enrolled'); // enrolled, completed, failed, withdrawn
            
            // Calificaciones
            $table->decimal('first_partial_grade', 5, 2)->nullable();
            $table->decimal('second_partial_grade', 5, 2)->nullable();
            $table->decimal('third_partial_grade', 5, 2)->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->decimal('average_grade', 5, 2)->nullable();
            
            // Estados de promoción y recuperación
            $table->boolean('promoted')->default(false);
            $table->boolean('repeated')->default(false);
            $table->boolean('in_recovery')->default(false);
            $table->boolean('recovered')->default(false);
            
            // Observaciones
            $table->text('observations')->nullable();
            $table->text('teacher_observations')->nullable();
            
            // Control de asistencia
            $table->integer('total_classes')->default(0);
            $table->integer('attended_classes')->default(0);
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            
            // Fechas importantes
            $table->date('enrollment_date')->nullable();
            $table->date('completion_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices (sin claves foráneas temporalmente)
            $table->index(['student_id', 'school_period_id', 'subject_id']);
            $table->index(['school_period_id', 'program_id', 'educational_level_id'], 'idx_academic_period_program_level');
            $table->index(['empresa_id', 'sucursal_id']);
            $table->index('status');
            $table->index('promoted');
            $table->index('repeated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_records');
    }
};
