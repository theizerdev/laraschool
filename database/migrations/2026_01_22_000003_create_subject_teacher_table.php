<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('subject_teacher')) {
            return;
        }

        Schema::create('subject_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->date('assigned_date');
            $table->string('academic_period', 50);
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
            
            $table->unique(['subject_id', 'teacher_id', 'academic_period']);
            $table->index(['subject_id', 'teacher_id']);
            $table->index('academic_period');
            $table->index('is_primary');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subject_teacher');
    }
};
