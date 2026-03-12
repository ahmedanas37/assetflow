<?php

namespace Database\Seeders;

use App\Domain\People\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('assetflow.defaults.admin_email', 'admin@example.local');
        $name = (string) config('assetflow.defaults.admin_name', 'System Administrator');
        $username = (string) config('assetflow.defaults.admin_username', 'admin');
        $password = trim((string) config('assetflow.defaults.admin_password', ''));

        if ($password === '') {
            throw new RuntimeException('ASSETFLOW_ADMIN_PASSWORD is empty. Set it in .env or use /setup for first-run admin creation.');
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'status' => UserStatus::Active->value,
                'password' => Hash::make($password),
            ],
        );

        $admin->name = $name;
        $admin->username = $username;
        $admin->status = UserStatus::Active->value;
        $admin->password = Hash::make($password);
        $admin->save();

        $role = Role::where('name', 'Admin')->first();

        if ($role) {
            $admin->syncRoles([$role->name]);
        }
    }
}
