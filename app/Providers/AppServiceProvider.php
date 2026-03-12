<?php

namespace App\Providers;

use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Accessories\Observers\AccessoryAssignmentObserver;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Assets\Observers\AssetAssignmentObserver;
use App\Domain\Assets\Observers\AssetObserver;
use App\Domain\Assets\Observers\StatusLabelObserver;
use App\Services\PortalSettings;
use Database\Seeders\StatusLabelsSeeder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Infolist;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PortalSettings::class, fn () => new PortalSettings);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Table::$defaultDateTimeDisplayFormat = 'M j, Y h:i A';
        Table::$defaultTimeDisplayFormat = 'h:i A';
        Infolist::$defaultDateTimeDisplayFormat = 'M j, Y h:i A';
        Infolist::$defaultTimeDisplayFormat = 'h:i A';
        DateTimePicker::$defaultDateTimeDisplayFormat = 'M j, Y h:i A';

        if (! $this->app->runningInConsole() && $this->app->environment('local')) {
            $configuredUrl = config('app.url') ?? '';
            $configuredHost = parse_url($configuredUrl, PHP_URL_HOST);
            $configuredPort = parse_url($configuredUrl, PHP_URL_PORT);
            $configuredScheme = parse_url($configuredUrl, PHP_URL_SCHEME) ?: 'http';

            $configuredAuthority = $configuredHost
                ? $configuredHost.($configuredPort ? ':'.$configuredPort : '')
                : null;

            $currentAuthority = request()->getHttpHost();
            $currentScheme = request()->getScheme();

            if (
                $configuredAuthority &&
                ($configuredAuthority !== $currentAuthority || $configuredScheme !== $currentScheme)
            ) {
                URL::forceRootUrl(request()->getSchemeAndHttpHost());
            }
        }

        if (! Storage::disk('local')->exists('livewire-tmp')) {
            Storage::disk('local')->makeDirectory('livewire-tmp');
        }

        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            if ($modelName === \App\Models\User::class) {
                return 'Database\\Factories\\UserFactory';
            }

            return 'Database\\Factories\\'.str_replace('App\\', '', $modelName).'Factory';
        });

        Asset::observe(AssetObserver::class);
        AssetAssignment::observe(AssetAssignmentObserver::class);
        AccessoryAssignment::observe(AccessoryAssignmentObserver::class);
        StatusLabel::observe(StatusLabelObserver::class);

        if (! $this->app->runningInConsole()) {
            StatusLabelsSeeder::ensureDefaults();
        }
    }
}
