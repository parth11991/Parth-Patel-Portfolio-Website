<?php

namespace Tests\Feature;

use App\Enums\PortfolioStatus;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\PortfolioMedia;
use App\Models\SiteProfile;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\SocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_public_and_handles_empty_nullable_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('settings', null)
                ->where('profile', null)
                ->has('socialLinks', 0)
                ->has('skills', 0)
                ->has('experiences', 0)
                ->has('education', 0)
                ->has('portfolios', 0)
                ->has('structuredData', 0)
            );
    }

    public function test_home_page_is_safe_for_valid_section_hashes(): void
    {
        foreach (['#about', '#skills', '#experience', '#education', '#projects', '#contact'] as $hash) {
            $this->get('/'.$hash)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Home')
                );
        }
    }

    public function test_home_page_excludes_private_draft_and_hidden_content(): void
    {
        SiteProfile::factory()->create([
            'full_name' => 'Public Name',
            'public_email' => 'hello@example.test',
        ]);
        SiteSetting::factory()->create([
            'site_name' => 'Public Site',
            'contact_recipient_email' => 'private@example.test',
        ]);

        SocialLink::factory()->create(['label' => 'Visible Social']);
        SocialLink::factory()->hidden()->create(['label' => 'Hidden Social']);
        Skill::factory()->create(['name' => 'Visible Skill']);
        Skill::factory()->hidden()->create(['name' => 'Hidden Skill']);
        Experience::factory()->create(['company_name' => 'Visible Company']);
        $hiddenExperience = Experience::factory()
            ->hidden()
            ->create(['company_name' => 'Hidden Company']);
        Education::factory()->create(['institution' => 'Visible School']);
        Education::factory()->hidden()->create(['institution' => 'Hidden School']);

        Portfolio::factory()->create(['title' => 'Draft Project']);
        Portfolio::factory()->published()->create([
            'experience_id' => $hiddenExperience->id,
            'title' => 'Public Project',
            'full_description' => 'Private long project notes',
        ]);
        Portfolio::factory()->create([
            'title' => 'Future Project',
            'status' => PortfolioStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('settings.siteName', 'Public Site')
                ->missing('settings.contactRecipientEmail')
                ->where('profile.fullName', 'Public Name')
                ->has('socialLinks', 1)
                ->where('socialLinks.0.label', 'Visible Social')
                ->has('skills', 1)
                ->where('skills.0.name', 'Visible Skill')
                ->has('experiences', 1)
                ->where('experiences.0.companyName', 'Visible Company')
                ->has('education', 1)
                ->where('education.0.institution', 'Visible School')
                ->has('portfolios', 1)
                ->where('portfolios.0.title', 'Public Project')
                ->where('portfolios.0.experience', null)
                ->missing('portfolios.0.fullDescription')
                ->missing('portfolios.0.status')
            );
    }

    public function test_home_page_orders_public_content_and_eager_loads_project_media(): void
    {
        $experience = Experience::factory()->create([
            'company_name' => 'Studio',
            'sort_order' => 2,
            'started_at' => '2024-01-01',
        ]);
        Experience::factory()->create([
            'company_name' => 'Agency',
            'sort_order' => 1,
            'started_at' => '2022-01-01',
        ]);
        Skill::factory()->create(['name' => 'Vue', 'sort_order' => 2]);
        Skill::factory()->create(['name' => 'Laravel', 'sort_order' => 1]);
        Education::factory()->create([
            'institution' => 'Second School',
            'sort_order' => 2,
            'started_at' => '2020-01-01',
        ]);
        Education::factory()->create([
            'institution' => 'First School',
            'sort_order' => 1,
            'started_at' => '2019-01-01',
        ]);
        SocialLink::factory()->create(['label' => 'B Link', 'sort_order' => 2]);
        SocialLink::factory()->create(['label' => 'A Link', 'sort_order' => 1]);

        $later = Portfolio::factory()
            ->published()
            ->for($experience)
            ->create(['title' => 'Second Project', 'sort_order' => 2]);
        $first = Portfolio::factory()
            ->published()
            ->for($experience)
            ->create(['title' => 'First Project', 'sort_order' => 1]);
        PortfolioMedia::factory()->for($first)->create([
            'path' => 'portfolio/second.jpg',
            'media_type' => 'screenshot',
            'fit' => 'cover',
            'alt_text' => 'Second media',
            'sort_order' => 2,
        ]);
        PortfolioMedia::factory()->for($first)->primary()->create([
            'path' => 'portfolio/first.jpg',
            'media_type' => 'logo',
            'fit' => 'contain',
            'alt_text' => 'First media',
            'sort_order' => 1,
        ]);
        PortfolioMedia::factory()
            ->for($later)
            ->create(['path' => 'portfolio/later.jpg']);

        $response = $this->get('/')->assertOk();

        $this->assertSame(
            ['A Link', 'B Link'],
            array_column($response->inertiaProps('socialLinks'), 'label'),
        );
        $this->assertSame(
            ['Laravel', 'Vue'],
            array_column($response->inertiaProps('skills'), 'name'),
        );
        $this->assertSame(
            ['Agency', 'Studio'],
            array_column($response->inertiaProps('experiences'), 'companyName'),
        );
        $this->assertSame(
            ['First School', 'Second School'],
            array_column($response->inertiaProps('education'), 'institution'),
        );
        $this->assertSame(
            ['First Project', 'Second Project'],
            array_column($response->inertiaProps('portfolios'), 'title'),
        );
        $this->assertSame(
            ['First media', 'Second media'],
            array_column($response->inertiaProps('portfolios.0.media'), 'altText'),
        );
        $this->assertSame(
            ['logo', 'screenshot'],
            array_column($response->inertiaProps('portfolios.0.media'), 'type'),
        );
        $this->assertSame(
            ['contain', 'cover'],
            array_column($response->inertiaProps('portfolios.0.media'), 'fit'),
        );
        $this->assertSame(
            'Studio',
            $response->inertiaProps('portfolios.0.experience.companyName'),
        );
    }

    public function test_home_page_exposes_seo_metadata_and_json_ld_from_admin_content(): void
    {
        SiteProfile::factory()->create([
            'full_name' => 'Parth Patel',
            'professional_title' => 'Laravel Developer',
            'location' => 'London',
            'public_email' => 'hello@example.test',
            'public_phone' => '+441234567890',
            'profile_image_path' => 'profile/parth.jpg',
        ]);
        SiteSetting::factory()->create([
            'site_name' => 'Parth Portfolio',
            'meta_title' => 'Parth Patel - Laravel Developer',
            'meta_description' => 'Laravel and Vue portfolio.',
            'canonical_url' => 'https://example.test/',
            'og_image_path' => 'settings/og.jpg',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('settings.metaTitle', 'Parth Patel - Laravel Developer')
                ->where('settings.metaDescription', 'Laravel and Vue portfolio.')
                ->where('settings.canonicalUrl', 'https://example.test/')
                ->where('settings.ogImageUrl', url('/storage/settings/og.jpg'))
                ->has('structuredData', 2)
                ->where('structuredData.0.@type', 'Person')
                ->where('structuredData.0.name', 'Parth Patel')
                ->where('structuredData.0.jobTitle', 'Laravel Developer')
                ->where('structuredData.0.email', 'hello@example.test')
                ->where('structuredData.0.telephone', '+441234567890')
                ->where('structuredData.0.address.addressLocality', 'London')
                ->where('structuredData.0.image', url('/storage/profile/parth.jpg'))
                ->where('structuredData.1.@type', 'WebSite')
                ->where('structuredData.1.name', 'Parth Portfolio')
                ->where('structuredData.1.url', 'https://example.test/')
                ->where('structuredData.1.description', 'Laravel and Vue portfolio.')
            );
    }
}
