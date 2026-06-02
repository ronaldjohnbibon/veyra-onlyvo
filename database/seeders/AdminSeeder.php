<?php

namespace Database\Seeders;

use App\Shared\Enums\UserType;
use App\Admin\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'admin@example.com')],
            [
                'tenant_id'  => null,
                'name'       => env('SEED_ADMIN_NAME', 'Onlyvo Admin'),
                'first_name' => env('SEED_ADMIN_FIRST_NAME', 'Onlyvo'),
                'last_name'  => env('SEED_ADMIN_LAST_NAME', 'Admin'),
                'phone'      => env('SEED_ADMIN_PHONE'),
                'password'   => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'is_active'  => true,
                'user_type'  => UserType::ADMIN,
            ],
        );
    }
}
