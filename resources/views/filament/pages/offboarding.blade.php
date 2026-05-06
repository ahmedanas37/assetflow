<x-filament::page>
    <div class="space-y-6">
        <form>
            {{ $this->form }}
        </form>

        @php
            $person = $this->selectedPerson();
            $summary = $this->clearanceSummary();
            $warnings = $this->clearanceWarnings();
            $assetAssignments = $this->assetAssignments();
            $accessoryAssignments = $this->accessoryAssignments();
        @endphp

        @if ($person)
            <x-filament::section>
                <x-slot name="heading">Clearance Summary</x-slot>

                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Person</div>
                        <div class="font-medium text-gray-950 dark:text-white">{{ $person->name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $person->department?->name ?? 'No department' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                        <x-filament::badge :color="$person->status === \App\Domain\People\Enums\UserStatus::Active ? 'success' : 'gray'">
                            {{ ucfirst($person->status?->value ?? 'unknown') }}
                        </x-filament::badge>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Open Assignments</div>
                        <div class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $summary['assets'] + $summary['accessories'] }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Outstanding Items</div>
                        <div class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $summary['total_items'] }}</div>
                    </div>
                </div>

                @if ($warnings->isNotEmpty())
                    <div class="mt-4 rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                        @foreach ($warnings as $warning)
                            <div>{{ $warning }}</div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 rounded-lg border border-success-200 bg-success-50 p-4 text-sm text-success-800 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-200">
                        This person has no active assignments and is ready for final clearance.
                    </div>
                @endif

                <div class="mt-5 flex flex-wrap gap-3">
                    @if (($summary['assets'] + $summary['accessories']) > 0 && $this->canCheckinAny())
                        <x-filament::button
                            color="warning"
                            wire:click="checkinAll"
                            wire:confirm="Check in all active assignments for this person?"
                            type="button"
                        >
                            Check In All Items
                        </x-filament::button>
                    @endif

                    @if ($person->status === \App\Domain\People\Enums\UserStatus::Active && $this->canMarkSelectedInactive())
                        <x-filament::button
                            color="gray"
                            wire:click="markInactive"
                            wire:confirm="Mark this person inactive?"
                            type="button"
                        >
                            Mark Inactive
                        </x-filament::button>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Assigned Assets</x-slot>

                @if ($assetAssignments->isEmpty())
                    <div class="text-sm text-gray-500 dark:text-gray-400">No active asset assignments.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <th class="py-2 pr-4">Asset</th>
                                    <th class="py-2 pr-4">Model</th>
                                    <th class="py-2 pr-4">Assigned</th>
                                    <th class="py-2 pr-4">Due</th>
                                    <th class="py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach ($assetAssignments as $assignment)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-gray-950 dark:text-white">
                                            <a class="text-primary-600 hover:underline dark:text-primary-400" href="{{ $this->assetUrl($assignment->asset_id) }}">
                                                {{ $assignment->asset?->asset_tag ?? '-' }}
                                            </a>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $assignment->asset?->assetModel?->name ?? '-' }}</td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $assignment->assigned_at?->format('d M Y') ?? '-' }}</td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $assignment->due_at?->format('d M Y') ?? '-' }}</td>
                                        <td class="py-3 text-right">
                                            @if ($this->canCheckinAssets())
                                                <x-filament::button
                                                    color="warning"
                                                    size="sm"
                                                    wire:click="checkinAsset({{ $assignment->id }})"
                                                    wire:confirm="Check in this asset as part of offboarding?"
                                                    type="button"
                                                >
                                                    Check In
                                                </x-filament::button>
                                            @else
                                                <span class="text-xs text-gray-500 dark:text-gray-400">No permission</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Assigned Accessories</x-slot>

                @if ($accessoryAssignments->isEmpty())
                    <div class="text-sm text-gray-500 dark:text-gray-400">No active accessory assignments.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <th class="py-2 pr-4">Accessory</th>
                                    <th class="py-2 pr-4">Quantity</th>
                                    <th class="py-2 pr-4">Remaining</th>
                                    <th class="py-2 pr-4">Assigned</th>
                                    <th class="py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach ($accessoryAssignments as $assignment)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-gray-950 dark:text-white">{{ $assignment->accessory?->name ?? '-' }}</td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $assignment->quantity }}</td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $assignment->remaining_quantity }}</td>
                                        <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $assignment->assigned_at?->format('d M Y') ?? '-' }}</td>
                                        <td class="py-3 text-right">
                                            @if ($this->canCheckinAccessories())
                                                <x-filament::button
                                                    color="warning"
                                                    size="sm"
                                                    wire:click="checkinAccessory({{ $assignment->id }})"
                                                    wire:confirm="Check in all remaining units for this accessory assignment?"
                                                    type="button"
                                                >
                                                    Check In
                                                </x-filament::button>
                                            @else
                                                <span class="text-xs text-gray-500 dark:text-gray-400">No permission</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Select a system user or employee to start an offboarding clearance review.
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament::page>
