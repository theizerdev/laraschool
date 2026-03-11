<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluations')) {
            return;
        }

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->onDelete('set null');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('evaluation_period_id')->constrained('evaluation_periods')->onDelete('cascade');
            $table->foreignId('evaluation_type_id')->constrained('evaluation_types')->onDelete('cascade');
            $table->string('name'); // Nombre de la evaluación
            $table->text('description')->nullable();
            $table->date('evaluation_date');
            $table->decimal('max_score', 5, 2)->default(20.00); // Nota máxima
            $table->decimal('weight', 5, 2)->default(100.00); // Peso porcentual dentro del lapso
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false); // Si las notas están publicadas
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
