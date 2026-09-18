<?php

namespace Tests\Feature;

use App\Enums\PortfolioStatus;
use App\Enums\SkillCategory;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\PortfolioMedia;
use App\Models\SiteProfile;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\SocialLink;
use Database\Seeders\CvContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cv_content_seeder_maps_factual_content_for_review(): void
    {
        SiteProfile::factory()->create();
        SiteSetting::factory()->create();

        $this->seed(CvContentSeeder::class);

        $profile = SiteProfile::query()->sole();
        $linkedin = SocialLink::query()->where('label', 'LinkedIn')->sole();
        $laravel = Skill::query()->where('name', 'Laravel')->sole();
        $experience = Experience::query()->where('company_name', 'Laxzo Ltd')->sole();
        $education = Education::query()->where('institution', 'Ahmedabad Institute Of Technology')->sole();
        $portfolio = Portfolio::query()->where('slug', 'semlis')->sole();

        $this->assertSame('Parth Patel', $profile->full_name);
        $this->assertSame('https://linkedin.com/in/webdeveloper-parth', $linkedin->url);
        $this->assertFalse($linkedin->is_visible);
        $this->assertSame(SkillCategory::Backend, $laravel->category);
        $this->assertFalse($laravel->is_visible);
        $this->assertSame('Senior Full Stack eCommerce SaaS Developer', $experience->role_title);
        $this->assertTrue($experience->is_current);
        $this->assertFalse($experience->is_visible);
        $this->assertSame('Computer Engineering', $education->field_of_study);
        $this->assertFalse($education->is_visible);
        $this->assertSame(PortfolioStatus::Draft, $portfolio->status);
        $this->assertSame(['Laravel 12', 'Vue.js 3', 'Tailwind CSS', 'Multi-Tenant SaaS', 'Software Architecture', 'Secure API Integrations'], $portfolio->technologies);
        $this->assertSame(0, PortfolioMedia::query()->count());
    }

    public function test_cv_content_seeder_is_idempotent_and_prevents_duplicates(): void
    {
        SiteProfile::factory()->create();
        SiteSetting::factory()->create();
        Skill::factory()->create([
            'name' => 'Laravel',
            'category' => SkillCategory::Backend,
            'is_visible' => true,
        ]);
        SocialLink::factory()->create([
            'label' => 'LinkedIn',
            'url' => 'https://example.com/manual-linkedin',
            'is_visible' => true,
        ]);

        $this->seed(CvContentSeeder::class);
        $counts = $this->contentCounts();
        $this->seed(CvContentSeeder::class);

        $this->assertSame($counts, $this->contentCounts());
        $this->assertSame(1, Skill::query()->where('name', 'Laravel')->count());
        $this->assertSame(1, SocialLink::query()->where('label', 'LinkedIn')->count());
        $this->assertSame('https://linkedin.com/in/webdeveloper-parth', SocialLink::query()->where('label', 'LinkedIn')->value('url'));
        $this->assertSame(1, SiteProfile::query()->count());
        $this->assertSame(1, SiteSetting::query()->count());
    }

    public function test_cv_content_seeder_preserves_unrelated_manual_records(): void
    {
        SiteProfile::factory()->create();
        SiteSetting::factory()->create();
        $manualSkill = Skill::factory()->create(['name' => 'Manual Skill']);
        $manualPortfolio = Portfolio::factory()->create(['slug' => 'manual-project']);

        $this->seed(CvContentSeeder::class);

        $this->assertModelExists($manualSkill);
        $this->assertModelExists($manualPortfolio);
        $this->assertSame(1, Skill::query()->where('name', 'Manual Skill')->count());
        $this->assertSame(1, Portfolio::query()->where('slug', 'manual-project')->count());
    }

    /**
     * @return array<string, int>
     */
    private function contentCounts(): array
    {
        return [
            'site_profiles' => SiteProfile::query()->count(),
            'site_settings' => SiteSetting::query()->count(),
            'social_links' => SocialLink::query()->count(),
            'skills' => Skill::query()->count(),
            'experiences' => Experience::query()->count(),
            'education' => Education::query()->count(),
            'portfolios' => Portfolio::query()->count(),
            'portfolio_media' => PortfolioMedia::query()->count(),
        ];
    }
}
