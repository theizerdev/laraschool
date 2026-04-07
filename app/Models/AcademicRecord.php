<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Multitenantable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicRecord extends Model
{
    use HasFactory, Multitenantable, LogsActivity, SoftDeletes;

    protected $fillable = [
        'student_id',
        'matricula_id',
        'school_period_id',
        'program_id',
        'educational_level_id',
        'subject_id',
        'empresa_id',
        'sucursal_id',
        'grade',
        'section',
        'status',
        'first_partial_grade',
        'second_partial_grade',
        'third_partial_grade',
        'final_grade',
        'average_grade',
        'promoted',
        'repeated',
        'in_recovery',
        'recovered',
        'observations',
        'teacher_observations',
        'total_classes',
        'attended_classes',
        'attendance_percentage',
        'enrollment_date',
        'completion_date',
    ];

    protected $casts = [
        'first_partial_grade' => 'decimal:2',
        'second_partial_grade' => 'decimal:2',
        'third_partial_grade' => 'decimal:2',
        'final_grade' => 'decimal:2',
        'average_grade' => 'decimal:2',
        'promoted' => 'boolean',
        'repeated' => 'boolean',
        'in_recovery' => 'boolean',
        'recovered' => 'boolean',
        'attendance_percentage' => 'decimal:2',
        'enrollment_date' => 'date',
        'completion_date' => 'date',
    ];

    const STATUS_ENROLLED = 'enrolled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_WITHDRAWN = 'withdrawn';
    const STATUS_IN_RECOVERY = 'in_recovery';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ENROLLED => 'Matriculado',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_FAILED => 'Reprobado',
            self::STATUS_WITHDRAWN => 'Retirado',
            self::STATUS_IN_RECOVERY => 'En Recuperación',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class, 'matricula_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolPeriod(): BelongsTo
    {
        return $this->belongsTo(SchoolPeriod::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Programa::class, 'program_id');
    }

    public function educationalLevel(): BelongsTo
    {
        return $this->belongsTo(EducationalLevel::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function recoveryPeriod(): BelongsTo
    {
        return $this->belongsTo(RecoveryPeriod::class, 'recovery_period_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('school_period_id', $periodId);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByGradeAndSection($query, $grade, $section)
    {
        return $query->where('grade', $grade)->where('section', $section);
    }

    public function scopePromoted($query)
    {
        return $query->where('promoted', true);
    }

    public function scopeRepeated($query)
    {
        return $query->where('repeated', true);
    }

    public function scopeInRecovery($query)
    {
        return $query->where('in_recovery', true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'final_grade',
                'status',
                'promoted',
                'repeated',
                'in_recovery',
                'recovered',
                'observations'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
