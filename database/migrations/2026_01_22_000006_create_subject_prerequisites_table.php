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
        if (Schema::hasTable('subject_prerequisites')) {
            return;
        }

        Schema::create('subject_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id'); // Materia que tiene prerrequisito
            $table->unsignedBigInteger('prerequisite_subject_id'); // Materia prerrequisito
            $table->enum('type', ['mandatory', 'recommended'])->default('mandatory'); // Tipo de prerrequisito
            $table->text('notes')->nullable(); // Notas adicionales
            $table->boolean('is_active')->default(true); // Estado del prerrequisito
            $table->timestamps();
            
            // Índices
            $table->index('subject_id');
            $table->index('prerequisite_subject_id');
            $table->index('type');
            $table->index('is_active');
            
            // Claves foráneas
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('prerequisite_subject_id')->references('id')->on('subjects')->onDelete('cascade');
            
            // Índice único para evitar duplicados
            $table->unique(['subject_id', 'prerequisite_subject_id'], 'unique_subject_prerequisite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_prerequisites');
    }
};
