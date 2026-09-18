<?php

namespace Database\Factories;

use App\Models\MorePortfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MorePortfolio>
 */
class MorePortfolioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->company(),
            'project_url' => fake()->optional()->url(),
            'technologies' => fake()->randomElements(['Laravel', 'Shopify', 'WordPress', 'CodeIgniter', 'Core PHP'], 2),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_visible' => true,
        ];
    }
}
