<?php

namespace Database\Factories;

use App\Enums\PortfolioMediaFit;
use App\Enums\PortfolioMediaType;
use App\Models\Portfolio;
use App\Models\PortfolioMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioMedia>
 */
class PortfolioMediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'path' => 'portfolio/'.fake()->uuid().'.jpg',
            'media_type' => fake()->randomElement(PortfolioMediaType::cases()),
            'fit' => fake()->randomElement(PortfolioMediaFit::cases()),
            'alt_text' => fake()->sentence(4),
            'is_primary' => false,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
