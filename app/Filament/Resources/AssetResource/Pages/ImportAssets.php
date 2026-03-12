<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Assets\Services\AssetTagService;
use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Models\Manufacturer;
use App\Domain\Locations\Models\Location;
use App\Domain\Vendors\Models\Vendor;
use App\Filament\Resources\AssetResource;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use League\Csv\Reader;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportAssets extends Page
{
    protected static string $resource = AssetResource::class;

    protected static string $view = 'filament.resources.asset-resource.pages.import-assets';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    /** @var array<int, array<string, string>> */
    public array $previewRows = [];

    /** @var array<int, array{row: int, errors: array<int, string>}> */
    public array $validationErrors = [];

    public int $totalRows = 0;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('import assets') ?? false;
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
                Toggle::make('create_missing')
                    ->label('Create missing reference data')
                    ->helperText('Create categories, models, manufacturers, locations, or vendors when not found.')
                    ->default(true),
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

        $rows = $this->readRows(createMissing: $this->shouldCreateMissing());
        $imported = 0;

        foreach ($rows as $row) {
            $assetTag = $row['asset_tag'] ?: app(AssetTagService::class)->generate($row['category_id'] ?? null);

            $asset = Asset::create([
                'asset_tag' => $assetTag,
                'serial' => $row['serial'] ?: null,
                'asset_model_id' => $row['asset_model_id'],
                'category_id' => $row['category_id'],
                'status_label_id' => $row['status_label_id'],
                'location_id' => $row['location_id'],
                'vendor_id' => $row['vendor_id'],
                'purchase_date' => $row['purchase_date'],
                'purchase_cost' => $row['purchase_cost'],
                'warranty_end_date' => $row['warranty_end_date'],
                'notes' => $row['notes'],
            ]);

            if ($asset) {
                $imported++;
            }
        }

        Notification::make()
            ->title('Import complete')
            ->body("Imported {$imported} assets.")
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function getMappingFields(): array
    {
        return [
            'asset_tag' => 'Asset Tag',
            'serial' => 'Serial',
            'model' => 'Model',
            'model_number' => 'Model Number',
            'manufacturer' => 'Manufacturer',
            'category' => 'Category',
            'status' => 'Status Label',
            'location' => 'Location',
            'vendor' => 'Vendor',
            'purchase_date' => 'Induction Date',
            'purchase_cost' => 'Purchase Cost',
            'warranty_end_date' => 'Warranty End Date',
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
        $createMissing = $this->shouldCreateMissing();
        $index = 0;

        foreach ($records as $record) {
            $mapped = $this->parseRecord($record);
            $resolved = $this->resolveRow($mapped, false, false);
            $rowErrors = [];

            if (! $mapped['asset_tag'] && ! $mapped['category'] && ! $mapped['model']) {
                $rowErrors[] = 'Asset tag is required when category or model is missing.';
            }

            if ($mapped['asset_tag'] && Asset::query()->where('asset_tag', $mapped['asset_tag'])->exists()) {
                $rowErrors[] = 'Asset tag already exists.';
            }

            if ($mapped['serial'] && Asset::query()->where('serial', $mapped['serial'])->exists()) {
                $rowErrors[] = 'Serial already exists.';
            }

            if (! $mapped['model']) {
                $rowErrors[] = 'Model is required.';
            } elseif (! $createMissing && ! $resolved['asset_model_id']) {
                $rowErrors[] = 'Model not found.';
            }

            if (! $mapped['location']) {
                $rowErrors[] = 'Location is required.';
            } elseif (! $createMissing && ! $resolved['location_id']) {
                $rowErrors[] = 'Location not found.';
            }

            if ($mapped['category'] && ! $createMissing && ! $resolved['category_id']) {
                $rowErrors[] = 'Category not found.';
            }

            if ($mapped['manufacturer'] && ! $createMissing && ! $resolved['manufacturer_id']) {
                $rowErrors[] = 'Manufacturer not found.';
            }

            if ($mapped['vendor'] && ! $createMissing && ! $resolved['vendor_id']) {
                $rowErrors[] = 'Vendor not found.';
            }

            if ($mapped['status'] && ! $resolved['status_label_id']) {
                $rowErrors[] = 'Status label not found.';
            }

            if ($mapped['purchase_cost'] && ! is_numeric($mapped['purchase_cost'])) {
                $rowErrors[] = 'Purchase cost must be numeric.';
            }

            if (! empty($mapped['purchase_date']) && ! $this->isValidDate($mapped['purchase_date'])) {
                $rowErrors[] = 'Induction date is invalid.';
            }

            if (! empty($mapped['warranty_end_date']) && ! $this->isValidDate($mapped['warranty_end_date'])) {
                $rowErrors[] = 'Warranty end date is invalid.';
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
        return $this->resolveRow($this->parseRecord($record), $createMissing, true);
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

    protected function resolveRow(array $mapped, bool $createMissing, bool $fallbackStatus): array
    {
        $category = $this->resolveCategory($mapped['category'], $createMissing);
        $manufacturer = $this->resolveManufacturer($mapped['manufacturer'], $createMissing);
        $assetModel = $this->resolveAssetModel(
            modelName: $mapped['model'],
            modelNumber: $mapped['model_number'],
            category: $category,
            manufacturer: $manufacturer,
            createMissing: $createMissing,
        );
        $location = $this->resolveLocation($mapped['location'], $createMissing);
        $vendor = $this->resolveVendor($mapped['vendor'], $createMissing);
        $status = $this->resolveStatus($mapped['status'], $fallbackStatus);

        return [
            'asset_tag' => $mapped['asset_tag'],
            'serial' => $mapped['serial'],
            'asset_model_id' => $assetModel?->id,
            'category_id' => $category?->id ?? $assetModel?->category_id,
            'manufacturer_id' => $manufacturer?->id,
            'status_label_id' => $status?->id,
            'location_id' => $location?->id,
            'vendor_id' => $vendor?->id,
            'purchase_date' => $mapped['purchase_date'] ?: null,
            'purchase_cost' => $mapped['purchase_cost'] ?: null,
            'warranty_end_date' => $mapped['warranty_end_date'] ?: null,
            'notes' => $mapped['notes'] ?: null,
        ];
    }

    protected function resolveCategory(string $name, bool $createMissing): ?Category
    {
        if (! $name) {
            return $createMissing ? Category::firstOrCreate(['name' => 'Uncategorized']) : null;
        }

        return $createMissing
            ? Category::firstOrCreate(['name' => $name])
            : Category::query()->where('name', $name)->first();
    }

    protected function resolveManufacturer(string $name, bool $createMissing): ?Manufacturer
    {
        $name = $name ?: 'Generic';

        return $createMissing
            ? Manufacturer::firstOrCreate(['name' => $name])
            : Manufacturer::query()->where('name', $name)->first();
    }

    protected function resolveAssetModel(
        string $modelName,
        string $modelNumber,
        ?Category $category,
        ?Manufacturer $manufacturer,
        bool $createMissing,
    ): ?AssetModel {
        if (! $modelName) {
            return null;
        }

        $categoryId = $category?->id;
        $manufacturerId = $manufacturer?->id;

        return $createMissing
            ? AssetModel::firstOrCreate([
                'manufacturer_id' => $manufacturerId ?? Manufacturer::firstOrCreate(['name' => 'Generic'])->id,
                'category_id' => $categoryId ?? Category::firstOrCreate(['name' => 'Uncategorized'])->id,
                'name' => $modelName,
                'model_number' => $modelNumber ?: null,
            ])
            : AssetModel::query()
                ->where('name', $modelName)
                ->when($manufacturerId, fn ($query) => $query->where('manufacturer_id', $manufacturerId))
                ->first();
    }

    protected function resolveLocation(string $name, bool $createMissing): ?Location
    {
        if (! $name) {
            return $createMissing ? Location::firstOrCreate(['name' => 'Unassigned']) : null;
        }

        return $createMissing
            ? Location::firstOrCreate(['name' => $name])
            : Location::query()->where('name', $name)->first();
    }

    protected function resolveVendor(string $name, bool $createMissing): ?Vendor
    {
        if (! $name) {
            return null;
        }

        return $createMissing
            ? Vendor::firstOrCreate(['name' => $name])
            : Vendor::query()->where('name', $name)->first();
    }

    protected function resolveStatus(string $name, bool $fallbackToDefault = true): ?StatusLabel
    {
        if ($name) {
            $status = StatusLabel::query()->where('name', $name)->first();
            if ($status) {
                return $status;
            }

            if (! $fallbackToDefault) {
                return null;
            }
        }

        return $fallbackToDefault
            ? StatusLabel::query()->where('is_default', true)->first()
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

    protected function isValidDate(string $value): bool
    {
        try {
            Carbon::parse($value);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
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

    protected function shouldCreateMissing(): bool
    {
        return (bool) ($this->data['create_missing'] ?? true);
    }
}
