<?php

namespace App\Exports;

use App\Models\RecoveryPeriod;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RecoveryPeriodReportExport implements FromView, WithTitle, ShouldAutoSize
{
    protected $recoveryPeriod;

    public function __construct(RecoveryPeriod $recoveryPeriod)
    {
        $this->recoveryPeriod = $recoveryPeriod;
    }

    public function view(): View
    {
        $students = $this->recoveryPeriod->recoveryEnrollments()->with(['student', 'subject'])->get();

        return view('exports.recovery-period-report', [
            'period' => $this->recoveryPeriod,
            'students' => $students
        ]);
    }

    public function title(): string
    {
        return 'Reporte de Recuperación';
    }
}
