<?php

namespace Database\Factories;

use App\Enums\PortfolioStatus;
use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Portfolio>
 */
class PortfolioFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'experience_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'short_description' => fake()->sentence(12),
            'full_description' => fake()->optional()->paragraphs(3, true),
            'project_url' => fake()->optional()->url(),
            'repository_url' => fake()->optional()->url(),
            'started_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d') : null,
            'completed_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d') : null,
            'is_ongoing' => false,
            'technologies' => fake()->randomElements(['Laravel', 'Vue', 'TypeScript', 'Tailwind CSS'], 2),
            'role' => fake()->optional()->jobTitle(),
            'outcome' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(0, 100),
            'status' => PortfolioStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PortfolioStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }
}
