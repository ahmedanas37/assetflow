<?php

namespace Database\Seeders;

use App\Domain\Inventory\Enums\CategoryType;
use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Models\Manufacturer;
use App\Domain\Locations\Models\Location;
use App\Domain\People\Models\Department;
use App\Domain\Vendors\Models\Vendor;
use Illuminate\Database\Seeder;

class CoreDataSeeder extends Seeder
{
    public function run(): void
    {
        $vendorEmail = (string) config('assetflow.defaults.vendor_contact_email', 'procurement@example.local');

        $department = Department::firstOrCreate(['name' => 'IT'], [
            'notes' => 'Default IT department',
        ]);

        Location::firstOrCreate(['name' => 'HQ'], [
            'notes' => 'Primary location',
        ]);

        $manufacturer = Manufacturer::firstOrCreate(['name' => 'Generic'], [
            'notes' => 'Default manufacturer',
        ]);

        $category = Category::firstOrCreate(['name' => 'Laptop'], [
            'type' => CategoryType::Asset->value,
            'depreciation_months' => 36,
            'prefix' => 'LAP',
        ]);

        Category::firstOrCreate(['name' => 'Accessories'], [
            'type' => CategoryType::Accessory->value,
        ]);

        $assetModel = AssetModel::withTrashed()->firstOrNew([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Generic Laptop',
            'model_number' => 'GEN-LAP',
        ]);

        $assetModel->fill([
            'category_id' => $category->id,
            'depreciation_months' => 36,
        ]);

        if ($assetModel->trashed()) {
            $assetModel->restore();
        }

        $assetModel->save();

        Vendor::firstOrCreate(['name' => 'Default Vendor'], [
            'contact_name' => 'Procurement',
            'email' => $vendorEmail,
        ]);
    }
}
