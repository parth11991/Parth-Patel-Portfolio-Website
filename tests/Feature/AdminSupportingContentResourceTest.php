<?php

namespace Tests\Feature;

use App\Enums\SkillCategory;
use App\Filament\Resources\EducationResource\Pages\CreateEducation;
use App\Filament\Resources\EducationResource\Pages\EditEducation;
use App\Filament\Resources\EducationResource\Pages\ListEducation;
use App\Filament\Resources\ExperienceResource\Pages\CreateExperience;
use App\Filament\Resources\ExperienceResource\Pages\EditExperience;
use App\Filament\Resources\ExperienceResource\Pages\ListExperiences;
use App\Filament\Resources\SkillResource\Pages\CreateSkill;
use App\Filament\Resources\SkillResource\Pages\EditSkill;
use App\Filament\Resources\SkillResource\Pages\ListSkills;
use App\Filament\Resources\SocialLinkResource\Pages\CreateSocialLink;
use App\Filament\Resources\SocialLinkResource\Pages\EditSocialLink;
use App\Filament\Resources\SocialLinkResource\Pages\ListSocialLinks;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSupportingContentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_search_filter_update_and_delete_social_links(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateSocialLink::class)
            ->fillForm([
                'label' => 'GitHub',
                'url' => 'https://github.com/parth',
                'icon_key' => 'github',
                'sort_order' => 10,
                'is_visible' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $link = SocialLink::query()->sole();

        Livewire::test(ListSocialLinks::class)
            ->assertCanSeeTableRecords([$link])
            ->searchTable('GitHub')
            ->assertCanSeeTableRecords([$link])
            ->filterTable('is_visible', true)
            ->assertCanSeeTableRecords([$link]);

        Livewire::test(EditSocialLink::class, ['record' => $link->id])
            ->fillForm(['is_visible' => false, 'sort_order' => 20])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertModelMissing($link);
    }

    public function test_social_links_validate_external_urls(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateSocialLink::class)
            ->fillForm([
                'label' => 'Broken',
                'url' => 'not-a-url',
                'icon_key' => 'link',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['url' => 'url']);
    }

    public function test_admin_can_manage_skills_without_inventing_proficiency(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $hidden = Skill::factory()->hidden()->create(['name' => 'Hidden Skill']);

        Livewire::test(CreateSkill::class)
            ->fillForm([
                'name' => 'Laravel',
                'category' => SkillCategory::Backend->value,
                'icon_key' => 'laravel',
                'proficiency' => null,
                'sort_order' => 5,
                'is_visible' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $skill = Skill::query()->where('name', 'Laravel')->sole();

        $this->assertNull($skill->proficiency);

        Livewire::test(ListSkills::class)
            ->searchTable('Laravel')
            ->assertCanSeeTableRecords([$skill])
            ->assertCanNotSeeTableRecords([$hidden])
            ->filterTable('category', SkillCategory::Backend->value)
            ->assertCanSeeTableRecords([$skill]);

        Livewire::test(EditSkill::class, ['record' => $skill->id])
            ->fillForm(['proficiency' => 80, 'is_visible' => false])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(80, $skill->refresh()->proficiency);
        $this->assertFalse($skill->is_visible);
    }

    public function test_skills_validate_category_and_proficiency(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateSkill::class)
            ->fillForm([
                'name' => 'Bad Skill',
                'category' => 'invalid',
                'proficiency' => 101,
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'category',
                'proficiency' => 'max',
            ]);
    }

    public function test_admin_can_manage_experience_and_current_role_may_omit_end_date(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $hidden = Experience::factory()->hidden()->create(['company_name' => 'Hidden Co']);

        Livewire::test(CreateExperience::class)
            ->fillForm([
                'company_name' => 'OpenAI',
                'role_title' => 'Developer',
                'employment_type' => 'Full-time',
                'location' => 'Remote',
                'summary' => 'Build systems.',
                'started_at' => '2024-01-01',
                'ended_at' => null,
                'is_current' => true,
                'company_url' => 'https://openai.com',
                'sort_order' => 1,
                'is_visible' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $experience = Experience::query()->where('company_name', 'OpenAI')->sole();

        $this->assertNull($experience->ended_at);

        Livewire::test(ListExperiences::class)
            ->searchTable('OpenAI')
            ->assertCanSeeTableRecords([$experience])
            ->assertCanNotSeeTableRecords([$hidden])
            ->filterTable('is_current', true)
            ->assertCanSeeTableRecords([$experience]);

        Livewire::test(EditExperience::class, ['record' => $experience->id])
            ->fillForm(['is_visible' => false, 'sort_order' => 2])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertFalse($experience->refresh()->is_visible);
    }

    public function test_experience_rejects_invalid_date_ranges_and_urls(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateExperience::class)
            ->fillForm([
                'company_name' => 'Bad Co',
                'role_title' => 'Developer',
                'summary' => 'Build systems.',
                'started_at' => '2024-05-01',
                'ended_at' => '2024-04-01',
                'is_current' => false,
                'company_url' => 'not-a-url',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'ended_at' => 'after_or_equal',
                'company_url' => 'url',
            ]);

        Livewire::test(CreateExperience::class)
            ->fillForm([
                'company_name' => 'Past Co',
                'role_title' => 'Developer',
                'summary' => 'Build systems.',
                'started_at' => '2024-05-01',
                'ended_at' => null,
                'is_current' => false,
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['ended_at' => 'required']);
    }

    public function test_admin_can_manage_education(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $hidden = Education::factory()->hidden()->create(['institution' => 'Hidden School']);

        Livewire::test(CreateEducation::class)
            ->fillForm([
                'institution' => 'University',
                'qualification' => 'BSc',
                'field_of_study' => 'Computer Science',
                'started_at' => '2020-09-01',
                'ended_at' => '2023-06-01',
                'description' => 'Studied software.',
                'sort_order' => 3,
                'is_visible' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $education = Education::query()->where('institution', 'University')->sole();

        Livewire::test(ListEducation::class)
            ->searchTable('University')
            ->assertCanSeeTableRecords([$education])
            ->assertCanNotSeeTableRecords([$hidden])
            ->filterTable('is_visible', true)
            ->assertCanSeeTableRecords([$education]);

        Livewire::test(EditEducation::class, ['record' => $education->id])
            ->fillForm(['is_visible' => false, 'sort_order' => 4])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->callAction(DeleteAction::class)
            ->assertNotified();

        $this->assertModelMissing($education);
    }

    public function test_education_rejects_invalid_date_ranges(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateEducation::class)
            ->fillForm([
                'institution' => 'Bad University',
                'qualification' => 'BSc',
                'started_at' => '2023-06-01',
                'ended_at' => '2020-09-01',
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['ended_at' => 'after_or_equal']);
    }

    public function test_non_admin_cannot_access_supporting_content_resources(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/social-links')->assertForbidden();
        $this->actingAs($user)->get('/admin/skills')->assertForbidden();
        $this->actingAs($user)->get('/admin/experiences')->assertForbidden();
        $this->actingAs($user)->get('/admin/education')->assertForbidden();
    }
}
