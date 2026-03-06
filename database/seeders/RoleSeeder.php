<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'        => Role::ADMIN,
                'label'       => 'Администратор',
                'description' => 'Полный доступ: управление пользователями, всеми разделами и страницами.',
            ],
            [
                'name'        => Role::EDITOR,
                'label'       => 'Редактор',
                'description' => 'Может создавать и редактировать страницы в доступных разделах.',
            ],
            [
                'name'        => Role::VIEWER,
                'label'       => 'Читатель',
                'description' => 'Только чтение. Роль по умолчанию для новых пользователей.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
