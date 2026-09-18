<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ContactInbox;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SiteProfile;
use App\Filament\Pages\SiteSettings;
use App\Filament\Widgets\ContentOverview;
use App\Models\SiteProfile as SiteProfileModel;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(fn (): string => $this->adminFaviconUrl())
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->navigationGroups([
                NavigationGroup::make('Website')->collapsible(false),
                NavigationGroup::make('Content')->collapsible(false),
                NavigationGroup::make('Messages')->collapsible(false),
            ])
            ->pages([
                Dashboard::class,
                SiteProfile::class,
                SiteSettings::class,
                ContactInbox::class,
            ])
            ->widgets([
                ContentOverview::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function adminFaviconUrl(): string
    {
        $profileImagePath = SiteProfileModel::query()->value('profile_image_path');

        if (filled($profileImagePath)) {
            return Storage::disk('public')->url($profileImagePath);
        }

        return asset('favicon.ico');
    }
}
