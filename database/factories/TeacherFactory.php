<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_code' => $this->faker->unique()->numerify('EMP-####'),
            'specialization' => $this->faker->randomElement(['Mathematics', 'Science', 'History', 'Literature', 'Art']),
            'degree' => $this->faker->randomElement(['Bachelor\'s', 'Master\'s', 'PhD']),
            'years_experience' => $this->faker->numberBetween(1, 20),
            'hire_date' => $this->faker->date(),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
             'empresa_id' => 1,
             'sucursal_id' => 1,
            'created_by' => 1, // Assuming user with ID 1 is an admin
            'updated_by' => 1,
        ];
    }
}
