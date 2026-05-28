<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminRbacSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        Admin::query()->updateOrCreate(
            ['email' => 'admin@themesdesign.test'],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'role' => AdminRole::Admin,
                'manager_id' => null,
            ]
        );

        Admin::query()->updateOrCreate(
            ['email' => 'marketing@themesdesign.test'],
            [
                'name' => 'Marketing User',
                'password' => $password,
                'role' => AdminRole::Marketing,
                'manager_id' => null,
            ]
        );

        $manager = Admin::query()->updateOrCreate(
            ['email' => 'sales.manager@themesdesign.test'],
            [
                'name' => 'Sales Manager',
                'password' => $password,
                'role' => AdminRole::SalesManager,
                'manager_id' => null,
            ]
        );

        Admin::query()->updateOrCreate(
            ['email' => 'sales.employee@themesdesign.test'],
            [
                'name' => 'Sales Employee',
                'password' => $password,
                'role' => AdminRole::SalesEmployee,
                'manager_id' => $manager->id,
            ]
        );
    }
}
