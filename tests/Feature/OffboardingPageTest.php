<?php

namespace Tests\Feature;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\People\Enums\UserStatus;
use App\Filament\Pages\OffboardingPage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OffboardingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_offboarding_page_can_check_in_all_user_assignments(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $actor = User::factory()->create([
            'status' => UserStatus::Active->value,
        ]);
        $actor->assignRole('Admin');

        $target = User::factory()->create([
            'status' => UserStatus::Active->value,
        ]);

        $asset = Asset::factory()->create();
        $assetAssignment = AssetAssignment::create([
            'asset_id' => $asset->id,
            'assigned_to_type' => AssignmentType::User->value,
            'assigned_to_id' => $target->id,
            'assigned_by_user_id' => $actor->id,
            'assigned_at' => now()->subDay(),
        ]);

        $accessory = Accessory::factory()->create([
            'quantity_total' => 5,
            'quantity_available' => 2,
        ]);
        $accessoryAssignment = AccessoryAssignment::create([
            'accessory_id' => $accessory->id,
            'assigned_to_type' => AssignmentType::User->value,
            'assigned_to_id' => $target->id,
            'assigned_by_user_id' => $actor->id,
            'assigned_at' => now()->subDay(),
            'quantity' => 3,
            'returned_quantity' => 1,
        ]);

        Livewire::actingAs($actor)
            ->test(OffboardingPage::class)
            ->set('data.target', 'user:'.$target->id)
            ->call('checkinAll')
            ->assertHasNoErrors();

        $this->assertFalse($assetAssignment->refresh()->is_active);
        $this->assertNotNull($assetAssignment->returned_at);
        $this->assertFalse($accessoryAssignment->refresh()->is_active);
        $this->assertSame(3, $accessoryAssignment->returned_quantity);
        $this->assertSame(4, $accessory->refresh()->quantity_available);
    }

    public function test_offboarding_page_can_mark_user_inactive(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $actor = User::factory()->create([
            'status' => UserStatus::Active->value,
        ]);
        $actor->assignRole('Admin');

        $target = User::factory()->create([
            'status' => UserStatus::Active->value,
        ]);

        Livewire::actingAs($actor)
            ->test(OffboardingPage::class)
            ->set('data.target', 'user:'.$target->id)
            ->call('markInactive')
            ->assertHasNoErrors();

        $this->assertSame(UserStatus::Inactive, $target->refresh()->status);
    }
}
