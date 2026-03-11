<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('subjects')) {
            return;
        }

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->integer('credits')->default(0);
            $table->integer('hours_per_week')->default(0);
            $table->foreignId('program_id')->constrained('programas');
            $table->foreignId('educational_level_id')->constrained('niveles_educativos');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['program_id', 'educational_level_id']);
            $table->index('is_active');
            $table->index('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subjects');
    }
};
