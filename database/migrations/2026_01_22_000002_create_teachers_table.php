<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('teachers')) {
            return;
        }

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('employee_code', 50)->unique();
            $table->string('specialization', 100)->nullable();
            $table->string('degree', 100)->nullable();
            $table->integer('years_experience')->default(0);
            $table->date('hire_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('empresa_id')->constrained('empresas')->default(1);
            $table->foreignId('sucursal_id')->constrained('sucursales')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('user_id');
            $table->index('employee_code');
            $table->index('is_active');
            $table->index('specialization');
        });
    }

    public function down()
    {
        Schema::dropIfExists('teachers');
    }
};
