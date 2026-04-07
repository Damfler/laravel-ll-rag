<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Space extends Model
{
    use HasFactory, SoftDeletes;

    // Варианты видимости
    const VISIBILITY_PUBLIC     = 'public';
    const VISIBILITY_RESTRICTED = 'restricted';
    const VISIBILITY_PRIVATE    = 'private';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'visibility',
        'owner_id',
    ];

    // ─── Отношения ────────────────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function rootPages(): HasMany
    {
        return $this->hasMany(Page::class)
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    // ─── Роутинг по slug ─────────────────────────────────────────────────────

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ─── Авто-слаг ────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Space $space) {
            if (empty($space->slug)) {
                $space->slug = Str::slug($space->name);
            }
        });
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $userGroupIds = $user->groups()->pluck('groups.id');

        return $query->where(function ($q) use ($user, $userGroupIds) {
            // Публичный — видят все
            $q->where('visibility', self::VISIBILITY_PUBLIC)
            // Приватный — только владелец
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('visibility', self::VISIBILITY_PRIVATE)
                     ->where('owner_id', $user->id);
              })
            // Ограниченный — участники назначенных групп или владелец
              ->orWhere(function ($q2) use ($user, $userGroupIds) {
                  $q2->where('visibility', self::VISIBILITY_RESTRICTED)
                     ->where(function ($q3) use ($user, $userGroupIds) {
                         $q3->where('owner_id', $user->id)
                            ->orWhereHas('groups', fn ($g) => $g->whereIn('groups.id', $userGroupIds));
                     });
              });
        });
    }
}
