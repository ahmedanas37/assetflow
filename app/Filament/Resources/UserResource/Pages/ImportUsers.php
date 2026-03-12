<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Domain\People\Enums\UserStatus;
use App\Domain\People\Models\Department;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use League\Csv\Reader;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Permission\Models\Role;

class ImportUsers extends Page
{
    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.resources.user-resource.pages.import-users';

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
            'default_password' => trim((string) config('assetflow.defaults.import_default_password', '')),
        ]);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('import users') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('file')
                    ->label('CSV File')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->storeFiles(false)
                    ->required()
                    ->afterStateUpdated(function (Forms\Set $set, ?TemporaryUploadedFile $state): void {
                        if (! $state) {
                            return;
                        }

                        $headers = $this->getCsvHeaders($state);
                        $mapping = $this->guessColumnMap($headers);

                        $set('column_map', $mapping);
                    }),
                Fieldset::make('Column Mapping')
                    ->columns(2)
                    ->schema($this->getMappingSchema())
                    ->statePath('column_map'),
                Toggle::make('create_missing_departments')
                    ->label('Create missing departments')
                    ->default(true),
                Toggle::make('update_existing')
                    ->label('Update existing users by email')
                    ->default(false),
                Select::make('default_role')
                    ->label('Default Role (when roles column is empty)')
                    ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->placeholder('No default role'),
                Select::make('default_status')
                    ->label('Default Status')
                    ->options([
                        UserStatus::Active->value => 'Active',
                        UserStatus::Inactive->value => 'Inactive',
                    ])
                    ->default(UserStatus::Active->value),
                TextInput::make('default_password')
                    ->label('Default Password (if password column is empty)')
                    ->password()
                    ->revealable()
                    ->placeholder('Leave blank to require per-row passwords in CSV')
                    ->minLength(12)
                    ->maxLength(255),
            ])
            ->statePath('data');
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

        foreach ($rows as $row) {
            $existing = User::query()->where('email', $row['email'])->first();

            if ($existing && $this->shouldUpdateExisting()) {
                $existing->name = $row['name'];

                if ($row['username']) {
                    $existing->username = $row['username'];
                }

                if ($row['department_id']) {
                    $existing->department_id = $row['department_id'];
                }

                if ($row['status']) {
                    $existing->status = $row['status'];
                }

                if ($row['password']) {
                    $existing->password = Hash::make($row['password']);
                }

                $existing->save();

                if (! empty($row['roles'])) {
                    $existing->syncRoles($row['roles']);
                }

                $updated++;

                continue;
            }

            $password = $row['password'] ?: $this->getDefaultPassword();

            $user = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'username' => $row['username'] ?: null,
                'department_id' => $row['department_id'],
                'status' => $row['status'] ?: $this->getDefaultStatus(),
                'password' => Hash::make($password),
            ]);

            if (! empty($row['roles'])) {
                $user->syncRoles($row['roles']);
            } elseif ($defaultRole = $this->getDefaultRole()) {
                $user->assignRole($defaultRole);
            }

            $created++;
        }

        Notification::make()
            ->title('Import complete')
            ->body("Created {$created} users, updated {$updated} users.")
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getMappingFields(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'username' => 'Username',
            'department' => 'Department',
            'status' => 'Status',
            'roles' => 'Roles',
            'password' => 'Password',
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
        $updateExisting = $this->shouldUpdateExisting();
        $defaultPassword = $this->getDefaultPassword();

        $seenEmails = [];
        $seenUsernames = [];

        $index = 0;

        foreach ($records as $record) {
            $mapped = $this->parseRecord($record);
            $rowErrors = [];

            $email = strtolower(trim($mapped['email']));
            $username = trim($mapped['username']);

            if ($mapped['name'] === '') {
                $rowErrors[] = 'Name is required.';
            }

            if ($email === '') {
                $rowErrors[] = 'Email is required.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Email is invalid.';
            } elseif (in_array($email, $seenEmails, true)) {
                $rowErrors[] = 'Duplicate email in CSV.';
            } else {
                $seenEmails[] = $email;
            }

            if ($username !== '') {
                if (in_array($username, $seenUsernames, true)) {
                    $rowErrors[] = 'Duplicate username in CSV.';
                } else {
                    $seenUsernames[] = $username;
                }
            }

            $existing = $email ? User::query()->where('email', $email)->first() : null;

            if ($existing && ! $updateExisting) {
                $rowErrors[] = 'Email already exists.';
            }

            if ($username !== '') {
                $usernameOwner = User::query()->where('username', $username)->first();

                if ($usernameOwner && (! $existing || $usernameOwner->id !== $existing->id)) {
                    $rowErrors[] = 'Username already exists.';
                }
            }

            $status = strtolower(trim($mapped['status']));
            if ($status !== '' && ! in_array($status, [UserStatus::Active->value, UserStatus::Inactive->value], true)) {
                $rowErrors[] = 'Status must be active or inactive.';
            }

            $roles = $this->parseRoles($mapped['roles']);
            if (! empty($roles)) {
                $existingRoles = Role::query()->whereIn('name', $roles)->pluck('name')->all();
                $missing = array_diff($roles, $existingRoles);

                if (! empty($missing)) {
                    $rowErrors[] = 'Roles not found: '.implode(', ', $missing).'.';
                }
            }

            if ($mapped['department'] !== '' && ! $createMissing) {
                if (! Department::query()->where('name', $mapped['department'])->exists()) {
                    $rowErrors[] = 'Department not found.';
                }
            }

            if (! $existing && empty($mapped['password']) && empty($defaultPassword)) {
                $rowErrors[] = 'Password is required when default password is empty.';
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
            'name' => $mapped['name'],
            'email' => strtolower($mapped['email']),
            'username' => $mapped['username'],
            'department_id' => $department?->id,
            'status' => $this->normalizeStatus($mapped['status']),
            'roles' => $this->parseRoles($mapped['roles']),
            'password' => $mapped['password'],
        ];
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

    /**
     * @return array<int, string>
     */
    protected function parseRoles(string $roles): array
    {
        if ($roles === '') {
            return [];
        }

        $parts = preg_split('/[;,]/', $roles) ?: [];

        return collect($parts)
            ->map(fn (string $role) => trim($role))
            ->filter()
            ->unique()
            ->values()
            ->all();
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

    protected function getDefaultRole(): ?string
    {
        $role = $this->data['default_role'] ?? null;

        return $role ?: null;
    }

    protected function getDefaultStatus(): string
    {
        return (string) ($this->data['default_status'] ?? UserStatus::Active->value);
    }

    protected function getDefaultPassword(): string
    {
        return (string) ($this->data['default_password'] ?? '');
    }
}
