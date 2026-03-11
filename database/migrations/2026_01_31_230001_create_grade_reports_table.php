<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grade_reports')) {
            return;
        }

        Schema::create('grade_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('sucursal_id')->constrained('sucursales')->onDelete('cascade');
            $table->foreignId('school_period_id')->constrained('school_periods')->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('evaluation_period_id')->nullable()->constrained('evaluation_periods')->nullOnDelete();
            $table->string('report_number')->unique();
            $table->enum('report_type', ['period', 'final', 'recovery'])->default('period');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('grades_data'); // Datos de las calificaciones
            $table->json('statistics')->nullable(); // Estadísticas
            $table->integer('total_students')->default(0);
            $table->integer('approved_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->decimal('average_grade', 5, 2)->nullable();
            $table->decimal('highest_grade', 5, 2)->nullable();
            $table->decimal('lowest_grade', 5, 2)->nullable();
            $table->enum('status', ['draft', 'generated', 'approved', 'published'])->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observations')->nullable();
            $table->string('file_path')->nullable(); // Ruta del PDF generado
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_period_id', 'section_id']);
            $table->index(['report_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_reports');
    }
};
