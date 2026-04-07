<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Multitenantable;
class Teacher extends Model
{
    use HasFactory, Multitenantable;

    protected $table = 'teachers';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'employee_code',
        'specialization',
        'degree',
        'years_experience',
        'hire_date',
        'empresa_id',
        'sucursal_id',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher', 'teacher_id', 'subject_id')
                    ->withPivot('assigned_date', 'academic_period', 'is_primary')
                    ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SubjectSchedule::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', 'like', "%{$specialization}%");
    }

    public function getFullNameAttribute(): string
    {
        return $this->name ?? 'Sin nombre';
    }

    public function getTeacherEmailAttribute(): string
    {
        return $this->email ?? '';
    }
}
