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
        if (Schema::hasTable('classrooms')) {
            return;
        }

        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->unsignedBigInteger('sucursal_id')->nullable();
            $table->string('nombre');
            $table->string('codigo')->unique();
            $table->string('ubicacion')->nullable();
            $table->integer('capacidad')->default(30);
            $table->enum('tipo_aula', ['regular', 'laboratorio', 'taller', 'auditorio', 'biblioteca', 'otro'])->default('regular');
            $table->json('recursos')->nullable(); // Array de recursos disponibles
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('empresa_id');
            $table->index('sucursal_id');
            $table->index('tipo_aula');
            $table->index('is_active');
            $table->index('capacidad');

            // Claves foráneas
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
