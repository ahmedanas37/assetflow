<x-filament::page>
    <div class="space-y-6">
        <x-filament::section heading="Upload CSV">
            <div class="space-y-6">
                <div>
                    <a
                        href="{{ route('assetflow.employees.template') }}"
                        class="fi-btn fi-btn-color-gray fi-color-gray fi-size-md relative inline-grid grid-flow-col items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-gray-950/10 transition duration-75 hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-gray-400/40 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:ring-white/20"
                        download="employee-import-template.csv"
                    >
                        Download Template
                    </a>
                </div>

                <div class="space-y-2" x-data="{ fileName: 'No file selected' }">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">CSV File</label>
                    <div class="flex items-center gap-3">
                        <label class="fi-btn fi-btn-color-gray fi-color-gray fi-size-md relative inline-grid cursor-pointer grid-flow-col items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-950/10 transition duration-75 hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-gray-400/40 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:ring-white/20">
                            Choose CSV
                            <input
                                type="file"
                                class="sr-only"
                                wire:model.live="data.file"
                                accept=".csv,text/csv,text/plain,application/vnd.ms-excel"
                                x-on:change="fileName = $event.target.files?.[0]?.name ?? 'No file selected'"
                            />
                        </label>
                        <div class="text-sm text-gray-600 dark:text-gray-300" x-text="fileName"></div>
                    </div>
                    <p class="text-xs text-gray-500">Accepted: .csv</p>
                    <div wire:loading wire:target="data.file" class="text-xs text-gray-500">Uploading…</div>
                    @error('data.file')
                        <p class="text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{ $this->form }}

                <div class="border-t border-gray-200/70 pt-4 flex flex-wrap gap-3">
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
