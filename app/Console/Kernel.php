<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendAccessNotificationJob::class,
        Commands\SendAutomaticNotificationsCommand::class,
        Commands\FetchExchangeRates::class,
        Commands\ProcessWhatsAppScheduledMessages::class,
        Commands\RetryFailedWhatsAppMessages::class,
        Commands\ScheduleWhatsAppRetry::class,
        Commands\SetupWhatsAppAutoRetry::class,
        Commands\WhatsAppRetryStatus::class,
        Commands\DevRetryWhatsApp::class, // Comando de desarrollo
        Commands\TestStudentWhatsAppNotification::class, // Comando de prueba para notificaciones de estudiantes
        Commands\CheckWhatsAppCompanyConfiguration::class, // Comando para verificar configuración de empresas WhatsApp
        Commands\ConfigureWhatsAppCompany::class, // Comando para configurar empresa específica para WhatsApp
        Commands\MigrateAllEmpresasToId2::class, // Comando para migrar todos los datos de empresa_id 1 a 2
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('notifications:send-automatic')->dailyAt('08:00');
        $schedule->command('reports:send-account-reminders')->dailyAt('08:10');
        
        // Obtener tasas de cambio del BCV dos veces al día
        $schedule->command('exchange:fetch')
            ->dailyAt('10:00')
            ->timezone('America/Caracas');
            
        $schedule->command('exchange:fetch')
            ->dailyAt('14:00')
            ->timezone('America/Caracas');

        // Procesar mensajes programados de WhatsApp cada minuto
        $schedule->command('whatsapp:process-scheduled')
            ->everyMinute()
            ->timezone('America/Caracas')
            ->withoutOverlapping()
            ->onOneServer();

        // Reenvío automático de mensajes fallidos cada hora
        $schedule->command('whatsapp:schedule-retry')
            ->hourly()
            ->timezone('America/Caracas')
            ->withoutOverlapping()
            ->onOneServer()
            ->when(function () {
                // Solo ejecutar si hay mensajes fallidos para reenviar
                return \App\Models\WhatsAppMessage::where('direction', 'outbound')
                    ->retryable()
                    ->exists();
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}