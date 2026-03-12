<x-filament-widgets::widget>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-white via-white to-slate-50 p-6">
            <div class="absolute -right-20 -top-20 h-48 w-48 rounded-full bg-[rgba(31,9,97,0.08)] blur-3xl"></div>
            <div class="absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-[rgba(23,93,253,0.08)] blur-3xl"></div>

            <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="space-y-2">
                    @if (! empty($logoUrl))
                        <img src="{{ $logoUrl }}" alt="{{ $product }} logo" class="h-10 w-auto object-contain">
                    @endif
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">
                        {{ $company }}
                    </p>
                    <h1 class="text-2xl font-semibold text-gray-900 md:text-3xl">
                        {{ $product }}
                    </h1>
                    <p class="text-sm text-gray-600">
                        Track assets, accessories, assignments, and maintenance in one place.
                    </p>
                    <p class="text-xs text-gray-500">
                        Metrics updated {{ $metricsUpdated }}.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-filament::button tag="a" href="{{ \App\Filament\Resources\AssetResource::getUrl() }}" icon="heroicon-o-cube">
                        Assets
                    </x-filament::button>
                    <x-filament::button tag="a" href="{{ \App\Filament\Resources\AccessoryResource::getUrl() }}" color="gray" icon="heroicon-o-puzzle-piece">
                        Accessories
                    </x-filament::button>
                    <x-filament::button tag="a" href="{{ \App\Filament\Resources\AssetAssignmentResource::getUrl() }}" color="gray" icon="heroicon-o-arrow-path">
                        Assignments
                    </x-filament::button>
                    <x-filament::button tag="a" href="{{ \App\Filament\Pages\Reports\WarrantyExpiringReport::getUrl() }}" color="gray" icon="heroicon-o-chart-bar">
                        Reports
                    </x-filament::button>
                    @can('import assets')
                        <x-filament::button tag="a" href="{{ \App\Filament\Resources\AssetResource::getUrl('import') }}" color="gray" icon="heroicon-o-arrow-up-tray">
                            Import Assets
                        </x-filament::button>
                    @endcan
                    @can('import users')
                        <x-filament::button tag="a" href="{{ \App\Filament\Resources\UserResource::getUrl('import') }}" color="gray" icon="heroicon-o-user-plus">
                            Import Users
                        </x-filament::button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
