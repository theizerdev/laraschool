<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('teachers', 'name')) {
            return;
        }

        if (\DB::getDriverName() !== 'mysql') {
            Schema::table('teachers', function (Blueprint $table) {
                $table->string('name', 255)->nullable()->after('user_id');
                $table->string('email', 255)->nullable()->after('name');
            });

            return;
        }

        Schema::table('teachers', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->after('user_id');
            $table->string('email', 255)->nullable()->after('name');
            
            // Índices para mejorar el rendimiento
            $table->index('email');
            $table->index(['name', 'email']);
        });
        
        // Actualizar los datos existentes con los valores de la tabla users
        \DB::table('teachers')->join('users', 'teachers.user_id', '=', 'users.id')
            ->update([
                'teachers.name' => \DB::raw('users.name'),
                'teachers.email' => \DB::raw('users.email')
            ]);
        
        // Hacer los campos obligatorios después de la migración de datos
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('name', 255)->nullable(false)->change();
            $table->string('email', 255)->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropIndex(['name', 'email']);
            $table->dropIndex(['email']);
            $table->dropColumn(['name', 'email']);
        });
    }
};
