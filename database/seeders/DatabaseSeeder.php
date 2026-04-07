<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Сначала роли
        $this->call(RoleSeeder::class);

        // Тестовый администратор
        $admin = User::firstOrCreate(
            ['email' => 'admin@wiki.local'],
            [
                'name'              => 'Admin',
                'email_verified_at' => now(),
                'password'          => bcrypt('password'),
                'default_role'      => 'admin',
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Тестовый редактор
        $editor = User::firstOrCreate(
            ['email' => 'editor@wiki.local'],
            [
                'name'              => 'Editor',
                'email_verified_at' => now(),
                'password'          => bcrypt('password'),
                'default_role'      => 'editor',
            ]
        );
        if (!$editor->hasRole('editor')) {
            $editor->assignRole('editor');
        }
    }
}
