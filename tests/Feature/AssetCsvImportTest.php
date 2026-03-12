<?php

namespace Tests\Feature;

use App\Domain\Assets\Models\StatusLabel;
use App\Filament\Resources\AssetResource\Pages\ImportAssets;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AssetCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_creates_asset(): void
    {
        StatusLabel::factory()->create([
            'name' => 'In Stock',
            'deployable' => true,
            'is_default' => true,
        ]);

        $user = User::factory()->create();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $importPermission = Permission::firstOrCreate([
            'name' => 'import assets',
            'guard_name' => 'web',
        ]);
        $panelPermission = Permission::firstOrCreate([
            'name' => 'access admin panel',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo([$importPermission, $panelPermission]);

        $this->actingAs($user);

        $csv = implode("\n", [
            'asset_tag,serial,model,model_number,manufacturer,category,status,location,vendor,purchase_date,purchase_cost,warranty_end_date,notes',
            'LAP-000001,SN-12345,ThinkPad T14,T14-Gen3,Lenovo,Laptop,In Stock,HQ,Default Vendor,2025-01-10,1200.00,2028-01-10,Initial import',
        ]);

        $file = UploadedFile::fake()->createWithContent('assets.csv', $csv);

        $mapping = [
            'asset_tag' => 'asset_tag',
            'serial' => 'serial',
            'model' => 'model',
            'model_number' => 'model_number',
            'manufacturer' => 'manufacturer',
            'category' => 'category',
            'status' => 'status',
            'location' => 'location',
            'vendor' => 'vendor',
            'purchase_date' => 'purchase_date',
            'purchase_cost' => 'purchase_cost',
            'warranty_end_date' => 'warranty_end_date',
            'notes' => 'notes',
        ];

        $page = app(ImportAssets::class);
        $page->data = [
            'file' => $file,
            'column_map' => $mapping,
            'create_missing' => true,
        ];
        $page->import();

        $this->assertDatabaseHas('assets', [
            'asset_tag' => 'LAP-000001',
            'serial' => 'SN-12345',
        ]);
    }
}
