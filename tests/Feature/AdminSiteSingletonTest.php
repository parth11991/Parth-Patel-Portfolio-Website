<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteProfile as SiteProfilePage;
use App\Filament\Pages\SiteSettings as SiteSettingsPage;
use App\Models\SiteProfile;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class AdminSiteSingletonTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_site_profile_row_can_exist(): void
    {
        SiteProfile::factory()->create();

        $this->expectException(LogicException::class);
        SiteProfile::factory()->create();
    }

    public function test_only_one_site_settings_row_can_exist(): void
    {
        SiteSetting::factory()->create();

        $this->expectException(LogicException::class);
        SiteSetting::factory()->create();
    }

    public function test_admin_can_update_site_profile_and_import_source_assets(): void
    {
        Storage::fake('public');
        $createdSourceAssets = $this->createMissingSourceAssets();
        $admin = User::factory()->admin()->create();

        try {
            $this->actingAs($admin);

            Livewire::test(SiteProfilePage::class)
                ->fillForm([
                    'full_name' => 'Parth Patel',
                    'professional_title' => 'Senior Laravel Developer',
                    'hero_intro' => 'Building useful software.',
                    'about_text' => 'Practical engineering work.',
                    'location' => 'London',
                    'public_email' => 'parth@example.com',
                ])
                ->call('save')
                ->assertHasNoFormErrors()
                ->assertNotified();

            $profile = SiteProfile::query()->sole();

            $this->assertSame('Senior Laravel Developer', $profile->professional_title);
            $this->assertStringStartsWith('profile/', $profile->profile_image_path);
            $this->assertStringStartsWith('cv/', $profile->cv_path);
            $this->assertStringNotContainsString('source-assets', $profile->profile_image_path);
            $this->assertStringNotContainsString('source-assets', $profile->cv_path);
            Storage::disk('public')->assertExists($profile->profile_image_path);
            Storage::disk('public')->assertExists($profile->cv_path);
        } finally {
            foreach ($createdSourceAssets as $path) {
                File::delete($path);
            }
        }
    }

    public function test_admin_can_replace_profile_files_and_old_files_are_cleaned_up(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('profile/old-profile.jpg', 'old image');
        Storage::disk('public')->put('cv/old-cv.pdf', '%PDF-1.4 old');

        SiteProfile::factory()->create([
            'profile_image_path' => 'profile/old-profile.jpg',
            'cv_path' => 'cv/old-cv.pdf',
        ]);

        $this->actingAs($admin);

        Livewire::test(SiteProfilePage::class)
            ->fillForm([
                'profile_image_path' => UploadedFile::fake()->image('profile.png'),
                'cv_path' => UploadedFile::fake()->create('cv.pdf', 128, 'application/pdf'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $profile = SiteProfile::query()->sole();

        $this->assertStringStartsWith('profile/', $profile->profile_image_path);
        $this->assertStringStartsWith('cv/', $profile->cv_path);
        $this->assertNotSame('profile/old-profile.jpg', $profile->profile_image_path);
        $this->assertNotSame('cv/old-cv.pdf', $profile->cv_path);
        Storage::disk('public')->assertExists($profile->profile_image_path);
        Storage::disk('public')->assertExists($profile->cv_path);
        Storage::disk('public')->assertMissing('profile/old-profile.jpg');
        Storage::disk('public')->assertMissing('cv/old-cv.pdf');
    }

    public function test_admin_can_upload_four_megabyte_profile_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SiteProfilePage::class)
            ->fillForm([
                'profile_image_path' => UploadedFile::fake()->image('profile.jpg')->size(4096),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $profile = SiteProfile::query()->sole();

        $this->assertStringStartsWith('profile/', $profile->profile_image_path);
        Storage::disk('public')->assertExists($profile->profile_image_path);
    }

    public function test_site_profile_rejects_invalid_upload_types(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SiteProfilePage::class)
            ->fillForm([
                'profile_image_path' => UploadedFile::fake()->create('profile.svg', 16, 'image/svg+xml'),
                'cv_path' => UploadedFile::fake()->create('cv.txt', 16, 'text/plain'),
            ])
            ->call('save')
            ->assertHasFormErrors([
                'profile_image_path',
                'cv_path',
            ]);
    }

    public function test_admin_can_update_site_settings_and_replace_og_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('site/old-og.jpg', 'old image');

        SiteSetting::factory()->create([
            'og_image_path' => 'site/old-og.jpg',
        ]);

        $this->actingAs($admin);

        Livewire::test(SiteSettingsPage::class)
            ->fillForm([
                'site_name' => 'Parth Patel',
                'default_theme' => 'dark',
                'accent_color' => '#0f766e',
                'meta_title' => 'Parth Patel Portfolio',
                'meta_description' => 'Portfolio metadata.',
                'canonical_url' => 'https://example.com',
                'contact_recipient_email' => 'contact@example.com',
                'og_image_path' => UploadedFile::fake()->image('og.webp'),
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = SiteSetting::query()->sole();

        $this->assertSame('Parth Patel', $settings->site_name);
        $this->assertSame('dark', $settings->default_theme->value);
        $this->assertStringStartsWith('site/', $settings->og_image_path);
        Storage::disk('public')->assertExists($settings->og_image_path);
        Storage::disk('public')->assertMissing('site/old-og.jpg');
    }

    public function test_site_settings_rejects_invalid_og_image_and_drops_unsafe_paths(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(SiteSettingsPage::class)
            ->fillForm([
                'og_image_path' => UploadedFile::fake()->create('og.svg', 16, 'image/svg+xml'),
            ])
            ->call('save')
            ->assertHasFormErrors(['og_image_path']);

        Livewire::test(SiteSettingsPage::class)
            ->fillForm([
                'og_image_path' => '../private/secret.png',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(SiteSetting::query()->sole()->og_image_path);
    }

    public function test_non_admin_cannot_access_site_singleton_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/site-profile')->assertForbidden();
        $this->actingAs($user)->get('/admin/site-settings')->assertForbidden();
    }

    /**
     * @return array<int, string>
     */
    private function createMissingSourceAssets(): array
    {
        $created = [];
        $profileDirectory = base_path('source-assets/profile');
        $cvDirectory = base_path('source-assets/cv');

        if (! File::isDirectory($profileDirectory)) {
            File::ensureDirectoryExists($profileDirectory);
        }

        if (! File::isDirectory($cvDirectory)) {
            File::ensureDirectoryExists($cvDirectory);
        }

        if (File::files($profileDirectory) === []) {
            $path = $profileDirectory.'/test-profile.png';
            File::put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true));
            $created[] = $path;
        }

        if (File::files($cvDirectory) === []) {
            $path = $cvDirectory.'/test-cv.pdf';
            File::put($path, "%PDF-1.4\n%%EOF");
            $created[] = $path;
        }

        return $created;
    }
}
