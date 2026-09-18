<?php

namespace Tests\Feature;

use App\Models\SiteProfile;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SeoFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_points_to_sitemap_and_blocks_private_paths(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('User-agent: *', false)
            ->assertSee('Allow: /', false)
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Disallow: /login', false)
            ->assertSee('Sitemap: '.route('sitemap'), false);
    }

    public function test_sitemap_lists_home_with_recent_content_timestamp(): void
    {
        SiteSetting::factory()->create(['updated_at' => '2026-08-01 12:00:00']);
        SiteProfile::factory()->create(['updated_at' => '2026-08-02 12:00:00']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.route('home').'</loc>', false)
            ->assertSee('<lastmod>2026-08-02T12:00:00+00:00</lastmod>', false);
    }

    public function test_missing_page_renders_accessible_inertia_404(): void
    {
        $this->get('/missing-page')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', 404)
                ->where('auth.user', null)
            );
    }
}
