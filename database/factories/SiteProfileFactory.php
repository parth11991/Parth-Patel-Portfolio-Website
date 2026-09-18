<?php

namespace Database\Factories;

use App\Models\SiteProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteProfile>
 */
class SiteProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'professional_title' => fake()->jobTitle(),
            'hero_intro' => fake()->paragraph(),
            'about_text' => fake()->paragraphs(2, true),
            'location' => fake()->city(),
            'public_email' => fake()->safeEmail(),
            'public_phone' => fake()->optional()->numerify('+44##########'),
            'availability_text' => fake()->optional()->sentence(),
            'profile_image_path' => fake()->optional()->filePath(),
            'cv_path' => fake()->optional()->filePath(),
            'years_experience' => fake()->optional()->numberBetween(1, 25),
            'hero_code_lines' => fake()->optional()->randomElements(['npm run build', 'php artisan test'], 2),
            'about_question' => fake()->optional()->sentence(),
            'about_answer' => fake()->optional()->paragraph(),
        ];
    }
}
