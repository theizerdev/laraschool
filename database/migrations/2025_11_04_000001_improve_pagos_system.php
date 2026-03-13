<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::dropIfExists('pago_detalles');
            Schema::dropIfExists('pagos');

            Schema::create('pagos', function (Blueprint $table) {
                $table->id();
                $table->string('serie', 10);
                $table->string('numero', 20);
                $table->string('tipo_pago');
                $table->date('fecha');
                $table->unsignedBigInteger('matricula_id');
                $table->unsignedBigInteger('user_id');
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('descuento', 10, 2)->default(0);
                $table->decimal('total', 10, 2);
                $table->string('metodo_pago')->nullable();
                $table->string('referencia')->nullable();
                $table->enum('estado', ['pendiente', 'aprobado', 'cancelado'])->default('pendiente');
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('empresa_id');
                $table->unsignedBigInteger('sucursal_id');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['empresa_id', 'sucursal_id', 'fecha']);
                $table->index(['matricula_id', 'estado']);
                $table->unique(['serie', 'numero', 'empresa_id', 'sucursal_id']);
            });

            Schema::create('pago_detalles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pago_id');
                $table->unsignedBigInteger('concepto_pago_id');
                $table->unsignedBigInteger('payment_schedule_id')->nullable();
                $table->string('descripcion');
                $table->decimal('cantidad', 10, 2)->default(1);
                $table->decimal('precio_unitario', 10, 2);
                $table->decimal('subtotal', 10, 2);
                $table->timestamps();

                $table->index('pago_id');
            });

            if (!Schema::hasColumn('payment_schedules', 'fecha_pago')) {
                Schema::table('payment_schedules', function (Blueprint $table) {
                    $table->date('fecha_pago')->nullable()->after('fecha_vencimiento');
                });
            }

            return;
        }

        // Obtener foreign keys existentes y eliminarlas
        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pagos' AND CONSTRAINT_NAME != 'PRIMARY'");

        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE pagos DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
        }

        // Renombrar tabla pagos a pagos_old
        Schema::rename('pagos', 'pagos_old');

        // Crear nueva tabla pagos (cabecera)
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('serie', 10);
            $table->string('numero', 20);
            $table->string('tipo_pago');
            $table->date('fecha');
            $table->unsignedBigInteger('matricula_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('metodo_pago')->nullable();
            $table->string('referencia')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'cancelado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('matricula_id')->references('id')->on('matriculas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');

            $table->index(['empresa_id', 'sucursal_id', 'fecha']);
            $table->index(['matricula_id', 'estado']);
            $table->unique(['serie', 'numero', 'empresa_id', 'sucursal_id']);
        });

        // Crear tabla de detalles de pago
        Schema::create('pago_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->onDelete('cascade');
            $table->foreignId('concepto_pago_id')->constrained('conceptos_pago');
            $table->foreignId('payment_schedule_id')->nullable()->constrained('payment_schedules');
            $table->string('descripcion');
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->index('pago_id');
        });

        // Actualizar payment_schedules para tracking de pagos
        Schema::table('payment_schedules', function (Blueprint $table) {
            //$table->decimal('monto_pagado', 10, 2)->default(0)->after('monto');
            $table->date('fecha_pago')->nullable()->after('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->dropColumn(['monto_pagado', 'fecha_pago']);
        });

        Schema::dropIfExists('pago_detalles');
        Schema::dropIfExists('pagos');

        Schema::rename('pagos_old', 'pagos');
    }
};
