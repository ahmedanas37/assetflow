<?php

namespace Tests\Feature;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Services\AccessoryAssignmentService;
use App\Domain\Assets\Enums\AssignmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessoryAssignmentAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_accessory_checkout_audit_log_uses_explicit_actor(): void
    {
        $accessory = Accessory::factory()->create([
            'quantity_total' => 5,
            'quantity_available' => 5,
        ]);
        $assignee = User::factory()->create();
        $actor = User::factory()->create();

        $assignment = app(AccessoryAssignmentService::class)->checkout(
            accessory: $accessory,
            type: AssignmentType::User,
            assignedToId: $assignee->id,
            actor: $actor,
            quantity: 2,
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $actor->id,
            'action' => 'accessory_checked_out',
            'entity_type' => $accessory->getMorphClass(),
            'entity_id' => $accessory->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $actor->id,
            'action' => 'created',
            'entity_type' => $assignment->getMorphClass(),
            'entity_id' => $assignment->id,
        ]);
    }

    public function test_accessory_checkin_audit_log_uses_explicit_actor(): void
    {
        $accessory = Accessory::factory()->create([
            'quantity_total' => 5,
            'quantity_available' => 5,
        ]);
        $assignee = User::factory()->create();
        $checkoutActor = User::factory()->create();
        $checkinActor = User::factory()->create();

        $assignment = app(AccessoryAssignmentService::class)->checkout(
            accessory: $accessory,
            type: AssignmentType::User,
            assignedToId: $assignee->id,
            actor: $checkoutActor,
            quantity: 2,
        );

        app(AccessoryAssignmentService::class)->checkin(
            assignment: $assignment,
            actor: $checkinActor,
            quantity: 1,
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $checkinActor->id,
            'action' => 'accessory_checked_in',
            'entity_type' => $accessory->getMorphClass(),
            'entity_id' => $accessory->id,
        ]);
    }
}
