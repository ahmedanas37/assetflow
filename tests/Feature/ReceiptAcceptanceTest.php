<?php

namespace Tests\Feature;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Mail\AccessoryAssignmentReceiptMail;
use App\Mail\AssetAssignmentReceiptMail;
use App\Models\User;
use App\Services\ReceiptAcceptanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_receipt_can_be_accepted_with_valid_token(): void
    {
        $assignment = $this->createAssetAssignment();
        $url = app(ReceiptAcceptanceService::class)->assetUrl($assignment);

        $this->get($url)
            ->assertOk()
            ->assertSee('Asset Receipt Acceptance')
            ->assertSee($assignment->asset->asset_tag);

        $this->post($url, [
            'accepted_by_name' => 'Jane Receiver',
        ])->assertRedirect($url);

        $assignment->refresh();

        $this->assertNotNull($assignment->accepted_at);
        $this->assertSame('Jane Receiver', $assignment->accepted_by_name);
        $this->assertNotNull($assignment->accepted_ip);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'receipt_accepted',
            'entity_type' => $assignment->asset->getMorphClass(),
            'entity_id' => $assignment->asset_id,
        ]);
    }

    public function test_asset_receipt_rejects_invalid_token(): void
    {
        $assignment = $this->createAssetAssignment();
        app(ReceiptAcceptanceService::class)->assetUrl($assignment);

        $this->get(route('assetflow.acceptance.asset.show', [$assignment, 'invalid-token']))
            ->assertNotFound();
    }

    public function test_accessory_receipt_can_be_accepted_with_valid_token(): void
    {
        $assignment = $this->createAccessoryAssignment();
        $url = app(ReceiptAcceptanceService::class)->accessoryUrl($assignment);

        $this->get($url)
            ->assertOk()
            ->assertSee('Accessory Receipt Acceptance')
            ->assertSee($assignment->accessory->name);

        $this->post($url, [
            'accepted_by_name' => 'Jane Receiver',
        ])->assertRedirect($url);

        $assignment->refresh();

        $this->assertNotNull($assignment->accepted_at);
        $this->assertSame('Jane Receiver', $assignment->accepted_by_name);
        $this->assertNotNull($assignment->accepted_ip);
    }

    public function test_assignment_receipt_emails_include_acceptance_links(): void
    {
        $assetAssignment = $this->createAssetAssignment();
        $assetAssignment->loadMissing(['asset.assetModel', 'asset.category', 'asset.statusLabel', 'assignedBy', 'assignedToUser']);

        $assetHtml = (new AssetAssignmentReceiptMail($assetAssignment))->render();

        $this->assertStringContainsString('/receipts/assets/'.$assetAssignment->id.'/accept/', $assetHtml);
        $this->assertStringNotContainsString('mailto:', $assetHtml);

        $accessoryAssignment = $this->createAccessoryAssignment();
        $accessoryAssignment->loadMissing(['accessory.category', 'accessory.manufacturer', 'accessory.location', 'assignedBy', 'assignedToUser']);

        $accessoryHtml = (new AccessoryAssignmentReceiptMail($accessoryAssignment))->render();

        $this->assertStringContainsString('/receipts/accessories/'.$accessoryAssignment->id.'/accept/', $accessoryHtml);
        $this->assertStringNotContainsString('mailto:', $accessoryHtml);
    }

    public function test_acceptance_link_is_stable_and_token_generation_is_not_audited_as_assignment_update(): void
    {
        $assignment = $this->createAssetAssignment();
        $service = app(ReceiptAcceptanceService::class);

        $firstUrl = $service->assetUrl($assignment);
        $secondUrl = $service->assetUrl($assignment->refresh());

        $this->assertSame($firstUrl, $secondUrl);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'updated',
            'entity_type' => $assignment->getMorphClass(),
            'entity_id' => $assignment->id,
        ]);
    }

    private function createAssetAssignment(): AssetAssignment
    {
        $asset = Asset::factory()->create();
        $assignee = User::factory()->create();
        $actor = User::factory()->create();

        return AssetAssignment::create([
            'asset_id' => $asset->id,
            'assigned_to_type' => AssignmentType::User->value,
            'assigned_to_id' => $assignee->id,
            'assigned_by_user_id' => $actor->id,
            'assigned_at' => now(),
        ]);
    }

    private function createAccessoryAssignment(): AccessoryAssignment
    {
        $accessory = Accessory::factory()->create([
            'quantity_total' => 5,
            'quantity_available' => 3,
        ]);
        $assignee = User::factory()->create();
        $actor = User::factory()->create();

        return AccessoryAssignment::create([
            'accessory_id' => $accessory->id,
            'assigned_to_type' => AssignmentType::User->value,
            'assigned_to_id' => $assignee->id,
            'assigned_by_user_id' => $actor->id,
            'assigned_at' => now(),
            'quantity' => 2,
        ]);
    }
}
