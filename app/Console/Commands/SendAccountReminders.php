<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlanPago;
use App\Services\Notification\NotificationService;
use App\Services\Audit\AuditService;

class SendAccountReminders extends Command
{
    protected $signature = 'reports:send-account-reminders {--days=7}';
    protected $description = 'Enviar recordatorios de cuotas pendientes y vencidas a clientes';

    public function handle(): int
    {
        $days = (int)$this->option('days');
        $limitDate = now()->addDays($days);
        $pendientes = PlanPago::with(['contrato.cliente'])
            ->whereIn('estado', ['pendiente', 'vencido', 'parcial'])
            ->where('fecha_vencimiento', '<=', $limitDate)
            ->orderBy('fecha_vencimiento')
            ->get();

        $count = 0;
        $notification = app(NotificationService::class);
        $audit = app(AuditService::class);

        foreach ($pendientes as $p) {
            $cliente = $p->contrato->cliente ?? null;
            if (!$cliente) continue;
            $message = "Recordatorio: Cuota #{$p->numero_cuota} vence el " . optional($p->fecha_vencimiento)->format('d/m/Y') .
                ". Saldo: $" . number_format($p->saldo_pendiente, 2);
            try {
                if (!empty($cliente->email)) {
                    $notification->enqueue('mail', [
                        'to' => $cliente->email,
                        'subject' => 'Recordatorio de Cuota Pendiente',
                        'body' => $message
                    ], 'high');
                }
                if (!empty($cliente->telefono)) {
                    $notification->enqueue('sms', [
                        'to' => $cliente->telefono,
                        'text' => $message
                    ], 'high');
                }
                $count++;
            } catch (\Exception $e) {
                $this->error("Error notificando cliente {$cliente->id}: {$e->getMessage()}");
                $audit->logUserAction('report.account_status.reminders.failed', [
                    'cliente_id' => $cliente->id ?? null,
                    'error' => $e->getMessage()
                ], 'Error recordatorio programado');
            }
        }

        $audit->logUserAction('report.account_status.reminders.cron', [
            'count' => $count,
            'days' => $days
        ], 'Recordatorios programados enviados');

        $this->info("Recordatorios encolados: {$count}");
        return Command::SUCCESS;
    }
}
