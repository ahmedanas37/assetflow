<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FirstRunSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_screen_is_available_on_a_fresh_instance(): void
    {
        $response = $this->get('/setup');

        $response->assertOk();
        $response->assertSee('First-Run Setup');
    }

    public function test_setup_creates_initial_admin_and_marks_instance_installed(): void
    {
        $password = Str::password(20);

        $response = $this->post('/setup', [
            'company_name' => 'Acme Corp',
            'brand_color' => '#0A7EFA',
            'admin_name' => 'Acme Admin',
            'admin_email' => 'admin@acme.test',
            'admin_username' => 'acme-admin',
            'admin_password' => $password,
            'admin_password_confirmation' => $password,
        ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('users', [
            'email' => 'admin@acme.test',
            'name' => 'Acme Admin',
        ]);

        $this->assertDatabaseHas('app_settings', [
            'key' => 'branding.company_name',
            'value' => 'Acme Corp',
        ]);

        $this->assertDatabaseHas('app_settings', [
            'key' => 'branding.brand_color',
            'value' => '#0A7EFA',
        ]);

        $this->assertDatabaseHas('app_settings', [
            'key' => 'system.installed_at',
        ]);

        $this->assertTrue(User::query()->where('email', 'admin@acme.test')->firstOrFail()->hasRole('Admin'));
    }

    public function test_setup_route_redirects_when_instance_is_already_installed(): void
    {
        User::factory()->create();

        $this->get('/setup')->assertRedirect('/admin/login');
    }
}
