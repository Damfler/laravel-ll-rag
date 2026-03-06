<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Space extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'is_public',
        'owner_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
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

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('is_public', true)
            ->orWhere('owner_id', $user->id);
    }
}
