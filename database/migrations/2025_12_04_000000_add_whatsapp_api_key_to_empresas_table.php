<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('empresas', 'whatsapp_api_key')) {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('whatsapp_api_key')->nullable()->after('api_key');
            $table->integer('whatsapp_rate_limit')->default(100)->after('whatsapp_api_key');
            $table->boolean('whatsapp_active')->default(true)->after('whatsapp_rate_limit');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_api_key', 'whatsapp_rate_limit', 'whatsapp_active']);
        });
    }
};
