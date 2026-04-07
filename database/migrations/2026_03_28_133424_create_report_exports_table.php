<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // Tipo de reporte: 'morosidad', 'matriculas', etc.
            $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
            $table->json('filters')->nullable(); // Filtros aplicados al reporte
            $table->string('file_path')->nullable(); // Ruta del archivo generado
            $table->string('file_name')->nullable(); // Nombre del archivo
            $table->integer('total_records')->nullable(); // Número total de registros
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas frecuentes
            $table->index(['user_id']);
            $table->index(['type']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('report_exports');
    }
};