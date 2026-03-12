<?php

namespace Tests\Feature;

use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Assets\Services\AssignmentService;
use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Models\Manufacturer;
use App\Domain\Locations\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssetCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_fails_when_asset_is_not_deployable(): void
    {
        $status = StatusLabel::factory()->create([
            'name' => 'Repair',
            'deployable' => false,
        ]);
        $asset = $this->createAssetWithStatus($status);
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(AssignmentService::class)->checkout(
            asset: $asset,
            type: AssignmentType::User,
            assignedToId: $user->id,
            actor: $user,
        );
    }

    public function test_checkout_fails_when_asset_is_already_checked_out(): void
    {
        $status = StatusLabel::factory()->create([
            'name' => 'In Stock',
            'deployable' => true,
        ]);
        $asset = $this->createAssetWithStatus($status);
        $user = User::factory()->create();

        AssetAssignment::create([
            'asset_id' => $asset->id,
            'assigned_to_type' => AssignmentType::User->value,
            'assigned_to_id' => $user->id,
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
            'due_at' => null,
            'returned_at' => null,
        ]);

        $this->expectException(ValidationException::class);

        app(AssignmentService::class)->checkout(
            asset: $asset,
            type: AssignmentType::User,
            assignedToId: $user->id,
            actor: $user,
        );
    }

    public function test_checkin_fails_when_asset_is_not_checked_out(): void
    {
        $status = StatusLabel::factory()->create([
            'name' => 'In Stock',
            'deployable' => true,
        ]);
        $asset = $this->createAssetWithStatus($status);
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(AssignmentService::class)->checkin(
            asset: $asset,
            actor: $user,
        );
    }

    private function createAssetWithStatus(StatusLabel $status): Asset
    {
        $category = Category::factory()->create();
        $manufacturer = Manufacturer::factory()->create();
        $model = AssetModel::factory()->create([
            'category_id' => $category->id,
            'manufacturer_id' => $manufacturer->id,
        ]);
        $location = Location::factory()->create();

        return Asset::factory()->create([
            'asset_model_id' => $model->id,
            'category_id' => $category->id,
            'status_label_id' => $status->id,
            'location_id' => $location->id,
        ]);
    }
}
