<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('empresas', 'api_key')) {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('api_key', 64)->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('api_key');
        });
    }
};
