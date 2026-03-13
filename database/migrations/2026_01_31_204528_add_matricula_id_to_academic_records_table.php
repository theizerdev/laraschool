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
        if (Schema::hasColumn('academic_records', 'matricula_id')) {
            return;
        }

        Schema::table('academic_records', function (Blueprint $table) {
            $table->unsignedBigInteger('matricula_id')->after('student_id')->nullable();
            $table->index(['matricula_id', 'student_id', 'school_period_id'], 'idx_academic_record_matricula');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_records', function (Blueprint $table) {
            $table->dropIndex('idx_academic_record_matricula');
            $table->dropColumn('matricula_id');
        });
    }
};
