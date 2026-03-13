<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('matriculas', 'solvente')) {
            return;
        }

        Schema::table('matriculas', function (Blueprint $table) {
            $table->boolean('solvente')->default(true)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn('solvente');
        });
    }
};
