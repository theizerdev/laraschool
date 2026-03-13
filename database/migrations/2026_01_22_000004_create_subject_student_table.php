<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('subject_student')) {
            return;
        }

        Schema::create('subject_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('enrollment_date');
            $table->enum('status', ['enrolled', 'completed', 'dropped', 'failed'])->default('enrolled');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->string('academic_period', 50);
            $table->timestamps();
            
            $table->unique(['subject_id', 'student_id', 'academic_period']);
            $table->index(['subject_id', 'student_id']);
            $table->index('status');
            $table->index('academic_period');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subject_student');
    }
};
