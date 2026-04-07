<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Multitenantable;

class Subject extends Model
{
    use HasFactory, Multitenantable;

    protected $table = 'subjects';

    protected $fillable = [
        'name',
        'code',
        'description',
        'credits',
        'hours_per_week',
        'program_id',
        'educational_level_id',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Programa::class, 'program_id');
    }

    public function educationalLevel(): BelongsTo
    {
        return $this->belongsTo(NivelEducativo::class, 'educational_level_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'subject_teacher', 'subject_id', 'teacher_id')
                    ->withPivot('assigned_date', 'academic_period', 'is_primary')
                    ->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'subject_student', 'subject_id', 'student_id')
                    ->withPivot('enrollment_date', 'status', 'final_grade')
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

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class, 'program_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SubjectSchedule::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Relación con los prerrequisitos de la materia
     */
    public function prerequisites(): HasMany
    {
        return $this->hasMany(SubjectPrerequisite::class, 'subject_id');
    }

    /**
     * Relación con las materias donde esta materia es prerrequisito
     */
    public function prerequisiteOf(): HasMany
    {
        return $this->hasMany(SubjectPrerequisite::class, 'prerequisite_subject_id');
    }

    /**
     * Relación con los planes de estudio de la materia
     */
    public function studyPlans(): BelongsToMany
    {
        return $this->belongsToMany(StudyPlan::class, 'study_plan_subjects', 'subject_id', 'study_plan_id')
                    ->withPivot('semester', 'year', 'subject_type', 'order', 'is_active')
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeByEducationalLevel($query, $levelId)
    {
        return $query->where('educational_level_id', $levelId);
    }
}
