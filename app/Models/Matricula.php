<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Multitenantable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Matricula extends Model
{
    use HasFactory, Multitenantable, LogsActivity;

    protected $fillable = [
        'empresa_id',
        'sucursal_id',
        'estudiante_id',
        'programa_id',
        'periodo_id',
        'nivel_educativo_id',
        'turno_id',
        'fecha_matricula',
        'costo',
        'cuota_inicial',
        'numero_cuotas',
        'estado',
        'observaciones',
        'solvente',
    ];

    protected $casts = [
        'fecha_matricula' => 'date',
        'costo' => 'decimal:2',
        'cuota_inicial' => 'decimal:2',
        'solvente' => 'boolean',
    ];

    protected $attributes = [
        'solvente' => true,
    ];

    public function estudiante()
    {
        return $this->belongsTo(Student::class, 'estudiante_id');
    }

    // Alias para la relación estudiante
    public function student()
    {
        return $this->estudiante();
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'programa_id');
    }

    public function nivelEducativo()
    {
        return $this->belongsTo(EducationalLevel::class, 'nivel_educativo_id');
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    public function schoolPeriod()
    {
        return $this->belongsTo(SchoolPeriod::class, 'periodo_id');
    }

    // Alias para la relación schoolPeriod
    public function periodo()
    {
        return $this->schoolPeriod();
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    // Relaciones con AcademicTracking
    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class, 'matricula_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'matricula_id');
    }

    public function academicStatusTracking(): HasMany
    {
        return $this->hasMany(AcademicStatusTracking::class, 'matricula_id');
    }

    // Corregir el nombre de la relación para que coincida con el modelo PaymentSchedule
    public function paymentSchedules()
    {
        return $this->hasMany(PaymentSchedule::class, 'matricula_id');
    }

    // Alias para la relación paymentSchedules
    public function cronogramaPagos()
    {
        return $this->paymentSchedules();
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'matricula_id');
    }

    // Métodos auxiliares para el estado académico
    public function getCurrentAcademicStatusAttribute()
    {
        return $this->academicStatusTracking()
                   ->where('status', AcademicStatusTracking::TRACKING_ACTIVE)
                   ->first();
    }

    public function getLatestCertificateAttribute()
    {
        return $this->certificates()
                   ->where('status', Certificate::STATUS_ACTIVE)
                   ->latest()
                   ->first();
    }

    public function getAcademicPerformanceAttribute()
    {
        $academicStatus = $this->current_academic_status;
        return $academicStatus ? $academicStatus->academic_summary : null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'estudiante_id',
                'programa_id',
                'nivel_educativo_id',
                'turno_id',
                'periodo_id',
                'fecha_matricula',
                'costo',
                'cuota_inicial',
                'numero_cuotas',
                'estado'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Sincronizar todos los payment schedules con los pagos reales
     * Este método actualiza el monto_pagado de cada cuota basándose en los pagos aprobados
     */
   public function syncPaymentSchedules()
    {
        foreach ($this->paymentSchedules as $schedule) {
            $schedule->syncPaidAmountFromPayments();
        }
        
        // Actualizar automáticamente la solvencia después de sincronizar pagos
        $this->updateSolvencia();
        
        return $this;
    }

    /**
     * Calcular si la matrícula está solvente (todas las cuotas pagadas y sin vencimientos)
     * 
     * @return bool true si está solvente, false si debe alguna cuota
     */
  public function calcularEsSolvente(): bool
    {
        $paymentSchedules = $this->paymentSchedules;
        
       if($paymentSchedules->isEmpty()) {
            return true; // Sin cronograma = solvente
        }

        // Verificar cada cuota - si tiene saldo pendiente, NO es solvente
        foreach ($paymentSchedules as $schedule) {
            // Si hay saldo pendiente (no está completamente pagada)
           if($schedule->saldo_pendiente > 0.01) { // Usar 0.01 para evitar problemas de precisión decimal
                // Verificar si está vencida
               if($schedule->estado === 'vencido') {
                    return false; // Definitivamente no es solvente
                }
                
                // Verificar si la fecha de vencimiento ya pasó y no está pagada
               if($schedule->fecha_vencimiento < now() && !$schedule->esta_pagado) {
                    return false; // Vencida técnicamente
                }
                
                // Si es una cuota futura pero tiene saldo pendiente, tampoco es solvente
                // porque significa que hay deuda acumulada
                return false;
            }
        }

        // Todas las cuotas están completamente pagadas
        return true;
    }

    /**
     * Actualizar el estado de solvencia de la matrícula
     * Se llama automáticamente después de sincronizar los payment schedules
     */
   public function updateSolvencia()
    {
        $esSolvente = $this->calcularEsSolvente();
        
        if($this->solvente !== $esSolvente) {
            $this->solvente = $esSolvente;
            $this->save();
        }

        return $esSolvente;
    }

    /**
     * Obtener resumen financiero de la matrícula
     */
   public function getResumenFinancieroAttribute(): array
    {
        $totalCuotas = $this->paymentSchedules->sum('monto');
        $totalPagado = $this->paymentSchedules->sum('monto_pagado');
        $totalPendiente = $totalCuotas - $totalPagado;
        
        $cuotasVencidas = $this->paymentSchedules->filter(function($schedule) {
            return $schedule->estado === 'vencido' || 
                   ($schedule->fecha_vencimiento < now() && !$schedule->esta_pagado);
        })->count();

        return [
            'total_cuotas' => $totalCuotas,
            'total_pagado' => $totalPagado,
            'total_pendiente' => $totalPendiente,
            'porcentaje_pagado' => $totalCuotas > 0 ? round(($totalPagado / $totalCuotas) * 100, 2) : 100,
            'cuotas_vencidas' => $cuotasVencidas,
            'cuotas_totales' => $this->paymentSchedules->count(),
            'cuotas_pagadas' => $this->paymentSchedules->filter(fn($s) => $s->esta_pagado)->count(),
            'es_solvente' => $this->solvente,
        ];
    }
}