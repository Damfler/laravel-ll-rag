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
        $admin = User::factory()->create([
            'name'         => 'Admin',
            'email'        => 'admin@wiki.local',
            'default_role' => 'admin',
        ]);
        $admin->assignRole('admin');

        // Тестовый редактор
        $editor = User::factory()->create([
            'name'         => 'Editor',
            'email'        => 'editor@wiki.local',
            'default_role' => 'editor',
        ]);
        $editor->assignRole('editor');
    }
}
