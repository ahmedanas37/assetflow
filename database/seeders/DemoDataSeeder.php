<?php

namespace Database\Seeders;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Enums\AssetCondition;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Inventory\Enums\CategoryType;
use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Models\Manufacturer;
use App\Domain\Locations\Models\Location;
use App\Domain\Maintenance\Enums\MaintenanceStatus;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Maintenance\Models\MaintenanceLog;
use App\Domain\People\Enums\UserStatus;
use App\Domain\People\Models\Department;
use App\Domain\People\Models\Employee;
use App\Domain\Vendors\Models\Vendor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            StatusLabelsSeeder::class,
            CoreDataSeeder::class,
            PortalSettingsSeeder::class,
        ]);

        $faker = fake();
        $seedKey = now()->format('YmdHis').strtoupper(Str::random(3));
        $demoEmailDomain = $this->resolveEmailDomain();
        $demoPassword = trim((string) config('assetflow.defaults.demo_password', ''));
        if ($demoPassword === '') {
            $demoPassword = Str::password(20);

            if ($this->command) {
                $this->command->warn('ASSETFLOW_DEMO_PASSWORD is empty. Generated a random demo password for this seed run:');
                $this->command->line($demoPassword);
            }
        }

        $departments = collect();
        foreach (['IT', 'Finance', 'HR', 'Operations', 'Sales', 'Support', 'Procurement'] as $name) {
            $departments->push(Department::firstOrCreate(
                ['name' => $name],
                ['notes' => "Demo department: {$name}"],
            ));
        }

        $users = collect();
        $fixedAccounts = [
            ['name' => 'Demo Admin', 'email' => $this->demoEmail('demo.admin', $demoEmailDomain), 'username' => 'demo_admin', 'role' => 'Admin'],
            ['name' => 'Demo Manager', 'email' => $this->demoEmail('demo.manager', $demoEmailDomain), 'username' => 'demo_manager', 'role' => 'IT Manager'],
            ['name' => 'Demo Viewer', 'email' => $this->demoEmail('demo.viewer', $demoEmailDomain), 'username' => 'demo_viewer', 'role' => 'Read-only'],
        ];

        foreach ($fixedAccounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'department_id' => $departments->random()->id,
                    'status' => UserStatus::Active->value,
                    'password' => Hash::make($demoPassword),
                ],
            );

            $user->syncRoles([$account['role']]);
            $users->push($user);
        }

        for ($i = 1; $i <= 18; $i++) {
            $status = $i <= 15 ? UserStatus::Active->value : UserStatus::Inactive->value;
            $user = User::factory()->create([
                'name' => $faker->name(),
                'email' => $this->demoEmail("demo.user.{$seedKey}.{$i}", $demoEmailDomain),
                'username' => "demo_user_{$seedKey}_{$i}",
                'department_id' => $departments->random()->id,
                'status' => $status,
                'password' => Hash::make($demoPassword),
            ]);

            $user->assignRole(Arr::random(['IT Manager', 'Read-only']));
            $users->push($user);
        }

        foreach ($departments as $department) {
            $department->manager_user_id = $users->random()->id;
            $department->save();
        }

        $employees = collect();
        for ($i = 1; $i <= 45; $i++) {
            $employees->push(Employee::create([
                'employee_id' => "EMP-{$seedKey}-".str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => $faker->name(),
                'email' => $faker->boolean(85) ? $this->demoEmail("employee.{$seedKey}.{$i}", $demoEmailDomain) : null,
                'department_id' => $departments->random()->id,
                'title' => $faker->jobTitle(),
                'phone' => $faker->phoneNumber(),
                'status' => $i <= 38 ? UserStatus::Active->value : UserStatus::Inactive->value,
                'notes' => $faker->sentence(),
            ]));
        }

        $locations = collect();
        $hq = Location::firstOrCreate(['name' => 'HQ'], [
            'address' => 'Main Campus',
            'notes' => 'Primary office location',
        ]);
        $locations->push($hq);

        foreach (['HQ - Floor 1', 'HQ - Floor 2', 'HQ - IT Store', 'HQ - Server Room', 'Regional Office', 'Warehouse'] as $name) {
            $locations->push(Location::firstOrCreate(
                ['name' => $name],
                [
                    'parent_id' => str_starts_with($name, 'HQ -') ? $hq->id : null,
                    'address' => $faker->address(),
                    'notes' => "Demo location: {$name}",
                ],
            ));
        }

        for ($i = 1; $i <= 4; $i++) {
            $locations->push(Location::create([
                'name' => "Demo Lab {$seedKey}-{$i}",
                'parent_id' => $hq->id,
                'address' => $faker->address(),
                'notes' => 'Demo temporary lab location',
            ]));
        }

        $manufacturers = collect();
        foreach (['Dell', 'HP', 'Lenovo', 'Apple', 'Cisco', 'Samsung', 'Logitech', 'Microsoft', 'Acer', 'Asus'] as $name) {
            $manufacturers->push(Manufacturer::firstOrCreate(
                ['name' => $name],
                ['notes' => "Demo manufacturer: {$name}"],
            ));
        }

        for ($i = 1; $i <= 3; $i++) {
            $manufacturers->push(Manufacturer::factory()->create([
                'name' => "Demo Manufacturer {$seedKey}-{$i}",
            ]));
        }

        $vendors = collect();
        foreach (['Tech Depot', 'Global Supply', 'Office Hub', 'Infra Wholesale', 'Prime IT Vendors', 'Contoso Supply'] as $name) {
            $vendors->push(Vendor::firstOrCreate(
                ['name' => $name],
                [
                    'contact_name' => $faker->name(),
                    'email' => Str::slug($name)."@vendors.{$demoEmailDomain}",
                    'phone' => $faker->phoneNumber(),
                    'website' => 'https://'.Str::slug($name).'.example.com',
                    'address' => $faker->address(),
                    'notes' => "Demo vendor: {$name}",
                ],
            ));
        }

        for ($i = 1; $i <= 3; $i++) {
            $vendors->push(Vendor::factory()->create([
                'name' => "Demo Vendor {$seedKey}-{$i}",
            ]));
        }

        $assetCategories = collect();
        foreach ([
            ['name' => 'Laptop', 'prefix' => 'LAP', 'depreciation_months' => 36],
            ['name' => 'Desktop', 'prefix' => 'DESK', 'depreciation_months' => 48],
            ['name' => 'Monitor', 'prefix' => 'MON', 'depreciation_months' => 48],
            ['name' => 'Server', 'prefix' => 'SRV', 'depreciation_months' => 60],
            ['name' => 'Network Device', 'prefix' => 'NET', 'depreciation_months' => 60],
            ['name' => 'Phone', 'prefix' => 'PHN', 'depreciation_months' => 24],
            ['name' => 'Printer', 'prefix' => 'PRN', 'depreciation_months' => 48],
        ] as $spec) {
            $assetCategories->push(Category::updateOrCreate(
                ['name' => $spec['name']],
                [
                    'type' => CategoryType::Asset->value,
                    'prefix' => $spec['prefix'],
                    'depreciation_months' => $spec['depreciation_months'],
                    'notes' => "Demo asset category: {$spec['name']}",
                ],
            ));
        }

        $accessoryCategories = collect();
        foreach ([
            ['name' => 'Accessories', 'prefix' => 'ACC'],
            ['name' => 'Mouse', 'prefix' => 'MSE'],
            ['name' => 'Keyboard', 'prefix' => 'KBD'],
            ['name' => 'Headset', 'prefix' => 'HDS'],
            ['name' => 'Docking Station', 'prefix' => 'DOC'],
            ['name' => 'Cable', 'prefix' => 'CBL'],
            ['name' => 'Webcam', 'prefix' => 'CAM'],
        ] as $spec) {
            $accessoryCategories->push(Category::updateOrCreate(
                ['name' => $spec['name']],
                [
                    'type' => CategoryType::Accessory->value,
                    'prefix' => $spec['prefix'],
                    'depreciation_months' => null,
                    'notes' => "Demo accessory category: {$spec['name']}",
                ],
            ));
        }

        Category::updateOrCreate(
            ['name' => 'Consumables'],
            [
                'type' => CategoryType::Consumable->value,
                'prefix' => 'CON',
                'depreciation_months' => null,
                'notes' => 'Demo consumable category',
            ],
        );

        $assetModels = collect();
        foreach ($assetCategories as $category) {
            for ($i = 1; $i <= 2; $i++) {
                $manufacturer = $manufacturers->random();
                $modelName = "{$manufacturer->name} {$category->name} {$faker->randomElement(['Pro', 'Plus', 'Gen', 'Ultra'])}";
                $modelNumber = strtoupper(($category->prefix ?: 'MOD').'-'.$faker->bothify('##??'));

                $assetModels->push(AssetModel::firstOrCreate(
                    [
                        'manufacturer_id' => $manufacturer->id,
                        'category_id' => $category->id,
                        'name' => $modelName,
                        'model_number' => $modelNumber,
                    ],
                    [
                        'depreciation_months' => $category->depreciation_months ?: random_int(24, 60),
                        'notes' => 'Demo asset model',
                    ],
                ));
            }
        }

        $statuses = StatusLabel::query()
            ->whereIn('name', ['In Stock', 'Deployed', 'Repair', 'Retired', 'Lost'])
            ->get()
            ->keyBy('name');

        $inStock = $statuses->get('In Stock');
        $deployed = $statuses->get('Deployed');
        $repair = $statuses->get('Repair');
        $retired = $statuses->get('Retired');
        $lost = $statuses->get('Lost');

        $assets = collect();
        for ($i = 1; $i <= 80; $i++) {
            $model = $assetModels->random();
            $statusName = Arr::random([
                'In Stock',
                'In Stock',
                'In Stock',
                'In Stock',
                'Repair',
                'Retired',
                'Lost',
            ]);

            $statusId = $statuses->get($statusName)?->id
                ?? $inStock?->id
                ?? StatusLabel::query()->value('id');

            $assets->push(Asset::factory()->create([
                'asset_tag' => strtoupper(($model->category?->prefix ?: 'AST').'-'.$seedKey.'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT)),
                'serial' => $faker->boolean(78) ? strtoupper('SN-'.$seedKey.'-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)) : null,
                'asset_model_id' => $model->id,
                'category_id' => $model->category_id,
                'status_label_id' => $statusId,
                'location_id' => $locations->random()->id,
                'vendor_id' => $vendors->random()->id,
                'purchase_date' => now()->subDays(random_int(45, 1600))->toDateString(),
                'purchase_cost' => $faker->randomFloat(2, 250, 6000),
                'warranty_end_date' => now()->addDays(random_int(-180, 540))->toDateString(),
                'notes' => $faker->sentence(),
                'custom_fields' => [
                    'cpu' => Arr::random(['Intel i5', 'Intel i7', 'Ryzen 5', 'Ryzen 7', 'Apple M2']),
                    'ram_gb' => Arr::random([8, 16, 32]),
                    'os' => Arr::random(['Windows 11', 'Ubuntu 24.04', 'macOS Sonoma']),
                ],
            ]));
        }

        $activeUsers = $users
            ->filter(fn (User $user): bool => $user->status === UserStatus::Active)
            ->values();

        $activeCandidates = $assets->filter(function (Asset $asset): bool {
            return (bool) optional($asset->statusLabel)->deployable;
        })->values();

        $activeAssignmentCount = min(28, $activeCandidates->count());
        $activeAssets = $activeCandidates->shuffle()->take($activeAssignmentCount)->values();

        foreach ($activeAssets as $asset) {
            $target = $this->randomAssignmentTarget($activeUsers, $employees, $locations);
            $assignedAt = now()->subDays(random_int(1, 120));
            $dueAt = null;

            if ($faker->boolean(70)) {
                $dueAt = $faker->boolean(30)
                    ? now()->subDays(random_int(1, 20))
                    : now()->addDays(random_int(3, 45));
            }

            AssetAssignment::create([
                'asset_id' => $asset->id,
                'assigned_to_type' => $target['type']->value,
                'assigned_to_id' => $target['id'],
                'assigned_to_label' => $target['label'],
                'assigned_by_user_id' => $activeUsers->random()->id,
                'assigned_at' => $assignedAt,
                'due_at' => $dueAt,
                'returned_at' => null,
                'return_condition' => null,
                'notes' => 'Demo active assignment',
                'location_at_assignment' => optional($asset->location)->name,
                'transferred_from_id' => null,
            ]);

            if ($deployed) {
                $asset->status_label_id = $deployed->id;
                $asset->save();
            }
        }

        $historicalAssignmentCount = min(35, $assets->count());
        $historicalAssets = $assets->shuffle()->take($historicalAssignmentCount)->values();

        foreach ($historicalAssets as $asset) {
            $target = $this->randomAssignmentTarget($activeUsers, $employees, $locations);
            $assignedAt = now()->subDays(random_int(45, 240));
            $returnedAt = (clone $assignedAt)->addDays(random_int(2, 40));

            AssetAssignment::create([
                'asset_id' => $asset->id,
                'assigned_to_type' => $target['type']->value,
                'assigned_to_id' => $target['id'],
                'assigned_to_label' => $target['label'],
                'assigned_by_user_id' => $activeUsers->random()->id,
                'assigned_at' => $assignedAt,
                'due_at' => (clone $assignedAt)->addDays(random_int(7, 30)),
                'returned_at' => $returnedAt,
                'return_condition' => Arr::random([
                    AssetCondition::Good->value,
                    AssetCondition::Fair->value,
                    AssetCondition::Damaged->value,
                ]),
                'notes' => 'Demo historical assignment',
                'location_at_assignment' => optional($asset->location)->name,
                'transferred_from_id' => null,
            ]);

            if ($asset->status_label_id === $deployed?->id && $faker->boolean(65)) {
                $asset->status_label_id = Arr::random([
                    $inStock?->id,
                    $repair?->id,
                    $retired?->id,
                    $lost?->id,
                ]) ?: $asset->status_label_id;
                $asset->save();
            }
        }

        $accessories = collect();
        $accessoryNames = [
            'Wireless Mouse',
            'Mechanical Keyboard',
            'USB-C Dock',
            'HDMI Cable',
            'Laptop Sleeve',
            'Noise Canceling Headset',
            'External Webcam',
            'Monitor Arm',
            'Power Adapter',
            'USB Hub',
            'Ethernet Adapter',
            'Conference Speaker',
            'Bluetooth Keyboard',
            'Gaming Mouse',
            'Docking Cradle',
            'Travel Charger',
            'Security Cable Lock',
            'Spare Battery',
            'Numeric Keypad',
            'DisplayPort Cable',
        ];

        foreach ($accessoryNames as $index => $name) {
            $total = random_int(12, 90);
            $accessories->push(Accessory::create([
                'name' => "{$name} {$seedKey}-".($index + 1),
                'category_id' => $accessoryCategories->random()->id,
                'manufacturer_id' => $manufacturers->random()->id,
                'vendor_id' => $vendors->random()->id,
                'location_id' => $locations->random()->id,
                'model_number' => strtoupper('ACC-'.$faker->bothify('###??')),
                'quantity_total' => $total,
                'quantity_available' => $total,
                'reorder_threshold' => random_int(3, 18),
                'notes' => 'Demo accessory inventory',
            ]));
        }

        $accessoryAssignmentCount = 0;
        for ($i = 1; $i <= 48; $i++) {
            $mode = Arr::random(['active', 'active', 'active', 'partial', 'returned']);
            $candidateAccessories = $mode === 'returned'
                ? $accessories
                : $accessories->filter(fn (Accessory $accessory): bool => $accessory->quantity_available > 0)->values();

            if ($candidateAccessories->isEmpty()) {
                break;
            }

            /** @var Accessory $accessory */
            $accessory = $candidateAccessories->random();

            $maxQuantity = $mode === 'returned'
                ? max(1, min(6, $accessory->quantity_total))
                : max(1, min(6, $accessory->quantity_available));

            if ($mode === 'partial' && $maxQuantity < 2) {
                $mode = 'active';
            }

            $quantity = $mode === 'partial'
                ? random_int(2, $maxQuantity)
                : random_int(1, $maxQuantity);

            $returnedQuantity = match ($mode) {
                'active' => 0,
                'partial' => random_int(1, $quantity - 1),
                default => $quantity,
            };

            $target = $this->randomAssignmentTarget($activeUsers, $employees, $locations);
            $assignedAt = now()->subDays(random_int(1, 160));
            $returnedAt = $returnedQuantity === $quantity
                ? (clone $assignedAt)->addDays(random_int(1, 45))
                : null;

            AccessoryAssignment::create([
                'accessory_id' => $accessory->id,
                'assigned_to_type' => $target['type']->value,
                'assigned_to_id' => $target['id'],
                'assigned_to_label' => $target['label'],
                'assigned_by_user_id' => $activeUsers->random()->id,
                'assigned_at' => $assignedAt,
                'due_at' => $faker->boolean(60) ? now()->addDays(random_int(5, 35)) : null,
                'returned_at' => $returnedAt,
                'quantity' => $quantity,
                'returned_quantity' => $returnedQuantity,
                'notes' => 'Demo accessory assignment',
                'location_at_assignment' => optional($accessory->location)->name,
            ]);

            $outstanding = $quantity - $returnedQuantity;
            if ($outstanding > 0) {
                $accessory->quantity_available = max(0, $accessory->quantity_available - $outstanding);
                $accessory->save();
            }

            $accessoryAssignmentCount++;
        }

        $maintenanceLogs = collect();
        for ($i = 1; $i <= 34; $i++) {
            $status = Arr::random([
                MaintenanceStatus::Open->value,
                MaintenanceStatus::Open->value,
                MaintenanceStatus::Closed->value,
            ]);

            $startDate = now()->subDays(random_int(3, 220));
            $endDate = $status === MaintenanceStatus::Closed->value
                ? (clone $startDate)->addDays(random_int(2, 35))
                : null;

            $maintenanceLogs->push(MaintenanceLog::create([
                'asset_id' => $assets->random()->id,
                'type' => Arr::random(array_map(
                    static fn (MaintenanceType $type): string => $type->value,
                    MaintenanceType::cases(),
                )),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate?->toDateString(),
                'cost' => $status === MaintenanceStatus::Closed->value
                    ? random_int(50, 2500)
                    : random_int(0, 600),
                'vendor_id' => $vendors->random()->id,
                'notes' => 'Demo maintenance log entry',
                'performed_by' => $faker->name(),
                'status' => $status,
            ]));
        }

        $attachmentCount = 0;
        foreach ($assets->shuffle()->take(12) as $asset) {
            $attachmentCount += $this->createDemoAttachment($asset, $activeUsers->random(), $seedKey, 'asset');
        }

        foreach ($maintenanceLogs->shuffle()->take(10) as $log) {
            $attachmentCount += $this->createDemoAttachment($log, $activeUsers->random(), $seedKey, 'maintenance');
        }

        Artisan::call('assetflow:update-metrics');

        $this->command?->info('Comprehensive demo data seeded successfully.');
        $this->command?->line(sprintf(
            'Created: %d users, %d employees, %d locations, %d assets, %d accessories, %d asset assignments, %d accessory assignments, %d maintenance logs, %d attachments.',
            $users->count(),
            $employees->count(),
            $locations->count(),
            $assets->count(),
            $accessories->count(),
            $activeAssignmentCount + $historicalAssignmentCount,
            $accessoryAssignmentCount,
            $maintenanceLogs->count(),
            $attachmentCount,
        ));
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Employee>  $employees
     * @param  Collection<int, Location>  $locations
     * @return array{type: AssignmentType, id: int, label: string|null}
     */
    private function randomAssignmentTarget(Collection $users, Collection $employees, Collection $locations): array
    {
        $type = Arr::random([
            AssignmentType::User,
            AssignmentType::Employee,
            AssignmentType::Location,
        ]);

        return match ($type) {
            AssignmentType::User => [
                'type' => $type,
                'id' => $users->random()->id,
                'label' => null,
            ],
            AssignmentType::Employee => [
                'type' => $type,
                'id' => $employees->random()->id,
                'label' => null,
            ],
            AssignmentType::Location => [
                'type' => $type,
                'id' => $locations->random()->id,
                'label' => 'Desk-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            ],
        };
    }

    private function resolveEmailDomain(): string
    {
        $domain = strtolower(trim((string) config('assetflow.defaults.email_domain', 'example.local')));

        return $domain !== '' ? $domain : 'example.local';
    }

    private function demoEmail(string $localPart, string $domain): string
    {
        $localPart = strtolower(trim($localPart));
        $localPart = preg_replace('/[^a-z0-9._+-]/', '.', $localPart) ?? 'user';
        $localPart = trim($localPart, '.');
        $localPart = preg_replace('/\.{2,}/', '.', $localPart) ?? 'user';

        if ($localPart === '') {
            $localPart = 'user';
        }

        return "{$localPart}@{$domain}";
    }

    private function createDemoAttachment(Asset|MaintenanceLog $attachable, User $uploadedBy, string $seedKey, string $scope): int
    {
        $productName = (string) config('assetflow.product_name', config('app.name', 'AssetFlow'));

        $content = implode(PHP_EOL, [
            $productName.' Demo Attachment',
            'Scope: '.$scope,
            'Entity: '.class_basename($attachable).' #'.$attachable->id,
            'Generated: '.now()->toDateTimeString(),
        ]).PHP_EOL;

        $path = "demo/{$seedKey}/{$scope}-{$attachable->id}-".Str::lower(Str::random(8)).'.txt';
        Storage::disk('private')->put($path, $content);

        $attachable->attachments()->create([
            'disk' => 'private',
            'path' => $path,
            'original_name' => basename($path),
            'mime' => 'text/plain',
            'size' => strlen($content),
            'hash' => hash('sha256', $content),
            'uploaded_by' => $uploadedBy->id,
            'uploaded_at' => now()->subDays(random_int(0, 20)),
        ]);

        return 1;
    }
}
