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
        if (Schema::hasTable('recovery_enrollments')) {
            return;
        }

        Schema::create('recovery_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recovery_period_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_record_id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            
            // Información de inscripción
            $table->string('enrollment_status', 50)->default('pending'); // pending, approved, rejected, completed
            $table->date('enrollment_date')->nullable();
            $table->date('approval_date')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Información académica
            $table->unsignedBigInteger('original_subject_id');
            $table->unsignedBigInteger('recovery_subject_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('grade', 10)->nullable();
            $table->string('section', 10)->nullable();
            
            // Calificaciones
            $table->decimal('original_grade', 5, 2)->nullable();
            $table->decimal('recovery_grade', 5, 2)->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->string('result', 50)->nullable(); // approved, failed, withdrawn
            
            // Estado de recuperación
            $table->boolean('attended_recovery')->default(false);
            $table->boolean('completed_recovery')->default(false);
            $table->date('recovery_completion_date')->nullable();
            
            // Información de pago
            $table->decimal('recovery_cost', 10, 2)->nullable();
            $table->string('payment_status', 50)->default('pending'); // pending, paid, waived
            $table->date('payment_date')->nullable();
            $table->string('payment_reference', 100)->nullable();
            
            // Documentación
            $table->boolean('documentation_complete')->default(false);
            $table->text('documentation_notes')->nullable();
            
            // Horario y profesor asignado
            $table->string('schedule', 100)->nullable();
            $table->string('classroom', 50)->nullable();
            $table->integer('total_recovery_classes')->default(0);
            $table->integer('attended_recovery_classes')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices (sin claves foráneas temporalmente)
            $table->unique(['recovery_period_id', 'student_id', 'academic_record_id'], 'idx_recovery_enrollment_unique');
            $table->index('student_id');
            $table->index('recovery_period_id');
            $table->index('academic_record_id');
            $table->index('enrollment_status');
            $table->index('empresa_id');
            $table->index('sucursal_id');
            $table->index('result');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_enrollments');
    }
};
