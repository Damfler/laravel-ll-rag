<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'author_id',
        'title',
        'content',
        'content_text',
        'change_summary',
        'version_number',
    ];

    protected $casts = [
        'content'    => 'array',
        'created_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
