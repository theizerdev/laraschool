<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grade_summaries')) {
            return;
        }

        Schema::create('grade_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->onDelete('set null');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('matricula_id')->constrained('matriculas')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('evaluation_period_id')->constrained('evaluation_periods')->onDelete('cascade');
            $table->decimal('average_score', 5, 2)->nullable(); // Promedio del lapso
            $table->decimal('final_score', 5, 2)->nullable(); // Nota final ajustada
            $table->text('observations')->nullable();
            $table->enum('status', ['pending', 'approved', 'failed', 'pending_review'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->unique(['student_id', 'subject_id', 'evaluation_period_id'], 'grade_summaries_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_summaries');
    }
};
