<?php

namespace App\Filament\Pages;

use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Accessories\Services\AccessoryAssignmentService;
use App\Domain\Assets\Enums\AssetCondition;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Assets\Services\AssignmentService;
use App\Domain\People\Enums\UserStatus;
use App\Domain\People\Models\Employee;
use App\Filament\Resources\AssetResource;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class OffboardingPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'People';

    protected static ?string $navigationLabel = 'Offboarding';

    protected static ?string $title = 'Offboarding';

    protected static string $view = 'filament.pages.offboarding';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'target' => null,
        ]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('view users') || $user?->can('view employees'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('target')
                    ->label('Person')
                    ->placeholder('Select a user or employee')
                    ->options(fn (): array => $this->targetOptions())
                    ->searchable()
                    ->preload()
                    ->live(),
            ])
            ->statePath('data');
    }

    /**
     * @return array<string, string>
     */
    public function targetOptions(): array
    {
        $users = User::query()
            ->with('department')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                'user:'.$user->id => sprintf(
                    'User: %s%s',
                    $user->name,
                    $user->department?->name ? ' - '.$user->department->name : '',
                ),
            ]);

        $employees = Employee::query()
            ->with('department')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Employee $employee): array => [
                'employee:'.$employee->id => sprintf(
                    'Employee: %s%s%s',
                    $employee->name,
                    $employee->employee_id ? ' ('.$employee->employee_id.')' : '',
                    $employee->department?->name ? ' - '.$employee->department->name : '',
                ),
            ]);

        return $users->merge($employees)->all();
    }

    public function selectedPerson(): User|Employee|null
    {
        [$type, $id] = $this->selectedTargetParts();

        return match ($type) {
            'user' => User::query()->with('department')->find($id),
            'employee' => Employee::query()->with('department')->find($id),
            default => null,
        };
    }

    public function selectedType(): ?AssignmentType
    {
        [$type] = $this->selectedTargetParts();

        return match ($type) {
            'user' => AssignmentType::User,
            'employee' => AssignmentType::Employee,
            default => null,
        };
    }

    /**
     * @return EloquentCollection<int, AssetAssignment>
     */
    public function assetAssignments(): EloquentCollection
    {
        $type = $this->selectedType();
        $person = $this->selectedPerson();

        if (! $type || ! $person) {
            return new EloquentCollection;
        }

        return AssetAssignment::query()
            ->with(['asset.assetModel', 'asset.statusLabel', 'assignedBy'])
            ->where('assigned_to_type', $type->value)
            ->where('assigned_to_id', $person->id)
            ->where('is_active', true)
            ->latest('assigned_at')
            ->get();
    }

    /**
     * @return EloquentCollection<int, AccessoryAssignment>
     */
    public function accessoryAssignments(): EloquentCollection
    {
        $type = $this->selectedType();
        $person = $this->selectedPerson();

        if (! $type || ! $person) {
            return new EloquentCollection;
        }

        return AccessoryAssignment::query()
            ->with(['accessory', 'assignedBy'])
            ->where('assigned_to_type', $type->value)
            ->where('assigned_to_id', $person->id)
            ->where('is_active', true)
            ->latest('assigned_at')
            ->get();
    }

    /**
     * @return array{assets: int, accessories: int, accessory_units: int, total_items: int, is_clear: bool}
     */
    public function clearanceSummary(): array
    {
        $assets = $this->assetAssignments();
        $accessories = $this->accessoryAssignments();
        $accessoryUnits = $accessories->sum(fn (AccessoryAssignment $assignment): int => $assignment->remaining_quantity);

        return [
            'assets' => $assets->count(),
            'accessories' => $accessories->count(),
            'accessory_units' => $accessoryUnits,
            'total_items' => $assets->count() + $accessoryUnits,
            'is_clear' => $assets->isEmpty() && $accessoryUnits === 0,
        ];
    }

    public function checkinAsset(int $assignmentId): void
    {
        if (! $this->canCheckinAssets()) {
            abort(403);
        }

        $assignment = AssetAssignment::query()
            ->with('asset')
            ->where('is_active', true)
            ->findOrFail($assignmentId);

        if (! $this->ownsAssignment($assignment->assigned_to_type, $assignment->assigned_to_id)) {
            abort(403);
        }

        app(AssignmentService::class)->checkin(
            asset: $assignment->asset,
            actor: $this->actor(),
            condition: AssetCondition::Good,
            notes: trim(($assignment->notes ? $assignment->notes.PHP_EOL : '').'Offboarding return.'),
        );

        Notification::make()
            ->title('Asset checked in')
            ->success()
            ->send();
    }

    public function checkinAccessory(int $assignmentId): void
    {
        if (! $this->canCheckinAccessories()) {
            abort(403);
        }

        $assignment = AccessoryAssignment::query()
            ->where('is_active', true)
            ->findOrFail($assignmentId);

        if (! $this->ownsAssignment($assignment->assigned_to_type, $assignment->assigned_to_id)) {
            abort(403);
        }

        app(AccessoryAssignmentService::class)->checkin(
            assignment: $assignment,
            actor: $this->actor(),
            quantity: $assignment->remaining_quantity,
            notes: trim(($assignment->notes ? $assignment->notes.PHP_EOL : '').'Offboarding return.'),
        );

        Notification::make()
            ->title('Accessory checked in')
            ->success()
            ->send();
    }

    public function checkinAll(): void
    {
        if (! $this->canCheckinAssets() && ! $this->canCheckinAccessories()) {
            abort(403);
        }

        if ($this->canCheckinAssets()) {
            foreach ($this->assetAssignments() as $assignment) {
                $this->checkinAsset($assignment->id);
            }
        }

        if ($this->canCheckinAccessories()) {
            foreach ($this->accessoryAssignments() as $assignment) {
                $this->checkinAccessory($assignment->id);
            }
        }

        Notification::make()
            ->title('All assigned items were checked in')
            ->success()
            ->send();
    }

    public function markInactive(): void
    {
        $person = $this->selectedPerson();

        if (! $person) {
            return;
        }

        if ($person instanceof User && $person->id === auth()->id()) {
            Notification::make()
                ->title('You cannot deactivate your own account from offboarding')
                ->danger()
                ->send();

            return;
        }

        $permission = $person instanceof User ? 'update users' : 'update employees';
        if (! $this->actor()->can($permission)) {
            abort(403);
        }

        $person->status = UserStatus::Inactive;
        $person->save();

        Notification::make()
            ->title('Person marked inactive')
            ->success()
            ->send();
    }

    public function assetUrl(int $assetId): string
    {
        return AssetResource::getUrl('view', ['record' => $assetId]);
    }

    public function canCheckinAssets(): bool
    {
        return $this->actor()->can('checkin assets');
    }

    public function canCheckinAccessories(): bool
    {
        return $this->actor()->can('checkin accessories');
    }

    public function canCheckinAny(): bool
    {
        return $this->canCheckinAssets() || $this->canCheckinAccessories();
    }

    public function canMarkSelectedInactive(): bool
    {
        $person = $this->selectedPerson();

        return match (true) {
            $person instanceof User => $this->actor()->can('update users'),
            $person instanceof Employee => $this->actor()->can('update employees'),
            default => false,
        };
    }

    /**
     * @return Collection<int, string>
     */
    public function clearanceWarnings(): Collection
    {
        $summary = $this->clearanceSummary();
        $person = $this->selectedPerson();

        return collect([
            $summary['assets'] > 0 ? $summary['assets'].' active asset assignment(s) still need return.' : null,
            $summary['accessory_units'] > 0 ? $summary['accessory_units'].' accessory unit(s) still need return.' : null,
            $person && $person->status === UserStatus::Active ? 'Person is still marked active.' : null,
        ])->filter()->values();
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function selectedTargetParts(): array
    {
        $target = (string) ($this->data['target'] ?? '');

        if (! str_contains($target, ':')) {
            return [null, null];
        }

        [$type, $id] = explode(':', $target, 2);

        return [$type, (int) $id];
    }

    private function ownsAssignment(AssignmentType|string $type, int $assignedToId): bool
    {
        $selectedType = $this->selectedType();
        $person = $this->selectedPerson();
        $assignmentType = $type instanceof AssignmentType ? $type : AssignmentType::tryFrom($type);

        return $selectedType
            && $person
            && $assignmentType === $selectedType
            && $assignedToId === $person->id;
    }

    private function actor(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
