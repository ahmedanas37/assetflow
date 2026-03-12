<x-filament-widgets::widget>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Operational Insights</h2>
                <p class="text-sm text-gray-600">
                    Focus areas across assignments, maintenance, and data quality.
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <a
                    href="{{ $card['url'] }}"
                    class="group rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-200 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">
                                {{ $card['eyebrow'] }}
                            </p>
                            <p class="text-2xl font-semibold text-gray-900">
                                {{ number_format($card['value']) }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ $card['label'] }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-2 text-gray-500 transition group-hover:text-gray-700">
                            <x-filament::icon name="{{ $card['icon'] }}" class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <x-filament::badge color="{{ $card['badge_color'] }}" size="sm">
                            {{ $card['badge'] }}
                        </x-filament::badge>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
