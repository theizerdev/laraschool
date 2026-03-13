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
        if (Schema::hasTable('recovery_periods')) {
            return;
        }

        // Tabla de períodos de recuperación
        Schema::create('recovery_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('school_period_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_start_date');
            $table->date('registration_end_date');
            $table->decimal('min_failing_grade', 5, 2)->default(0);
            $table->decimal('max_failing_grade', 5, 2)->default(9.99);
            $table->decimal('min_recovery_grade', 5, 2)->default(10.00);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->foreign('school_period_id')->references('id')->on('school_periods')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['empresa_id', 'sucursal_id']);
            $table->index('school_period_id');
            $table->index('is_active');
            $table->index('start_date');
            $table->index('end_date');
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    
        Schema::dropIfExists('recovery_periods');
    }
};
