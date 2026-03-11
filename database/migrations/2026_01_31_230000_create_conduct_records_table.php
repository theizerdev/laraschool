<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conduct_records')) {
            return;
        }

        Schema::create('conduct_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('sucursal_id')->constrained('sucursales')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_period_id')->constrained('school_periods')->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->date('date');
            $table->enum('type', ['positive', 'negative', 'neutral', 'warning', 'sanction'])->default('neutral');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->string('category')->nullable();
            $table->text('description');
            $table->text('actions_taken')->nullable();
            $table->text('parent_notified')->nullable();
            $table->date('parent_notification_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->boolean('resolved')->default(false);
            $table->date('resolution_date')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('registered_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'date']);
            $table->index(['student_id', 'school_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conduct_records');
    }
};
