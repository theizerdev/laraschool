<?php

namespace App\Listeners;

use App\Events\UserPasswordChanged;
use App\Services\SecurityAuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SecurityEventListener
{
    protected $securityAuditService;

    public function __construct(SecurityAuditService $securityAuditService)
    {
        $this->securityAuditService = $securityAuditService;
    }

    /**
     * Handle the event.
     */
    public function handle(UserPasswordChanged $event): void
    {
        // Registrar el cambio de contraseña en los logs de seguridad
        $securityLog = new \App\Models\SecurityLog();
        $securityLog->user_id = $event->user->id;
        $securityLog->action = 'password_changed';
        $securityLog->description = "El usuario {$event->user->email} cambió su contraseña";
        $securityLog->ip_address = $event->ipAddress;
        $securityLog->user_agent = $event->userAgent;
        $securityLog->save();

        Log::info("Registro de cambio de contraseña", [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => $event->ipAddress
        ]);
    }
}