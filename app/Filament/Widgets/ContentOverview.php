<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ContactInbox;
use App\Filament\Pages\SiteProfile as SiteProfilePage;
use App\Filament\Resources\EducationResource;
use App\Filament\Resources\ExperienceResource;
use App\Filament\Resources\MorePortfolioResource;
use App\Filament\Resources\PortfolioResource;
use App\Filament\Resources\SkillResource;
use App\Filament\Resources\SocialLinkResource;
use App\Models\ContactMessage;
use App\Models\Education as EducationModel;
use App\Models\Experience as ExperienceModel;
use App\Models\MorePortfolio;
use App\Models\Portfolio;
use App\Models\SiteProfile;
use App\Models\Skill;
use App\Models\SocialLink;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Content overview';

    protected ?string $description = 'Counts link to the admin areas for later content management.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make('Site profiles', SiteProfile::query()->count())
                ->icon(Heroicon::UserCircle)
                ->url(SiteProfilePage::getUrl()),
            Stat::make('Social links', SocialLink::query()->count())
                ->icon(Heroicon::Link)
                ->url(SocialLinkResource::getUrl()),
            Stat::make('Skills', Skill::query()->count())
                ->icon(Heroicon::Sparkles)
                ->url(SkillResource::getUrl()),
            Stat::make('Experience', ExperienceModel::query()->count())
                ->icon(Heroicon::Briefcase)
                ->url(ExperienceResource::getUrl()),
            Stat::make('Education', EducationModel::query()->count())
                ->icon(Heroicon::AcademicCap)
                ->url(EducationResource::getUrl()),
            Stat::make('Portfolio items', Portfolio::query()->count())
                ->icon(Heroicon::RectangleGroup)
                ->url(PortfolioResource::getUrl()),
            Stat::make('More portfolio items', MorePortfolio::query()->count())
                ->icon(Heroicon::RectangleGroup)
                ->url(MorePortfolioResource::getUrl()),
            Stat::make('Contact messages', ContactMessage::query()->count())
                ->icon(Heroicon::Inbox)
                ->url(ContactInbox::getUrl()),
        ];
    }
}
