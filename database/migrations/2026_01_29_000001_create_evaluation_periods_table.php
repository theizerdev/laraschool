<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_periods')) {
            return;
        }

        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->onDelete('set null');
            $table->foreignId('school_period_id')->constrained('school_periods')->onDelete('cascade');
            $table->string('name'); // Ej: "1er Lapso", "2do Lapso", "3er Lapso"
            $table->integer('number')->default(1); // Número del lapso (1, 2, 3)
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('weight', 5, 2)->default(100.00); // Peso porcentual del lapso
            $table->boolean('is_active')->default(true);
            $table->boolean('is_closed')->default(false); // Si el lapso está cerrado para modificaciones
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->unique(['school_period_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_periods');
    }
};
