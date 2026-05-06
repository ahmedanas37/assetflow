<?php

namespace Tests\Feature;

use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\People\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_limited_scan_page(): void
    {
        User::factory()->create();
        $assignee = User::factory()->create([
            'name' => 'Private Assignee',
        ]);
        $asset = Asset::factory()->create();
        AssetAssignment::create([
            'asset_id' => $asset->id,
            'assigned_to_type' => AssignmentType::User->value,
            'assigned_to_id' => $assignee->id,
            'assigned_by_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $this->get(route('assetflow.assets.scan', $asset))
            ->assertOk()
            ->assertSee($asset->asset_tag)
            ->assertSee('Login to manage this asset')
            ->assertDontSee('Open Admin Record')
            ->assertDontSee('Private Assignee')
            ->assertDontSee('Serial');
    }

    public function test_authorized_user_sees_scan_management_actions_and_acceptance_link(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $actor = User::factory()->create([
            'status' => UserStatus::Active->value,
        ]);
        $actor->assignRole('Admin');

        $assignee = User::factory()->create();
        $asset = Asset::factory()->create();
        AssetAssignment::create([
            'asset_id' => $asset->id,
            'assigned_to_type' => AssignmentType::User->value,
            'assigned_to_id' => $assignee->id,
            'assigned_by_user_id' => $actor->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($actor)
            ->get(route('assetflow.assets.scan', $asset))
            ->assertOk()
            ->assertSee('Open Admin Record')
            ->assertSee('Print Receipt')
            ->assertSee('/receipts/assets/', false);
    }

    public function test_label_page_points_qr_verification_to_scan_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $actor = User::factory()->create([
            'status' => UserStatus::Active->value,
        ]);
        $actor->assignRole('Admin');

        $asset = Asset::factory()->create();

        $this->actingAs($actor)
            ->get(route('assetflow.labels.single', $asset))
            ->assertOk()
            ->assertSee(route('assetflow.assets.scan', $asset), false);
    }
}
