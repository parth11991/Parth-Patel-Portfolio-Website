<?php

namespace Database\Factories;

use App\Enums\SiteTheme;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_name' => fake()->company(),
            'default_theme' => fake()->randomElement(SiteTheme::cases()),
            'accent_color' => fake()->hexColor(),
            'meta_title' => fake()->sentence(4),
            'meta_description' => fake()->sentence(12),
            'canonical_url' => fake()->optional()->url(),
            'og_image_path' => fake()->optional()->filePath(),
            'contact_recipient_email' => fake()->optional()->safeEmail(),
        ];
    }
}
