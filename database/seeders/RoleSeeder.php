<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::query()->firstOrCreate(
            ['slug' => Role::ADMIN],
            ['name' => 'Administrator'],
        );

        Role::query()->firstOrCreate(
            ['slug' => Role::MEMBER],
            ['name' => 'Member'],
        );
    }
}