<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\SecurityLog;

class SecurityAuditService
{
    /**
     * Registro de intentos de autenticación fallidos
     */
    public function logFailedLoginAttempt($email, $ipAddress, $userAgent = null)
    {
        $securityLog = new SecurityLog();
        $securityLog->user_id = null; // No se conoce el usuario aún
        $securityLog->action = 'failed_login';
        $securityLog->description = "Intento de inicio de sesión fallido para el email: {$email}";
        $securityLog->ip_address = $ipAddress;
        $securityLog->user_agent = $userAgent;
        $securityLog->metadata = [
            'email' => $email
        ];
        $securityLog->save();
        
        // Verificar si hay demasiados intentos fallidos desde la misma IP
        $this->checkBruteForceAttack($ipAddress);
    }
    
    /**
     * Registro de intentos de autenticación exitosos
     */
    public function logSuccessfulLogin(User $user, $ipAddress, $userAgent = null)
    {
        $securityLog = new SecurityLog();
        $securityLog->user_id = $user->id;
        $securityLog->action = 'successful_login';
        $securityLog->description = "Inicio de sesión exitoso para el usuario: {$user->email}";
        $securityLog->ip_address = $ipAddress;
        $securityLog->user_agent = $userAgent;
        $securityLog->save();
    }
    
    /**
     * Registro de intentos de acceso no autorizado
     */
    public function logUnauthorizedAccess($userId, $route, $ipAddress)
    {
        $user = User::find($userId);
        $securityLog = new SecurityLog();
        $securityLog->user_id = $userId;
        $securityLog->action = 'unauthorized_access';
        $securityLog->description = "Intento de acceso no autorizado a: {$route} por el usuario: " . ($user ? $user->email : 'desconocido');
        $securityLog->ip_address = $ipAddress;
        $securityLog->metadata = [
            'route' => $route
        ];
        $securityLog->save();
    }
    
    /**
     * Verificar si hay un posible ataque de fuerza bruta
     */
    public function checkBruteForceAttack($ipAddress)
    {
        $fiveMinutesAgo = now()->subMinutes(5);
        
        $failedAttemptsCount = SecurityLog::where('ip_address', $ipAddress)
            ->where('action', 'failed_login')
            ->where('created_at', '>', $fiveMinutesAgo)
            ->count();
            
        if ($failedAttemptsCount >= 5) {
            // Potencial ataque de fuerza bruta
            Log::warning("Posible ataque de fuerza bruta detectado", [
                'ip' => $ipAddress,
                'failed_attempts' => $failedAttemptsCount,
                'timestamp' => now()
            ]);
            
            // Aquí podrías implementar bloqueo de IP si es necesario
            $this->blockIpAddress($ipAddress);
        }
    }
    
    /**
     * Bloquear una dirección IP
     */
    public function blockIpAddress($ipAddress)
    {
        // Registrar el bloqueo
        $securityLog = new SecurityLog();
        $securityLog->user_id = null;
        $securityLog->action = 'ip_blocked';
        $securityLog->description = "Dirección IP bloqueada por actividades sospechosas: {$ipAddress}";
        $securityLog->ip_address = $ipAddress;
        $securityLog->save();
        
        // Aquí puedes implementar la lógica de bloqueo real si es necesario
        // Por ejemplo, guardar en una tabla de IPs bloqueadas
    }
    
    /**
     * Verificar si una IP está bloqueada
     */
    public function isIpAddressBlocked($ipAddress)
    {
        // Esta es una implementación básica - en producción podrías tener una tabla específica
        $recentBlocks = SecurityLog::where('ip_address', $ipAddress)
            ->where('action', 'ip_blocked')
            ->where('created_at', '>', now()->subHours(24))
            ->count();
            
        return $recentBlocks > 0;
    }
}