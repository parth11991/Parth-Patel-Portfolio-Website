<?php

namespace Tests\Feature;

use App\Enums\PortfolioMediaFit;
use App\Enums\PortfolioMediaType;
use App\Enums\PortfolioStatus;
use App\Filament\Resources\PortfolioResource\Pages\CreatePortfolio;
use App\Filament\Resources\PortfolioResource\Pages\EditPortfolio;
use App\Filament\Resources\PortfolioResource\Pages\ListPortfolios;
use App\Filament\Resources\PortfolioResource\RelationManagers\MediaRelationManager;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\PortfolioMedia;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPortfolioResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_portfolio_with_slug_experience_and_publish_flow(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $experience = Experience::factory()->create([
            'company_name' => 'OpenAI',
            'role_title' => 'Developer',
        ]);

        Livewire::test(CreatePortfolio::class)
            ->fillForm([
                'title' => 'Client Portal',
                'slug' => 'client-portal',
                'short_description' => 'Private portal build.',
                'full_description' => 'Long project notes.',
                'experience_id' => $experience->id,
                'project_url' => 'https://example.com',
                'repository_url' => 'https://github.com/example/client-portal',
                'started_at' => '2024-01-01',
                'completed_at' => '2024-03-01',
                'is_ongoing' => false,
                'technologies' => ['Laravel', 'Vue'],
                'role' => 'Full-stack developer',
                'outcome' => null,
                'sort_order' => 1,
                'status' => PortfolioStatus::Draft->value,
                'published_at' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $portfolio = Portfolio::query()->where('slug', 'client-portal')->sole();

        $this->assertTrue($portfolio->experience->is($experience));
        $this->assertNull($portfolio->outcome);
        $this->assertSame([], Portfolio::published()->pluck('title')->all());

        Livewire::test(EditPortfolio::class, ['record' => $portfolio->id])
            ->fillForm([
                'status' => PortfolioStatus::Published->value,
                'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(['Client Portal'], Portfolio::published()->pluck('title')->all());
    }

    public function test_portfolio_validation_rejects_duplicate_slug_bad_urls_and_dates(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Portfolio::factory()->create(['slug' => 'client-portal']);

        Livewire::test(CreatePortfolio::class)
            ->fillForm([
                'title' => 'Bad Project',
                'slug' => 'client-portal',
                'short_description' => 'Bad project.',
                'project_url' => 'not-a-url',
                'repository_url' => 'also-bad',
                'started_at' => '2024-05-01',
                'completed_at' => '2024-04-01',
                'is_ongoing' => false,
                'technologies' => ['Laravel'],
                'sort_order' => 0,
                'status' => PortfolioStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'slug' => 'unique',
                'project_url' => 'url',
                'repository_url' => 'url',
                'completed_at' => 'after_or_equal',
            ]);

        Livewire::test(CreatePortfolio::class)
            ->fillForm([
                'title' => 'Ongoing Project',
                'slug' => 'ongoing-project',
                'short_description' => 'Still active.',
                'started_at' => '2024-05-01',
                'completed_at' => '2024-06-01',
                'is_ongoing' => true,
                'technologies' => ['Laravel'],
                'sort_order' => 0,
                'status' => PortfolioStatus::Published->value,
                'published_at' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'completed_at' => 'prohibited',
                'published_at' => 'required',
            ]);
    }

    public function test_portfolio_list_search_filters_and_draft_scope(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $published = Portfolio::factory()->published()->create(['title' => 'Published Project', 'sort_order' => 10]);
        $draft = Portfolio::factory()->create(['title' => 'Draft Project', 'sort_order' => 20]);

        Livewire::test(ListPortfolios::class)
            ->assertCanSeeTableRecords([$published, $draft])
            ->searchTable('Published')
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft])
            ->filterTable('status', PortfolioStatus::Published->value)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft]);

        $this->assertSame(['Published Project'], Portfolio::published()->pluck('title')->all());
    }

    public function test_admin_can_upload_order_and_delete_portfolio_media(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $portfolio = Portfolio::factory()->create();

        Livewire::test(MediaRelationManager::class, [
            'ownerRecord' => $portfolio,
            'pageClass' => EditPortfolio::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'path' => UploadedFile::fake()->image('logo.png'),
                'media_type' => PortfolioMediaType::Logo->value,
                'fit' => PortfolioMediaFit::Contain->value,
                'alt_text' => 'Project logo',
                'is_primary' => true,
                'sort_order' => 20,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified()
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'path' => UploadedFile::fake()->image('screen.jpg'),
                'media_type' => PortfolioMediaType::Screenshot->value,
                'fit' => PortfolioMediaFit::Cover->value,
                'alt_text' => 'Project screenshot',
                'is_primary' => false,
                'sort_order' => 10,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $logo = PortfolioMedia::query()->where('alt_text', 'Project logo')->sole();
        $screenshot = PortfolioMedia::query()->where('alt_text', 'Project screenshot')->sole();

        $this->assertStringStartsWith("portfolio/{$portfolio->id}/", $logo->path);
        $this->assertSame(PortfolioMediaFit::Contain, $logo->fit);
        $this->assertSame(PortfolioMediaFit::Cover, $screenshot->fit);
        $this->assertSame(['Project screenshot', 'Project logo'], $portfolio->refresh()->media->pluck('alt_text')->all());
        Storage::disk('public')->assertExists($logo->path);
        Storage::disk('public')->assertExists($screenshot->path);

        Livewire::test(MediaRelationManager::class, [
            'ownerRecord' => $portfolio,
            'pageClass' => EditPortfolio::class,
        ])
            ->callAction(TestAction::make(EditAction::class)->table($screenshot), [
                'is_primary' => true,
                'sort_order' => 5,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified()
            ->callAction(TestAction::make(DeleteAction::class)->table($logo))
            ->assertNotified();

        $this->assertTrue($screenshot->refresh()->is_primary);
        $this->assertSame(1, $portfolio->media()->where('is_primary', true)->count());
        $this->assertModelMissing($logo);
        Storage::disk('public')->assertMissing($logo->path);
    }

    public function test_media_upload_rejects_svg_and_cascade_delete_cleans_files(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $portfolio = Portfolio::factory()->create();

        Livewire::test(MediaRelationManager::class, [
            'ownerRecord' => $portfolio,
            'pageClass' => EditPortfolio::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'path' => UploadedFile::fake()->create('logo.svg', 16, 'image/svg+xml'),
                'media_type' => PortfolioMediaType::Logo->value,
                'fit' => PortfolioMediaFit::Contain->value,
                'alt_text' => 'Bad SVG',
                'sort_order' => 0,
            ])
            ->assertHasActionErrors(['path']);

        Storage::disk('public')->put("portfolio/{$portfolio->id}/logo.jpg", 'image');
        Storage::disk('public')->put("portfolio/{$portfolio->id}/screen.jpg", 'image');
        $logo = PortfolioMedia::factory()->for($portfolio)->primary()->create([
            'path' => "portfolio/{$portfolio->id}/logo.jpg",
            'media_type' => PortfolioMediaType::Logo,
            'fit' => PortfolioMediaFit::Contain,
        ]);
        $screenshot = PortfolioMedia::factory()->for($portfolio)->create([
            'path' => "portfolio/{$portfolio->id}/screen.jpg",
            'media_type' => PortfolioMediaType::Screenshot,
            'fit' => PortfolioMediaFit::Cover,
        ]);

        $portfolio->delete();

        $this->assertModelMissing($portfolio);
        $this->assertModelMissing($logo);
        $this->assertModelMissing($screenshot);
        Storage::disk('public')->assertMissing("portfolio/{$portfolio->id}/logo.jpg");
        Storage::disk('public')->assertMissing("portfolio/{$portfolio->id}/screen.jpg");
    }

    public function test_non_admin_cannot_access_portfolio_resource(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/portfolios')->assertForbidden();
    }
}
