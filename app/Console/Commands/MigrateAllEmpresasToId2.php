<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateAllEmpresasToId2 extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'empresas:migrate-to-id2 
                            {--dry-run : Mostrar los cambios que se harían sin ejecutarlos}
                            {--force : Ejecutar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar todos los registros de empresa_id 1 a empresa_id 2 en todas las tablas';

    /**
     * Tablas que tienen columna empresa_id y deben ser actualizadas
     */
    protected $tablasConEmpresaId = [
        'users',
        'students', 
        'student_access_logs',
        'school_periods',
        'niveles_educativos',
        'turnos',
        'programas',
        'matriculas',
        'payment_schedules',
        'conceptos_pago',
        'pagos',
        'comprobantes',
        'series',
        'cajas',
        'mensajes',
        'mensaje_destinatarios',
        'mensaje_archivos',
        'biblioteca_categorias',
        'biblioteca_archivos',
        'biblioteca_descargas',
        'subjects',
        'teachers',
        'subject_teacher',
        'subject_student',
        'subject_schedules',
        'study_plans',
        'study_plan_subjects',
        'evaluation_periods',
        'evaluation_types',
        'evaluations',
        'grades',
        'grade_summaries',
        'sections',
        'section_student',
        'schedules',
        'classrooms',
        'attendances',
        'conduct_records',
        'grade_reports',
        'academic_records',
        'recovery_enrollments',
        'certificates',
        'academic_status_tracking',
        'late_payment_rules',
        'student_financial_blacklists',
        'financial_validation_logs',
        'exchange_rates',
        'exchange_rate_daily_histories',
        'exchange_rate_monthly_histories',
        'reunions',
        'sucursales',
        'whatsapp_messages',
        'whatsapp_scheduled_messages',
        'whatsapp_templates',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando migración de empresa_id 1 a empresa_id 2');
        $this->info('================================================');

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if (!$dryRun && !$force) {
            $this->warn('⚠️  Esta acción actualizará TODOS los registros de empresa_id 1 a empresa_id 2');
            $this->warn('   en todas las tablas del sistema.');
            $this->warn('');
            $this->warn('   Esto es irreversible sin un respaldo.');
            $this->warn('');
            
            if (!$this->confirm('¿Está seguro de que desea continuar?')) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        // Verificar que la empresa 2 existe
        $empresa2 = \App\Models\Empresa::find(2);
        if (!$empresa2) {
            $this->error('❌ No se encontró la empresa con ID 2');
            $this->info('Por favor, cree primero la empresa con ID 2');
            return 1;
        }

        $this->info("✅ Empresa 2 encontrada: {$empresa2->razon_social}");

        // Verificar que la empresa 1 existe
        $empresa1 = \App\Models\Empresa::find(1);
        if (!$empresa1) {
            $this->warn('⚠️  No se encontró la empresa con ID 1');
            $this->info('   No hay registros para migrar.');
            return 0;
        }

        $totalActualizados = 0;
        $totalTablas = 0;

        DB::beginTransaction();

        try {
            foreach ($this->tablasConEmpresaId as $tabla) {
                if (!Schema::hasTable($tabla)) {
                    $this->warn("⚠️  La tabla {$tabla} no existe, saltando...");
                    continue;
                }

                if (!Schema::hasColumn($tabla, 'empresa_id')) {
                    $this->warn("⚠️  La tabla {$tabla} no tiene columna empresa_id, saltando...");
                    continue;
                }

                // Contar registros con empresa_id 1
                $count = DB::table($tabla)->where('empresa_id', 1)->count();

                if ($count === 0) {
                    $this->line("ℹ️  Tabla {$tabla}: Sin registros con empresa_id 1");
                    continue;
                }

                $this->info("📊 Tabla {$tabla}: {$count} registros para actualizar");

                if ($dryRun) {
                    $this->line("   [DRY RUN] Se actualizarían {$count} registros de empresa_id 1 a 2");
                } else {
                    // Actualizar los registros
                    $afectados = DB::table($tabla)
                        ->where('empresa_id', 1)
                        ->update(['empresa_id' => 2]);

                    $this->info("   ✅ {$afectados} registros actualizados");
                    $totalActualizados += $afectados;
                }

                $totalTablas++;
            }

            if ($dryRun) {
                $this->info("\n📋 RESUMEN DE SIMULACIÓN (DRY RUN):");
                $this->info("=====================================");
                $this->info("Tablas procesadas: {$totalTablas}");
                $this->info("Total de registros que se actualizarían: {$totalActualizados}");
                $this->info("\n💡 Use sin --dry-run para ejecutar los cambios");
            } else {
                $this->info("\n✅ MIGRACIÓN COMPLETADA");
                $this->info("=======================");
                $this->info("Tablas actualizadas: {$totalTablas}");
                $this->info("Total de registros actualizados: {$totalActualizados}");
                
                // Actualizar la configuración de WhatsApp
                $this->actualizarConfiguracionWhatsApp();
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error durante la migración: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    /**
     * Actualizar la configuración de WhatsApp para usar empresa 2
     */
    private function actualizarConfiguracionWhatsApp(): void
    {
        $this->info("\n📱 Actualizando configuración de WhatsApp...");

        // Verificar si la empresa 2 tiene API key configurada
        $empresa2 = \App\Models\Empresa::find(2);
        
        if (empty($empresa2->whatsapp_api_key)) {
            $this->warn("⚠️  La empresa 2 no tiene API key de WhatsApp configurada");
            
            // Copiar la API key de la empresa 1 si existe
            $empresa1 = \App\Models\Empresa::find(1);
            if ($empresa1 && !empty($empresa1->whatsapp_api_key)) {
                $empresa2->whatsapp_api_key = $empresa1->whatsapp_api_key;
                $empresa2->save();
                $this->info("✅ API key copiada de empresa 1 a empresa 2");
            } else {
                // Generar una nueva API key
                $apiKey = $empresa2->regenerateWhatsAppApiKey();
                $this->info("✅ Nueva API key generada: " . substr($apiKey, 0, 15) . '...');
            }
        } else {
            $this->info("ℹ️  La empresa 2 ya tiene API key configurada");
        }

        // Verificar usuarios con empresa_id 1 y actualizarlos a 2
        $usuariosEmpresa1 = \App\Models\User::where('empresa_id', 1)->count();
        if ($usuariosEmpresa1 > 0) {
            $actualizados = \App\Models\User::where('empresa_id', 1)->update(['empresa_id' => 2]);
            $this->info("✅ {$actualizados} usuarios actualizados de empresa_id 1 a 2");
        }

        $this->info("✅ Configuración de WhatsApp actualizada");
    }
}