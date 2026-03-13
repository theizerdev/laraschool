<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Multitenantable;

class PaymentSchedule extends Model
{
    use HasFactory, Multitenantable;

    protected $fillable = [
        'matricula_id',
        'numero_cuota',
        'monto',
        'monto_pagado',
        'fecha_vencimiento',
        'estado',
        'empresa_id',
        'sucursal_id'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'fecha_vencimiento' => 'date'
    ];

    protected $attributes = [
        'estado' => 'pendiente',
        'monto_pagado' => 0
    ];

    public function matricula()
    {
        return $this->belongsTo(Matricula::class);
    }

    public function pagoDetalles()
    {
        return $this->hasMany(PagoDetalle::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', now());
    }

    public function getSaldoPendienteAttribute()
    {
        return $this->monto - $this->monto_pagado;
    }

    public function getRecargoMorosidadAttribute()
    {
        if ($this->estado !== 'pendiente' || $this->fecha_vencimiento >= now()) {
            return 0;
        }

        $rule = \App\Models\LatePaymentRule::getActiveRule();
        if (!$rule) {
            return 0;
        }

        $diasVencido = $this->fecha_vencimiento->diffInDays(now());
        return $rule->calcularRecargo($this->saldo_pendiente, $diasVencido);
    }

    public function getMontoConRecargoAttribute()
    {
        return $this->saldo_pendiente + $this->recargo_morosidad;
    }

    public function getEstaPagadoAttribute()
    {
        return $this->monto_pagado >= $this->monto;
    }

    /**
     * Sincroniza el monto pagado desde los pagos reales aprobados
     * y actualiza el estado de la cuota según corresponda
     */
    public function syncPaidAmountFromPayments()
    {
        // Sumar todos los pagos aprobados asociados a esta cuota
        $totalPagado = $this->pagoDetalles()
            ->whereHas('pago', function ($query) {
                $query->where('estado', 'aprobado');
            })
            ->sum('subtotal');

        $this->monto_pagado = $totalPagado;
        
        // Actualizar estado según corresponda
        if ($this->esta_pagado) {
            $this->estado = 'pagado';
        } elseif ($this->fecha_vencimiento < now() && $this->estado !== 'pagado') {
            $this->estado = 'vencido';
        } else {
            $this->estado = 'pendiente';
        }

        $this->save();
        
        return $totalPagado;
    }

    /**
     * Boot method para manejar eventos del modelo
     * Se asegura de actualizar automáticamente el estado de la cuota
     * cuando se registran nuevos pagos
     */
    protected static function boot()
    {
        parent::boot();

        // Cuando se crea un pago detalle, actualizar la cuota correspondiente
        static::created(function ($schedule) {
            $schedule->syncPaidAmountFromPayments();
        });

        // También podríamos escuchar eventos updating/updated si necesitamos
        // manejar cambios en los pagos existentes
    }
}
