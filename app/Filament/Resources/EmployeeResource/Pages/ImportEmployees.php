<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Domain\People\Enums\UserStatus;
use App\Domain\People\Models\Department;
use App\Domain\People\Models\Employee;
use App\Filament\Resources\EmployeeResource;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use League\Csv\Reader;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportEmployees extends Page
{
    protected static string $resource = EmployeeResource::class;

    protected static string $view = 'filament.resources.employee-resource.pages.import-employees';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    /** @var array<int, array<string, string>> */
    public array $previewRows = [];

    /** @var array<int, array{row: int, errors: array<int, string>}> */
    public array $validationErrors = [];

    public int $totalRows = 0;

    public function mount(): void
    {
        $this->form->fill([
            'create_missing_departments' => true,
            'update_existing' => false,
            'default_status' => UserStatus::Active->value,
        ]);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('import employees') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Column Mapping')
                    ->columns(2)
                    ->schema($this->getMappingSchema())
                    ->statePath('column_map'),
                Toggle::make('create_missing_departments')
                    ->label('Create missing departments')
                    ->default(true),
                Toggle::make('update_existing')
                    ->label('Update existing employees by ID (otherwise skip)')
                    ->default(false),
                Select::make('default_status')
                    ->label('Default Status')
                    ->options([
                        UserStatus::Active->value => 'Active',
                        UserStatus::Inactive->value => 'Inactive',
                    ])
                    ->default(UserStatus::Active->value),
            ])
            ->statePath('data');
    }

    public function updatedDataFile(mixed $state): void
    {
        $file = $this->resolveUploadedFile($state);

        if (! $file) {
            return;
        }

        $headers = $this->getCsvHeaders($file);
        $this->data['column_map'] = $this->guessColumnMap($headers);
    }

    public function preview(): void
    {
        $this->validationErrors = [];
        $this->previewRows = $this->readPreviewRows(limit: 10);
    }

    public function validateImport(): void
    {
        $this->validationErrors = $this->validateRows();

        if (empty($this->validationErrors)) {
            Notification::make()
                ->title('Validation passed')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Validation failed')
                ->body('Please review the errors below.')
                ->danger()
                ->send();
        }
    }

    public function import(): void
    {
        $this->validationErrors = $this->validateRows();

        if (! empty($this->validationErrors)) {
            Notification::make()
                ->title('Import blocked')
                ->body('Fix validation errors before importing.')
                ->danger()
                ->send();

            return;
        }

        $rows = $this->readRows(createMissing: $this->shouldCreateMissingDepartments());
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $ignoredEmails = 0;
        $skippedConflicts = 0;

        foreach ($rows as $row) {
            $employeeId = $this->normalizeEmployeeId((string) ($row['employee_id'] ?? ''));
            $existing = $this->findExistingEmployee($employeeId);

            if ($existing && $this->shouldUpdateExisting()) {
                $existing->name = $row['name'];
                if ($employeeId !== '') {
                    $existing->employee_id = $employeeId;
                }
                if ($row['email'] !== null && $row['email'] !== '') {
                    $safeEmail = $this->sanitizeEmailForSave($row['email'], $existing);
                    if ($safeEmail !== null) {
                        $existing->email = $safeEmail;
                    } else {
                        $ignoredEmails++;
                    }
                }
                $existing->department_id = $row['department_id'] ?: $existing->department_id;
                $existing->status = $row['status'] ?: $existing->status;
                $existing->title = $row['title'] ?: $existing->title;
                $existing->phone = $row['phone'] ?: $existing->phone;
                $existing->notes = $row['notes'] ?: $existing->notes;
                $existing->save();

                $updated++;

                continue;
            }

            if ($existing) {
                $skipped++;

                continue;
            }

            try {
                $emailToStore = $this->sanitizeEmailForSave($row['email'] ?? '', null);
                if (($row['email'] ?? '') !== '' && $emailToStore === null) {
                    $ignoredEmails++;
                }

                Employee::create([
                    'employee_id' => $employeeId !== '' ? $employeeId : null,
                    'name' => $row['name'],
                    'email' => $emailToStore,
                    'department_id' => $row['department_id'],
                    'status' => $row['status'] ?: $this->getDefaultStatus(),
                    'title' => $row['title'] ?: null,
                    'phone' => $row['phone'] ?: null,
                    'notes' => $row['notes'] ?: null,
                ]);
            } catch (UniqueConstraintViolationException) {
                $skipped++;
                $skippedConflicts++;

                continue;
            }

            $created++;
        }

        Notification::make()
            ->title('Import complete')
            ->body("Created {$created}, updated {$updated}, skipped {$skipped}. Duplicate emails ignored: {$ignoredEmails}.")
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getMappingFields(): array
    {
        return [
            'employee_id' => 'Employee ID',
            'name' => 'Name',
            'email' => 'Email',
            'department' => 'Department',
            'status' => 'Status',
            'title' => 'Title',
            'phone' => 'Phone',
            'notes' => 'Notes',
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected function getMappingSchema(): array
    {
        $options = $this->getHeaderOptions();

        return collect($this->getMappingFields())
            ->map(function (string $label, string $field) use ($options): Select {
                return Select::make($field)
                    ->label($label)
                    ->options($options)
                    ->searchable()
                    ->placeholder('Not mapped');
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function readRows(?int $limit = null, bool $createMissing = false): array
    {
        $file = $this->getUploadedFile();

        if (! $file) {
            return [];
        }

        $stream = $this->openStream($file);

        if (! $stream) {
            return [];
        }

        $reader = Reader::createFromStream($stream);
        $reader->setHeaderOffset(0);

        $records = $reader->getRecords();
        $rows = [];
        $count = 0;

        foreach ($records as $record) {
            $rows[] = $this->mapRow($record, $createMissing);
            $count++;

            if ($limit && $count >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function readPreviewRows(?int $limit = null): array
    {
        $file = $this->getUploadedFile();

        if (! $file) {
            return [];
        }

        $stream = $this->openStream($file);

        if (! $stream) {
            return [];
        }

        $reader = Reader::createFromStream($stream);
        $reader->setHeaderOffset(0);

        $records = $reader->getRecords();
        $rows = [];
        $count = 0;

        foreach ($records as $record) {
            $rows[] = $this->parseRecord($record);
            $count++;

            if ($limit && $count >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{row: int, errors: array<int, string>}>
     */
    protected function validateRows(): array
    {
        $errors = [];
        $file = $this->getUploadedFile();

        if (! $file) {
            return [];
        }

        $stream = $this->openStream($file);

        if (! $stream) {
            return [];
        }

        $reader = Reader::createFromStream($stream);
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        $createMissing = $this->shouldCreateMissingDepartments();

        $seenEmployeeIds = [];

        $index = 0;

        foreach ($records as $record) {
            $mapped = $this->parseRecord($record);
            $rowErrors = [];

            $employeeId = $this->normalizeEmployeeId($mapped['employee_id']);

            if ($mapped['name'] === '') {
                $rowErrors[] = 'Name is required.';
            }

            if ($employeeId !== '') {
                if (in_array($employeeId, $seenEmployeeIds, true)) {
                    $rowErrors[] = 'Duplicate employee ID in CSV.';
                } else {
                    $seenEmployeeIds[] = $employeeId;
                }
            }

            $status = strtolower(trim($mapped['status']));
            if ($status !== '' && ! in_array($status, [UserStatus::Active->value, UserStatus::Inactive->value], true)) {
                $rowErrors[] = 'Status must be active or inactive.';
            }

            if ($mapped['department'] !== '' && ! $createMissing) {
                if (! Department::query()->where('name', $mapped['department'])->exists()) {
                    $rowErrors[] = 'Department not found.';
                }
            }

            if (! empty($rowErrors)) {
                $errors[] = [
                    'row' => $index + 2,
                    'errors' => $rowErrors,
                ];
            }

            $index++;
        }

        $this->totalRows = $index;

        return $errors;
    }

    protected function mapRow(array $record, bool $createMissing): array
    {
        return $this->resolveRow($this->parseRecord($record), $createMissing);
    }

    /**
     * @return array<string, string>
     */
    protected function parseRecord(array $record): array
    {
        $columnMap = $this->data['column_map'] ?? [];
        $mapped = [];

        foreach ($this->getMappingFields() as $field => $label) {
            $header = $columnMap[$field] ?? null;

            if ($header && array_key_exists($header, $record)) {
                $mapped[$field] = trim((string) $record[$header]);
            } else {
                $mapped[$field] = '';
            }
        }

        return $mapped;
    }

    protected function resolveRow(array $mapped, bool $createMissing): array
    {
        $department = $this->resolveDepartment($mapped['department'], $createMissing);

        return [
            'employee_id' => $this->normalizeEmployeeId($mapped['employee_id']),
            'name' => $mapped['name'],
            'email' => $this->normalizeEmailForStorage($mapped['email']),
            'department_id' => $department?->id,
            'status' => $this->normalizeStatus($mapped['status']),
            'title' => $mapped['title'],
            'phone' => $mapped['phone'],
            'notes' => $mapped['notes'],
        ];
    }

    protected function normalizeEmailForStorage(string $email): ?string
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        return $email;
    }

    protected function normalizeEmployeeId(string $employeeId): string
    {
        $employeeId = trim($employeeId);

        if ($employeeId === '') {
            return '';
        }

        $employeeId = preg_replace('/[\s\x{00A0}]+/u', '', $employeeId) ?? $employeeId;

        return trim($employeeId);
    }

    protected function sanitizeEmailForSave(string $email, ?Employee $existing): ?string
    {
        $email = $this->normalizeEmailForStorage($email);

        if (! $email) {
            return null;
        }

        $query = Employee::query()->where('email', $email);

        if ($existing) {
            $query->where('id', '!=', $existing->id);
        }

        if ($query->exists()) {
            return null;
        }

        return $email;
    }

    protected function resolveDepartment(string $name, bool $createMissing): ?Department
    {
        if (! $name) {
            return null;
        }

        return $createMissing
            ? Department::firstOrCreate(['name' => $name])
            : Department::query()->where('name', $name)->first();
    }

    protected function normalizeStatus(string $status): ?string
    {
        $status = strtolower(trim($status));

        if ($status === '') {
            return null;
        }

        return in_array($status, [UserStatus::Active->value, UserStatus::Inactive->value], true)
            ? $status
            : null;
    }

    protected function getUploadedFile(): TemporaryUploadedFile|UploadedFile|null
    {
        $file = $this->data['file'] ?? null;

        return $this->resolveUploadedFile($file);
    }

    protected function resolveUploadedFile(mixed $file): TemporaryUploadedFile|UploadedFile|null
    {
        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            return $file;
        }

        return Arr::first((array) $file);
    }

    /**
     * @return array<int, string>
     */
    protected function getHeaderOptions(): array
    {
        $file = $this->getUploadedFile();

        if (! $file) {
            return [];
        }

        $headers = $this->getCsvHeaders($file);

        if (empty($headers)) {
            return [];
        }

        return array_combine($headers, $headers);
    }

    /**
     * @return array<int, string>
     */
    protected function getCsvHeaders(TemporaryUploadedFile|UploadedFile $file): array
    {
        $stream = $this->openStream($file);

        if (! $stream) {
            return [];
        }

        $reader = Reader::createFromStream($stream);
        $reader->setHeaderOffset(0);

        return array_filter(array_map('trim', $reader->getHeader()));
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, string|null>
     */
    protected function guessColumnMap(array $headers): array
    {
        $lowerHeaders = array_map('strtolower', $headers);
        $mapping = [];

        foreach ($this->getMappingFields() as $field => $label) {
            $guess = strtolower(str_replace(' ', '_', $label));
            $index = array_search($guess, $lowerHeaders, true);

            if ($index === false) {
                $index = array_search(strtolower($field), $lowerHeaders, true);
            }

            $mapping[$field] = $index === false ? null : $headers[$index];
        }

        return $mapping;
    }

    protected function openStream(TemporaryUploadedFile|UploadedFile $file)
    {
        if (method_exists($file, 'readStream')) {
            return $file->readStream();
        }

        $path = $file->getRealPath();

        if (! $path) {
            return null;
        }

        return fopen($path, 'r');
    }

    protected function shouldCreateMissingDepartments(): bool
    {
        return (bool) ($this->data['create_missing_departments'] ?? true);
    }

    protected function shouldUpdateExisting(): bool
    {
        return (bool) ($this->data['update_existing'] ?? false);
    }

    protected function getDefaultStatus(): string
    {
        return (string) ($this->data['default_status'] ?? UserStatus::Active->value);
    }

    protected function findExistingEmployee(string $employeeId): ?Employee
    {
        if ($employeeId !== '') {
            $existing = Employee::query()->where('employee_id', $employeeId)->first();
            if ($existing) {
                return $existing;
            }
        }

        return null;
    }
}
