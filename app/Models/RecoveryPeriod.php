<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Multitenantable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryPeriod extends Model
{
    use HasFactory, Multitenantable, LogsActivity;

    protected $fillable = [
        'empresa_id',
        'sucursal_id',
        'name',
        'description',
        'school_period_id',
        'start_date',
        'end_date',
        'registration_start_date',
        'registration_end_date',
        'min_failing_grade',
        'max_failing_grade',
        'min_recovery_grade',
        'recovery_cost',
        'max_students_per_group',
        'total_hours',
        'classes_per_week',
        'class_duration',
        'is_active',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_start_date' => 'date',
        'registration_end_date' => 'date',
        'min_failing_grade' => 'decimal:2',
        'max_failing_grade' => 'decimal:2',
        'min_recovery_grade' => 'decimal:2',
        'recovery_cost' => 'decimal:2',
        'max_students_per_group' => 'integer',
        'total_hours' => 'integer',
        'classes_per_week' => 'integer',
        'class_duration' => 'integer',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function schoolPeriod(): BelongsTo
    {
        return $this->belongsTo(SchoolPeriod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recoveryEnrollments(): HasMany
    {
        return $this->hasMany(RecoveryEnrollment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopeBySchoolPeriod($query, $periodId)
    {
        return $query->where('school_period_id', $periodId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeRegistrationOpen($query)
    {
        return $query->where('is_active', true)
                    ->where('registration_start_date', '<=', now())
                    ->where('registration_end_date', '>=', now());
    }

    public function getStatusLabelAttribute(): string
    {
        $now = now();

        if (!$this->is_active) {
            return 'Inactivo';
        }

        if ($this->approved_at && $now < $this->start_date) {
            return 'Programado';
        }

        if ($now >= $this->start_date && $now <= $this->end_date) {
            return 'En Curso';
        }

        if ($now > $this->end_date) {
            return 'Finalizado';
        }

        return 'Pendiente';
    }

    public function getIsRegistrationOpenAttribute(): bool
    {
        $now = now();
        return $this->is_active &&
               $now >= $this->registration_start_date &&
               $now <= $this->registration_end_date;
    }

    public function getEnrolledStudentsCountAttribute(): int
    {
        return $this->recoveryEnrollments()
                   ->where('enrollment_status', 'approved')
                   ->count();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'description',
                'start_date',
                'end_date',
                'registration_start_date',
                'registration_end_date',
                'min_failing_grade',
                'max_failing_grade',
                'min_recovery_grade',
                'recovery_cost',
                'max_students_per_group',
                'total_hours',
                'classes_per_week',
                'class_duration',
                'is_active'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
