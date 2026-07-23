<?php

namespace Pilot\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pilot\Laravel\Support\ContentPayload;
use Pilot\Laravel\Support\ContentRenderer;

class Content extends Model
{
    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'review_requested_at' => 'datetime',
        'meta' => 'array',
    ];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->whereNull('parent_block_id')->orderBy('position');
    }

    public function allBlocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('position');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    public function toPayload(?string $locale = null): ContentPayload
    {
        return app(ContentRenderer::class)->fromModel($this, $locale);
    }

    public function bang(?string $locale = null): ContentPayload
    {
        return $this->toPayload($locale);
    }
}
