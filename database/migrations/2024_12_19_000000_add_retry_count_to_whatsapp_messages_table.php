<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('whatsapp_messages', 'retry_count')) {
            return;
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            // Añadir campo para contador de reintentos
            $table->unsignedTinyInteger('retry_count')->default(0)->after('metadata');
            
            // Añadir índice para búsquedas más eficientes
            $table->index(['direction', 'status', 'retry_count'], 'idx_direction_status_retry');
            $table->index(['created_at', 'direction', 'retry_count'], 'idx_created_direction_retry');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropIndex(['idx_direction_status_retry']);
            $table->dropIndex(['idx_created_direction_retry']);
            $table->dropColumn('retry_count');
        });
    }
};
