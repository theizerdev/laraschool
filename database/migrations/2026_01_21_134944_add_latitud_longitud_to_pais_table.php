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
        if (Schema::hasColumn('pais', 'latitud')) {
            return;
        }

        Schema::table('pais', function (Blueprint $table) {
            $table->decimal('latitud', 11, 8)->nullable()->after('continente');
            $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pais', function (Blueprint $table) {
            $table->dropColumn(['latitud', 'longitud']);
        });
    }
};
