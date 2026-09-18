<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'role_title' => fake()->jobTitle(),
            'employment_type' => fake()->optional()->randomElement(['full-time', 'part-time', 'contract']),
            'location' => fake()->optional()->city(),
            'summary' => fake()->paragraph(),
            'started_at' => fake()->dateTimeBetween('-8 years', '-1 year')->format('Y-m-d'),
            'ended_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d') : null,
            'is_current' => false,
            'company_url' => fake()->optional()->url(),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_visible' => true,
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => null,
            'is_current' => true,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
