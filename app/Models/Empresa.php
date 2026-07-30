<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Empresa extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'razon_social',
        'documento',
        'direccion',
        'latitud',
        'longitud',
        'representante_legal',
        'status',
        'telefono',
        'email',
        'pais_id',
        'api_key',
        // Campos para integración WhatsApp multi-empresa
        'whatsapp_api_key',
        'whatsapp_rate_limit',
        'whatsapp_active',
        'whatsapp_phone',
        'whatsapp_status',
        'whatsapp_last_connected',
        'whatsapp_api_url',
        'whatsapp_instance'
    ];

    protected $casts = [
        'status' => 'boolean',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'whatsapp_active' => 'boolean',
        'whatsapp_last_connected' => 'datetime',
    ];

    public function sucursales()
    {
        return $this->hasMany(Sucursal::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    public function scopeForUser($query)
    {
        if (auth()->check() && !auth()->user()->hasRole('Super Administrador')) {
            if (auth()->user()->empresa_id) {
                $query->where('id', auth()->user()->empresa_id);
            }
        }
        return $query;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($empresa) {
            if (!$empresa->api_key) {
                $empresa->api_key = self::generateApiKey();
            }
        });
    }

    public static function generateApiKey(): string
    {
        return 'vg_' . bin2hex(random_bytes(24));
    }

    public function regenerateApiKey(): void
    {
        $this->update(['api_key' => self::generateApiKey()]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['razon_social', 'documento', 'direccion', 'representante_legal', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ==================== Métodos WhatsApp ====================

    /**
     * Verifica si la empresa tiene WhatsApp configurado
     */
    public function hasWhatsAppConfigured(): bool
    {
        return !empty($this->whatsapp_api_key);
    }

    /**
     * Verifica si WhatsApp está conectado
     */
    public function isWhatsAppConnected(): bool
    {
        return $this->whatsapp_status === 'connected';
    }

    /**
     * Obtiene el servicio de WhatsApp para esta empresa
     */
    public function getWhatsAppService(): \App\Services\WhatsAppService
    {
        return \App\Services\WhatsAppService::forCompany($this);
    }

    /**
     * Genera una nueva API key para WhatsApp
     */
    public function regenerateWhatsAppApiKey(): string
    {
        $apiKey = 'wa_' . $this->id . '_' . bin2hex(random_bytes(16));
        $this->update(['whatsapp_api_key' => $apiKey]);
        return $apiKey;
    }

    /**
     * Actualiza el estado de WhatsApp
     */
    public function updateWhatsAppStatus(string $status, ?string $phone = null): void
    {
        $data = ['whatsapp_status' => $status];
        
        if ($status === 'connected') {
            $data['whatsapp_last_connected'] = now();
            if ($phone) {
                $data['whatsapp_phone'] = $phone;
            }
        }
        
        $this->update($data);
    }
}