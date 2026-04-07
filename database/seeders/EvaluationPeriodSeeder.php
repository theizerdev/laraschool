<?php

namespace Database\Seeders;

use App\Models\EvaluationPeriod;
use App\Models\SchoolPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EvaluationPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EvaluationPeriod::query()->delete();

        $schoolPeriods = SchoolPeriod::all();

        if ($schoolPeriods->isEmpty()) {
            $this->command->info('No school periods found, skipping evaluation period seeding.');
            return;
        }

        foreach ($schoolPeriods as $schoolPeriod) {
            $startDate = Carbon::parse($schoolPeriod->start_date);
            $endDate = Carbon::parse($schoolPeriod->end_date);
            $totalDays = $startDate->diffInDays($endDate);
            $daysPerPeriod = floor($totalDays / 3);

            // Create 3 evaluation periods for each school period
            for ($i = 1; $i <= 3; $i++) {
                $periodStartDate = $startDate->copy()->addDays($daysPerPeriod * ($i - 1));
                $periodEndDate = $periodStartDate->copy()->addDays($daysPerPeriod - 1);

                if ($i === 3) {
                    $periodEndDate = $endDate; // Ensure the last period ends on the exact date
                }

                EvaluationPeriod::create([
                    'empresa_id' => $schoolPeriod->empresa_id,
                    'sucursal_id' => $schoolPeriod->sucursal_id,
                    'school_period_id' => $schoolPeriod->id,
                    'name' => "Lapso {$i}",
                    'number' => $i,
                    'start_date' => $periodStartDate,
                    'end_date' => $periodEndDate,
                    'weight' => ($i === 3) ? 34 : 33, // Assign weights 33, 33, 34
                    'is_active' => true,
                    'is_closed' => false,
                    'description' => "Lapso de evaluación número {$i} para el período escolar {$schoolPeriod->name}.",
                ]);
            }
        }
    }
}
