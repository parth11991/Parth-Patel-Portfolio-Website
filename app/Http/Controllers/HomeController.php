<?php

namespace App\Http\Controllers;

use App\Models\Education;
use App\Models\Experience;
use App\Models\MorePortfolio;
use App\Models\Portfolio;
use App\Models\PortfolioMedia;
use App\Models\SiteProfile;
use App\Models\SiteSetting;
use App\Models\Skill;
use App\Models\SocialLink;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = SiteProfile::query()
            ->select([
                'full_name',
                'professional_title',
                'hero_intro',
                'about_text',
                'location',
                'public_email',
                'public_phone',
                'availability_text',
                'profile_image_path',
                'cv_path',
                'years_experience',
                'hero_code_lines',
                'about_question',
                'about_answer',
            ])
            ->first();

        $settings = SiteSetting::query()
            ->select([
                'site_name',
                'default_theme',
                'accent_color',
                'meta_title',
                'meta_description',
                'canonical_url',
                'og_image_path',
            ])
            ->first();

        $canonicalUrl = $settings?->canonical_url ?: $request->url();
        $ogImageUrl = $this->absolutePublicUrl($settings?->og_image_path);

        return Inertia::render('Home', [
            'settings' => $settings ? [
                'siteName' => $settings->site_name,
                'defaultTheme' => $this->enumValue($settings->default_theme),
                'accentColor' => $settings->accent_color,
                'metaTitle' => $settings->meta_title,
                'metaDescription' => $settings->meta_description,
                'canonicalUrl' => $canonicalUrl,
                'ogImageUrl' => $ogImageUrl,
            ] : null,
            'profile' => $profile ? [
                'fullName' => $profile->full_name,
                'professionalTitle' => $profile->professional_title,
                'heroIntro' => $profile->hero_intro,
                'aboutText' => $profile->about_text,
                'location' => $profile->location,
                'publicEmail' => $profile->public_email,
                'publicPhone' => $profile->public_phone,
                'availabilityText' => $profile->availability_text,
                'profileImageUrl' => $this->publicUrl($profile->profile_image_path),
                'cvUrl' => $this->publicUrl($profile->cv_path),
                'yearsExperience' => $profile->years_experience,
                'heroCodeLines' => $profile->hero_code_lines ?? [],
                'aboutQuestion' => $profile->about_question,
                'aboutAnswer' => $profile->about_answer,
            ] : null,
            'structuredData' => $this->structuredData($settings, $profile, $canonicalUrl, $ogImageUrl),
            'socialLinks' => SocialLink::query()
                ->select(['label', 'url', 'icon_key'])
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (SocialLink $link): array => [
                    'label' => $link->label,
                    'url' => $link->url,
                    'iconKey' => $link->icon_key,
                ])
                ->values()
                ->all(),
            'skills' => Skill::query()
                ->select(['name', 'category', 'icon_key', 'proficiency'])
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (Skill $skill): array => [
                    'name' => $skill->name,
                    'category' => $this->enumValue($skill->category),
                    'iconKey' => $skill->icon_key,
                    'proficiency' => $skill->proficiency,
                ])
                ->values()
                ->all(),
            'experiences' => Experience::query()
                ->select([
                    'id',
                    'company_name',
                    'role_title',
                    'employment_type',
                    'location',
                    'summary',
                    'started_at',
                    'ended_at',
                    'is_current',
                    'company_url',
                ])
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (Experience $experience): array => [
                    'id' => $experience->id,
                    'companyName' => $experience->company_name,
                    'roleTitle' => $experience->role_title,
                    'employmentType' => $experience->employment_type,
                    'location' => $experience->location,
                    'summary' => $experience->summary,
                    'startedAt' => $this->dateString($experience->started_at),
                    'endedAt' => $this->dateString($experience->ended_at),
                    'isCurrent' => $experience->is_current,
                    'companyUrl' => $experience->company_url,
                ])
                ->values()
                ->all(),
            'education' => Education::query()
                ->select([
                    'institution',
                    'qualification',
                    'field_of_study',
                    'started_at',
                    'ended_at',
                    'description',
                ])
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (Education $education): array => [
                    'institution' => $education->institution,
                    'qualification' => $education->qualification,
                    'fieldOfStudy' => $education->field_of_study,
                    'startedAt' => $this->dateString($education->started_at),
                    'endedAt' => $this->dateString($education->ended_at),
                    'description' => $education->description,
                ])
                ->values()
                ->all(),
            'portfolios' => Portfolio::query()
                ->select([
                    'id',
                    'experience_id',
                    'title',
                    'slug',
                    'short_description',
                    'project_url',
                    'repository_url',
                    'started_at',
                    'completed_at',
                    'is_ongoing',
                    'technologies',
                    'role',
                    'outcome',
                    'published_at',
                ])
                ->with([
                    'experience:id,company_name,is_visible',
                    'media' => fn ($query) => $query
                        ->select(['id', 'portfolio_id', 'path', 'media_type', 'fit', 'alt_text', 'is_primary', 'sort_order'])
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                ])
                ->published()
                ->ordered()
                ->get()
                ->map(function (Portfolio $portfolio): array {
                    $experience = $portfolio->experience;

                    return [
                        'title' => $portfolio->title,
                        'slug' => $portfolio->slug,
                        'shortDescription' => $portfolio->short_description,
                        'projectUrl' => $portfolio->project_url,
                        'repositoryUrl' => $portfolio->repository_url,
                        'startedAt' => $this->dateString($portfolio->started_at),
                        'completedAt' => $this->dateString($portfolio->completed_at),
                        'isOngoing' => $portfolio->is_ongoing,
                        'technologies' => $portfolio->technologies ?? [],
                        'role' => $portfolio->role,
                        'outcome' => $portfolio->outcome,
                        'publishedAt' => $this->dateString($portfolio->published_at),
                        'experience' => $experience instanceof Experience && $experience->is_visible ? [
                            'companyName' => $experience->company_name,
                        ] : null,
                        'media' => $portfolio->media
                            ->map(fn (PortfolioMedia $media): array => [
                                'url' => $this->publicUrl($media->path),
                                'type' => $this->enumValue($media->media_type),
                                'fit' => $this->enumValue($media->fit),
                                'altText' => $media->alt_text,
                                'isPrimary' => $media->is_primary,
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
            'morePortfolios' => MorePortfolio::query()
                ->select(['title', 'project_url', 'technologies'])
                ->visible()
                ->ordered()
                ->get()
                ->map(fn (MorePortfolio $portfolio): array => [
                    'title' => $portfolio->title,
                    'projectUrl' => $portfolio->project_url,
                    'technologies' => $portfolio->technologies ?? [],
                ])
                ->values()
                ->all(),
        ]);
    }

    private function publicUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }

    private function dateString(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return filled($value) ? (string) $value : null;
    }

    private function absolutePublicUrl(?string $path): ?string
    {
        $publicUrl = $this->publicUrl($path);

        return filled($publicUrl) ? url($publicUrl) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function structuredData(
        ?SiteSetting $settings,
        ?SiteProfile $profile,
        string $canonicalUrl,
        ?string $ogImageUrl,
    ): array {
        $graph = [];

        if ($profile?->full_name) {
            $person = [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => $profile->full_name,
                'url' => $canonicalUrl,
            ];

            if ($profile->professional_title) {
                $person['jobTitle'] = $profile->professional_title;
            }

            if ($profile->public_email) {
                $person['email'] = $profile->public_email;
            }

            if ($profile->public_phone) {
                $person['telephone'] = $profile->public_phone;
            }

            if ($profile->location) {
                $person['address'] = [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $profile->location,
                ];
            }

            $profileImageUrl = $this->absolutePublicUrl($profile->profile_image_path);

            if ($profileImageUrl) {
                $person['image'] = $profileImageUrl;
            }

            $graph[] = $person;
        }

        if ($settings?->site_name) {
            $website = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $settings->site_name,
                'url' => $canonicalUrl,
            ];

            if ($settings->meta_description) {
                $website['description'] = $settings->meta_description;
            }

            if ($ogImageUrl) {
                $website['image'] = $ogImageUrl;
            }

            $graph[] = $website;
        }

        return $graph;
    }
}
