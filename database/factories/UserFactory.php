<?php

namespace Database\Factories;

use App\Enums\UserLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'password' => 'password',
            'level' => UserLevel::Management,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'active' => true,
        ];
    }

    public function level(UserLevel $level): static
    {
        return $this->state(['level' => $level]);
    }
}
