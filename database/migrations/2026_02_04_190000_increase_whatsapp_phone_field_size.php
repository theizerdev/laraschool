<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para aumentar el tamaño del campo whatsapp_phone
 * 
 * El campo actual solo permite 20 caracteres, pero los números de WhatsApp
 * pueden tener formatos como: 584241703465:98@s.whatsapp.net (28 caracteres)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Aumentar el tamaño del campo whatsapp_phone de 20 a 50 caracteres
            if (Schema::hasColumn('empresas', 'whatsapp_phone')) {
                try {
                    $table->string('whatsapp_phone', 50)->nullable()->change();
                } catch (\Throwable $e) {
                    return;
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Volver al tamaño original de 20 caracteres
            if (Schema::hasColumn('empresas', 'whatsapp_phone')) {
                try {
                    $table->string('whatsapp_phone', 20)->nullable()->change();
                } catch (\Throwable $e) {
                    return;
                }
            }
        });
    }
};
