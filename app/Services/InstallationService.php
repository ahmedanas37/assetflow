<?php

namespace App\Services;

use App\Domain\People\Enums\UserStatus;
use App\Domain\Settings\Models\AppSetting;
use App\Models\User;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\PortalSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StatusLabelsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

class InstallationService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'status_labels',
        'categories',
        'manufacturers',
        'asset_models',
        'vendors',
        'locations',
        'departments',
        'app_settings',
    ];

    public function databaseReady(): bool
    {
        try {
            foreach (self::REQUIRED_TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function isInstalled(): bool
    {
        if (! $this->databaseReady()) {
            return false;
        }

        try {
            $installedAt = AppSetting::query()
                ->where('key', 'system.installed_at')
                ->value('value');

            if (is_string($installedAt) && trim($installedAt) !== '') {
                return true;
            }

            return User::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{success: bool, output: string}
     */
    public function initializeDatabase(): array
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            return [
                'success' => true,
                'output' => trim((string) Artisan::output()),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'output' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array{
     *     company_name: string,
     *     brand_color?: string|null,
     *     admin_name: string,
     *     admin_email: string,
     *     admin_username?: string|null,
     *     admin_password: string
     * }  $data
     */
    public function install(array $data): User
    {
        if (! $this->databaseReady()) {
            throw new RuntimeException('Database is not ready. Run migrations first.');
        }

        if ($this->isInstalled()) {
            throw new RuntimeException('This instance is already installed.');
        }

        return DB::transaction(function () use ($data): User {
            $this->runInitialSeeders();

            $admin = User::query()->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'username' => $data['admin_username'] ?: null,
                'status' => UserStatus::Active->value,
                'password' => Hash::make($data['admin_password']),
            ]);

            $role = Role::query()->firstOrCreate([
                'name' => 'Admin',
                'guard_name' => 'web',
            ]);

            $admin->syncRoles([$role->name]);

            app(PortalSettings::class)->setMany([
                'branding.company_name' => $data['company_name'],
                'branding.brand_color' => $this->sanitizeColor($data['brand_color'] ?? null),
                'system.installed_at' => now()->toIso8601String(),
                'system.installed_by' => $admin->email,
            ]);

            return $admin;
        });
    }

    private function runInitialSeeders(): void
    {
        app(RolesAndPermissionsSeeder::class)->run();
        app(StatusLabelsSeeder::class)->run();
        app(CoreDataSeeder::class)->run();
        app(PortalSettingsSeeder::class)->run();
    }

    private function sanitizeColor(?string $color): string
    {
        $value = is_string($color) ? trim($color) : '';

        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1) {
            return strtoupper($value);
        }

        return '#1459D9';
    }
}
