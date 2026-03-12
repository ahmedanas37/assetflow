<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\PortalSettingsPage;
use App\Http\Middleware\EnsureApplicationInstalled;
use App\Services\PortalSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $portalSettings = app(PortalSettings::class);
        $logoUrl = $portalSettings->logoUrl();

        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName($portalSettings->productName());

        if ($logoUrl) {
            $panel
                ->brandLogo($logoUrl)
                ->brandLogoHeight('2.25rem')
                ->favicon($logoUrl);
        }

        return $panel
            ->colors([
                'primary' => Color::hex($portalSettings->brandColor()),
                'info' => Color::hex('#175DFD'),
            ])
            ->navigationGroups([
                'Dashboard',
                'Assets',
                'Assignments',
                'People',
                'Inventory',
                'Locations',
                'Vendors',
                'Maintenance',
                'Reports',
                'Audit',
                'Administration',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                PortalSettingsPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\DashboardIntroWidget::class,
                \App\Filament\Widgets\OperationalInsightsWidget::class,
                \App\Filament\Widgets\AssetStatsOverviewWidget::class,
                \App\Filament\Widgets\AccessoryStatsOverviewWidget::class,
                \App\Filament\Widgets\WarrantyExpiringWidget::class,
                \App\Filament\Widgets\OverdueCheckoutsWidget::class,
                \App\Filament\Widgets\AssetsByStatusChartWidget::class,
                \App\Filament\Widgets\AssetsByCategoryChartWidget::class,
                \App\Filament\Widgets\LowStockAccessoriesWidget::class,
                \App\Filament\Widgets\AssignmentsDueSoonWidget::class,
                \App\Filament\Widgets\RecentActivityWidget::class,
                \App\Filament\Widgets\RecentlyUpdatedAssetsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                EnsureApplicationInstalled::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
