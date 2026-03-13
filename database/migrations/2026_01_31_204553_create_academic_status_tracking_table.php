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
        if (Schema::hasTable('academic_status_tracking')) {
            return;
        }

        Schema::create('academic_status_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('matricula_id');
            $table->unsignedBigInteger('school_period_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('educational_level_id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            
            // Estado académico del período
            $table->string('academic_status', 50)->default('active'); // active, probation, suspended, completed, withdrawn
            $table->decimal('period_average', 5, 2)->nullable(); // Promedio del período
            $table->integer('total_subjects')->default(0); // Total de materias
            $table->integer('approved_subjects')->default(0); // Materias aprobadas
            $table->integer('failed_subjects')->default(0); // Materias reprobadas
            $table->integer('in_recovery_subjects')->default(0); // Materias en recuperación
            
            // Rendimiento académico
            $table->string('performance_level', 20)->nullable(); // excellent, good, average, poor
            $table->decimal('attendance_percentage', 5, 2)->nullable(); // Porcentaje de asistencia
            $table->string('conduct_grade', 10)->nullable(); // Calificación de conducta
            
            // Decisiones académicas
            $table->boolean('promoted')->default(false); // Si fue promovido
            $table->boolean('repeated')->default(false); // Si repitió el período
            $table->boolean('graduated')->default(false); // Si se graduó
            $table->boolean('withdrawn')->default(false); // Si se retiró
            
            // Fechas importantes
            $table->date('enrollment_date');
            $table->date('completion_date')->nullable(); // Fecha de finalización
            $table->date('withdrawal_date')->nullable(); // Fecha de retiro
            
            // Observaciones y notas
            $table->text('academic_observations')->nullable();
            $table->text('disciplinary_observations')->nullable();
            $table->text('recommendations')->nullable();
            
            // Estado del tracking
            $table->string('status', 50)->default('active'); // active, closed, cancelled
            $table->unsignedBigInteger('reviewed_by')->nullable(); // Usuario que revisó
            $table->date('review_date')->nullable(); // Fecha de revisión
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->unique(['student_id', 'matricula_id', 'school_period_id'], 'idx_academic_status_unique');
            $table->index(['student_id', 'school_period_id'], 'idx_student_period');
            $table->index(['matricula_id', 'academic_status'], 'idx_matricula_status');
            $table->index(['program_id', 'educational_level_id'], 'idx_program_level');
            $table->index(['empresa_id', 'sucursal_id']);
            $table->index('academic_status');
            $table->index('performance_level');
            $table->index('promoted');
            $table->index('graduated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_status_tracking');
    }
};
