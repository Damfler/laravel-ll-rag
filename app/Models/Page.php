<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'space_id',
        'parent_id',
        'title',
        'slug',
        'content',
        'content_text',
        'author_id',
        'last_edited_by',
        'is_published',
        'sort_order',
        'depth',
    ];

    protected $casts = [
        'content'      => 'array',
        'is_published' => 'boolean',
    ];

    // ─── Отношения ────────────────────────────────────────────────────────────

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('sort_order');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->latest();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    // ─── Авто-слаг и версия ───────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Page $page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            if ($page->parent_id) {
                $parent = Page::find($page->parent_id);
                $page->depth = $parent ? $parent->depth + 1 : 0;
            }
        });

        static::updated(function (Page $page) {
            // Сохраняем версию при изменении контента или заголовка
            if ($page->wasChanged(['content', 'title'])) {
                $lastVersion = $page->versions()->first();
                $nextNumber  = $lastVersion ? $lastVersion->version_number + 1 : 1;

                PageVersion::create([
                    'page_id'        => $page->id,
                    'author_id'      => $page->last_edited_by ?? $page->author_id,
                    'title'          => $page->title,
                    'content'        => $page->content,
                    'content_text'   => $page->content_text,
                    'version_number' => $nextNumber,
                ]);
            }
        });
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    // ─── Хлебные крошки ───────────────────────────────────────────────────────

    public function breadcrumbs(): array
    {
        $crumbs = [];
        $page   = $this;

        while ($page) {
            array_unshift($crumbs, ['title' => $page->title, 'slug' => $page->slug, 'id' => $page->id]);
            $page = $page->parent;
        }

        return $crumbs;
    }
}
