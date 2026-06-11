<?php

namespace Pilot\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pilot\Laravel\Support\PreviewUrl;

class SpacePreviewTarget extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function previewUrlFor(Content $content, ?int $expiresMinutes = null): string
    {
        return app(PreviewUrl::class)->forContent($content, $this->url, $expiresMinutes);
    }
}
