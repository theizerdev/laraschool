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
        Schema::create('exchange_rate_monthly_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->decimal('usd_avg', 10, 4);
            $table->decimal('usd_min', 10, 4);
            $table->decimal('usd_max', 10, 4);
            $table->decimal('eur_avg', 10, 4)->nullable();
            $table->decimal('eur_min', 10, 4)->nullable();
            $table->decimal('eur_max', 10, 4)->nullable();
            $table->unsignedInteger('records_count');
            $table->json('sources')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_monthly_histories');
    }
};
