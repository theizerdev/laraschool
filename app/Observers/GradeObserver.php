<?php

namespace App\Observers;

use App\Models\Grade;
use App\Models\AcademicRecord;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Matricula;

class GradeObserver
{
    /**
     * Handle the Grade "created" event.
     */
    public function created(Grade $grade): void
    {
        $this->updateAcademicRecord($grade);
    }

    /**
     * Handle the Grade "updated" event.
     */
    public function updated(Grade $grade): void
    {
        $this->updateAcademicRecord($grade);
    }

    /**
     * Handle the Grade "deleted" event.
     */
    public function deleted(Grade $grade): void
    {
        $this->updateAcademicRecord($grade, true);
    }

    /**
     * Handle the Grade "restored" event.
     */
    public function restored(Grade $grade): void
    {
        $this->updateAcademicRecord($grade);
    }

    /**
     * Handle the Grade "force deleted" event.
     */
    public function forceDeleted(Grade $grade): void
    {
        //
    }

    protected function updateAcademicRecord(Grade $grade, $isDeleted = false)
    {
        $evaluation = $grade->evaluation;
        $student = $grade->student;

        if (!$evaluation || !$student) {
            return;
        }

        $evaluationPeriod = $evaluation->evaluationPeriod;
        $schoolPeriodId = $evaluationPeriod->school_period_id;

        if (!$evaluationPeriod || !$schoolPeriodId) {
            return;
        }

        // Find the corresponding matricula
        $matricula = Matricula::where('estudiante_id', $student->id)
            ->where('periodo_id', $schoolPeriodId)
            ->first();

        // If no matricula is found, we can't proceed
        if (!$matricula) {
            return;
        }

        $academicRecord = AcademicRecord::firstOrCreate(
            [
                'student_id' => $student->id,
                'subject_id' => $evaluation->subject_id,
                'school_period_id' => $schoolPeriodId,
                'matricula_id' => $matricula->id,
            ],
            [
                'program_id' => $evaluation->subject->program_id,
                'educational_level_id' => $student->nivel_educativo_id,

                'grade' => $student->nivel_educativo_id,
                'section' => $student->seccion,
                'empresa_id' => $student->empresa_id,
                'sucursal_id' => $student->sucursal_id,
                'status' => AcademicRecord::STATUS_ENROLLED,
            ]
        );

        // Asignar la nota a la columna correcta (parcial o final)
        $evaluationType = $evaluation->evaluationType->name;
        $partialGradeColumn = match ($evaluationType) {
            'Parcial 1' => 'first_partial_grade',
            'Parcial 2' => 'second_partial_grade',
            'Parcial 3' => 'third_partial_grade',
            default => null,
        };

        if ($partialGradeColumn) {
            $academicRecord->{$partialGradeColumn} = $isDeleted ? null : $grade->score;
        }

        // Recalcular siempre la nota final
        $this->recalculateFinalGrade($academicRecord);

        $academicRecord->status = $this->determineStatus($academicRecord->final_grade);

        $academicRecord->save();
    }

    protected function recalculateFinalGrade(AcademicRecord $academicRecord)
    {
        // Obtener todas las evaluaciones para la materia del registro académico
        $evaluations = Evaluation::where('subject_id', $academicRecord->subject_id)
            ->whereHas('evaluationPeriod', function ($query) use ($academicRecord) {
                $query->where('school_period_id', $academicRecord->school_period_id);
            })
            ->get();

        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($evaluations as $evaluation) {
            // Buscar la nota del estudiante para esta evaluación específica
            $grade = Grade::where('student_id', $academicRecord->student_id)
                        ->where('evaluation_id', $evaluation->id)
                        ->first();

            if ($grade) {
                $weightedSum += $grade->score * ($evaluation->weight / 100);
                $totalWeight += $evaluation->weight;
            }
        }

        // Si no hay notas, la nota final es null
        if ($totalWeight > 0) {
            // La nota final es la suma ponderada. No se necesita división si el peso total es 100.
            $academicRecord->final_grade = $weightedSum;
        } else {
            $academicRecord->final_grade = null;
        }
    }

    protected function determineStatus($finalGrade)
    {
        if ($finalGrade === null) {
            return AcademicRecord::STATUS_ENROLLED;
        }

        if ($finalGrade >= 10) {
            return AcademicRecord::STATUS_COMPLETED;
        }

        return AcademicRecord::STATUS_FAILED;
    }
}
