<?php

namespace App\Models\Concerns;

use App\Models\Group;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function hasRole(string $role): bool
    {
        // Быстрая проверка через default_role
        if ($this->default_role === $role) {
            return true;
        }

        // Полная проверка через pivot-таблицу
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        if (in_array($this->default_role, $roles)) {
            return true;
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isEditor(): bool
    {
        return $this->hasAnyRole([Role::ADMIN, Role::EDITOR]);
    }

    public function isViewer(): bool
    {
        return true; // все аутентифицированные пользователи могут смотреть
    }

    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $this->roles()->syncWithoutDetaching($role->id);

        // Обновляем default_role если это более высокая роль
        $priority = [Role::VIEWER => 1, Role::EDITOR => 2, Role::ADMIN => 3];
        if (($priority[$roleName] ?? 0) > ($priority[$this->default_role] ?? 0)) {
            $this->update(['default_role' => $roleName]);
        }
    }

    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }
}
