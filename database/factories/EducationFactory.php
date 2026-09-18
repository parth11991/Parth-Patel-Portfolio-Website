<?php

namespace Database\Factories;

use App\Models\Education;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'institution' => fake()->company(),
            'qualification' => fake()->sentence(3),
            'field_of_study' => fake()->optional()->words(2, true),
            'started_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-10 years', '-3 years')->format('Y-m-d') : null,
            'ended_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d') : null,
            'description' => fake()->optional()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_visible' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
