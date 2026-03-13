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
        if (Schema::hasTable('certificates')) {
            return;
        }

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('matricula_id');
            $table->unsignedBigInteger('school_period_id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            
            // Información del certificado
            $table->string('certificate_type', 50); // academic, attendance, conduct, completion
            $table->string('certificate_number', 100)->unique();
            $table->date('issue_date');
            $table->date('expiration_date')->nullable();
            $table->string('status', 50)->default('active'); // active, revoked, expired
            
            // Contenido del certificado
            $table->text('content')->nullable();
            $table->json('academic_data')->nullable(); // Datos académicos en JSON
            $table->decimal('overall_average', 5, 2)->nullable(); // Promedio general
            $table->integer('total_subjects')->nullable(); // Total de materias
            $table->integer('approved_subjects')->nullable(); // Materias aprobadas
            
            // Campos adicionales según tipo
            $table->string('conduct_grade', 10)->nullable(); // Calificación de conducta
            $table->decimal('attendance_percentage', 5, 2)->nullable(); // Porcentaje de asistencia
            $table->boolean('completed')->default(false); // Si completó el período
            
            // Control de validez
            $table->string('verification_code', 100)->unique();
            $table->boolean('is_digital')->default(true);
            $table->string('digital_signature', 255)->nullable();
            $table->string('issued_by', 100)->nullable(); // Nombre del que emitió
            $table->unsignedBigInteger('issued_by_user_id')->nullable(); // ID del usuario que emitió
            
            // Observaciones
            $table->text('observations')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->date('revocation_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['student_id', 'matricula_id', 'school_period_id'], 'idx_certificate_student_matricula_period');
            $table->index(['certificate_type', 'status'], 'idx_certificate_type_status');
            $table->index('certificate_number');
            $table->index('verification_code');
            $table->index(['empresa_id', 'sucursal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
