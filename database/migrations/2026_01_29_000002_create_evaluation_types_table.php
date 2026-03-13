<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluation_types')) {
            return;
        }

        Schema::create('evaluation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->onDelete('set null');
            $table->string('name'); // Ej: "Examen", "Quiz", "Tarea", "Participación", "Exposición"
            $table->string('code', 20); // Ej: "EXAM", "QUIZ", "TASK"
            $table->decimal('default_weight', 5, 2)->default(0); // Peso por defecto
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['empresa_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_types');
    }
};
