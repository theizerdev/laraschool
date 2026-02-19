<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rate_daily_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_history_id')->constrained('exchange_rate_monthly_histories')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('usd_rate', 10, 4);
            $table->decimal('eur_rate', 10, 4)->nullable();
            $table->string('source', 64)->nullable();
            $table->time('fetch_time')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->unique(['monthly_history_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_daily_histories');
    }
};
