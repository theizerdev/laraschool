<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Using a factory to create a user and then a teacher
        $subjects = Subject::all();

        if ($subjects->isEmpty()) {
            $this->command->info('No subjects found, skipping teacher seeding.');
            return;
        }

        Teacher::factory(10)->create()->each(function ($teacher) use ($subjects) {
            $teacher->subjects()->attach(
                $subjects->random(rand(2, 5))->pluck('id')->toArray(),
                [
                    'assigned_date' => now(),
                    'academic_period' => '2024-2025',

                    'is_primary' => true
                ]
            );
        });
    }
}
