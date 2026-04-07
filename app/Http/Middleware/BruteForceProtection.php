<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SecurityAuditService;

class BruteForceProtection
{
    protected $securityAuditService;

    public function __construct(SecurityAuditService $securityAuditService)
    {
        $this->securityAuditService = $securityAuditService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ipAddress = $request->ip();
        
        // Verificar si la IP está bloqueada
        if ($this->securityAuditService->isIpAddressBlocked($ipAddress)) {
            return response()->json([
                'error' => 'Dirección IP bloqueada por actividades sospechosas',
                'message' => 'Tu IP ha sido temporalmente bloqueada debido a intentos de acceso sospechosos.'
            ], 403);
        }

        // Permitir solicitudes GET sin restricción
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Verificar si es una solicitud de login
        if ($request->is('login') || $request->is('api/login')) {
            $maxAttempts = 5;
            $lockoutTime = 900; // 15 minutos
            
            $key = 'login_attempts_' . $ipAddress;
            $attempts = Cache::get($key, 0);
            
            if ($attempts >= $maxAttempts) {
                return response()->json([
                    'error' => 'Demasiados intentos de inicio de sesión',
                    'message' => 'Se ha superado el límite de intentos de inicio de sesión. Inténtalo de nuevo más tarde.'
                ], 429);
            }
        }

        return $next($request);
    }
}