<?php

namespace App\Console\Commands;

use App\Domain\People\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ResetAdminCommand extends Command
{
    protected $signature = 'assetflow:reset-admin
        {--email= : Admin email}
        {--password= : Admin password}
        {--generate-password : Generate a strong random password and print it once}
        {--name= : Admin display name}
        {--username= : Admin username}';

    protected $description = 'Create or reset the admin account.';

    public function handle(): int
    {
        $email = (string) ($this->option('email') ?: config('assetflow.defaults.admin_email'));
        $name = (string) ($this->option('name') ?: config('assetflow.defaults.admin_name'));
        $username = (string) ($this->option('username') ?: config('assetflow.defaults.admin_username'));
        $password = trim((string) $this->option('password'));

        if ((bool) $this->option('generate-password')) {
            $password = Str::password(20);
        } elseif ($password === '') {
            $password = trim((string) config('assetflow.defaults.admin_password'));
        }

        if ($password === '') {
            $this->error('Password is required. Use --password=... or --generate-password.');

            return self::FAILURE;
        }

        if (mb_strlen($password) < 12) {
            $this->error('Password must be at least 12 characters.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'status' => UserStatus::Active->value,
                'password' => Hash::make($password),
            ],
        );

        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $user->syncRoles([$role->name]);

        $permissions = Permission::query()->pluck('name')->all();
        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        $this->info("Admin account ready: {$email}");
        if ((bool) $this->option('generate-password')) {
            $this->warn('Generated admin password (store this now):');
            $this->line($password);
        }

        return self::SUCCESS;
    }
}
