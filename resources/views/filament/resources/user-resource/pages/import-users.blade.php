<x-filament::page>
    <div class="space-y-6">
        <x-filament::section heading="Upload CSV">
            <div class="mb-4">
                <x-filament::button tag="a" href="{{ route('assetflow.users.template') }}" color="gray">
                    Download Template
                </x-filament::button>
            </div>
            {{ $this->form }}

            <div class="mt-4 flex flex-wrap gap-3">
                <x-filament::button wire:click="preview" color="gray">
                    Preview
                </x-filament::button>
                <x-filament::button wire:click="validateImport" color="warning">
                    Validate
                </x-filament::button>
                <x-filament::button wire:click="import" color="success">
                    Import
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section heading="Preview">
            @if (empty($previewRows))
                <p class="text-sm text-gray-500">Upload a CSV file and click Preview.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr>
                                @foreach (array_keys($previewRows[0]) as $header)
                                    <th class="border-b px-2 py-1 text-left">{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewRows as $row)
                                <tr>
                                    @foreach ($row as $value)
                                        <td class="border-b px-2 py-1">{{ $value }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section heading="Validation Results">
            @if (empty($validationErrors))
                <p class="text-sm text-gray-500">No validation errors.</p>
            @else
                <div class="space-y-2">
                    @foreach ($validationErrors as $error)
                        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700">
                            <strong>Row {{ $error['row'] }}:</strong>
                            {{ implode('; ', $error['errors']) }}
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
