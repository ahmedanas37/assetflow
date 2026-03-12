<?php

namespace Tests\Feature;

use App\Domain\Assets\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_view_asset(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('view', $asset));
    }

    public function test_user_with_permission_can_view_asset(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'view assets',
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($permission);

        $this->assertTrue(Gate::forUser($user)->allows('view', $asset));
    }
}
