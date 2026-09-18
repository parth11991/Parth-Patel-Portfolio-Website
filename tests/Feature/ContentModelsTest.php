<?php

namespace Tests\Feature;

use App\Enums\PortfolioMediaFit;
use App\Enums\PortfolioMediaType;
use App\Enums\PortfolioStatus;
use App\Enums\SiteTheme;
use App\Enums\SkillCategory;
use App\Models\ContactMessage;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\PortfolioMedia;
use App\Models\SiteProfile;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_cast_domain_attributes(): void
    {
        $user = User::factory()->admin()->create();
        $profile = SiteProfile::factory()->create([
            'hero_code_lines' => ['composer test', 'npm run build'],
            'years_experience' => 7,
        ]);
        $settings = SiteSetting::factory()->create(['default_theme' => SiteTheme::Dark]);
        $skill = Skill::factory()->create(['category' => SkillCategory::Backend, 'is_visible' => false]);
        $portfolio = Portfolio::factory()->published()->create(['technologies' => ['Laravel', 'Vue']]);
        $media = PortfolioMedia::factory()->create([
            'portfolio_id' => $portfolio->id,
            'media_type' => PortfolioMediaType::Logo,
            'fit' => PortfolioMediaFit::Contain,
        ]);
        $message = ContactMessage::factory()->read()->create();

        $this->assertTrue($user->is_admin);
        $this->assertSame(['composer test', 'npm run build'], $profile->hero_code_lines);
        $this->assertSame(7, $profile->years_experience);
        $this->assertSame(SiteTheme::Dark, $settings->default_theme);
        $this->assertSame(SkillCategory::Backend, $skill->category);
        $this->assertFalse($skill->is_visible);
        $this->assertSame(PortfolioStatus::Published, $portfolio->status);
        $this->assertSame(['Laravel', 'Vue'], $portfolio->technologies);
        $this->assertSame(PortfolioMediaType::Logo, $media->media_type);
        $this->assertSame(PortfolioMediaFit::Contain, $media->fit);
        $this->assertNotNull($message->read_at);
    }

    public function test_visible_and_published_scopes_filter_content(): void
    {
        SocialLink::factory()->create(['label' => 'Visible']);
        SocialLink::factory()->hidden()->create(['label' => 'Hidden']);
        Skill::factory()->create(['name' => 'Visible Skill']);
        Skill::factory()->hidden()->create(['name' => 'Hidden Skill']);
        Experience::factory()->create(['company_name' => 'Visible Co']);
        Experience::factory()->hidden()->create(['company_name' => 'Hidden Co']);
        Education::factory()->create(['institution' => 'Visible School']);
        Education::factory()->hidden()->create(['institution' => 'Hidden School']);
        Portfolio::factory()->published()->create(['title' => 'Published']);
        Portfolio::factory()->create(['title' => 'Draft']);

        $this->assertSame(['Visible'], SocialLink::visible()->pluck('label')->all());
        $this->assertSame(['Visible Skill'], Skill::visible()->pluck('name')->all());
        $this->assertSame(['Visible Co'], Experience::visible()->pluck('company_name')->all());
        $this->assertSame(['Visible School'], Education::visible()->pluck('institution')->all());
        $this->assertSame(['Published'], Portfolio::published()->pluck('title')->all());
    }

    public function test_portfolio_relationships_and_delete_rules(): void
    {
        $experience = Experience::factory()->create();
        $portfolio = Portfolio::factory()->for($experience)->create();
        $media = PortfolioMedia::factory()->for($portfolio)->create();

        $this->assertTrue($portfolio->experience->is($experience));
        $this->assertTrue($experience->portfolios->first()->is($portfolio));

        $experience->delete();
        $this->assertNull($portfolio->refresh()->experience_id);

        $portfolio->delete();
        $this->assertModelMissing($media);
    }

    public function test_portfolio_media_are_ordered(): void
    {
        $portfolio = Portfolio::factory()->create();
        PortfolioMedia::factory()->for($portfolio)->create(['alt_text' => 'Third', 'sort_order' => 30]);
        PortfolioMedia::factory()->for($portfolio)->create(['alt_text' => 'First', 'sort_order' => 10]);
        PortfolioMedia::factory()->for($portfolio)->create(['alt_text' => 'Second', 'sort_order' => 20]);

        $this->assertSame(['First', 'Second', 'Third'], $portfolio->refresh()->media->pluck('alt_text')->all());
    }

    public function test_only_one_primary_media_item_exists_per_portfolio(): void
    {
        $portfolio = Portfolio::factory()->create();
        $first = PortfolioMedia::factory()->for($portfolio)->create(['is_primary' => false]);
        $second = PortfolioMedia::factory()->for($portfolio)->primary()->create();
        $third = PortfolioMedia::factory()->for($portfolio)->primary()->create();

        $this->assertTrue($first->refresh()->is_primary === false);
        $this->assertTrue($second->refresh()->is_primary === false);
        $this->assertTrue($third->refresh()->is_primary);
        $this->assertSame(1, $portfolio->media()->where('is_primary', true)->count());

        $first->forceFill(['is_primary' => true])->save();

        $this->assertTrue($first->refresh()->is_primary);
        $this->assertFalse($third->refresh()->is_primary);
        $this->assertSame(1, $portfolio->media()->where('is_primary', true)->count());
    }

    public function test_first_media_item_becomes_primary_when_none_exists(): void
    {
        $portfolio = Portfolio::factory()->create();

        $media = PortfolioMedia::factory()->for($portfolio)->create(['is_primary' => false]);

        $this->assertTrue($media->refresh()->is_primary);
    }
}
