<?php

namespace Database\Factories;

use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialLink>
 */
class SocialLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => fake()->word(),
            'url' => fake()->url(),
            'icon_key' => fake()->slug(1),
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
