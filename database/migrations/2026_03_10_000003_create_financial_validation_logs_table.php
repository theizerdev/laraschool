<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financial_validation_logs')) {
            return;
        }

        Schema::create('financial_validation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->foreignId('estudiante_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('school_periods')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('solvente')->default(false);
            $table->decimal('total_adeudado', 12, 2)->default(0);
            $table->unsignedInteger('duracion_ms')->default(0);
            $table->json('detalles')->nullable();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->timestamps();

            $table->index(['estudiante_id', 'periodo_id']);
            $table->index(['empresa_id', 'sucursal_id', 'created_at'], 'idx_fv_empresa_sucursal_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_validation_logs');
    }
};
