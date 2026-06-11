<?php

namespace Pilot\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'data' => 'array',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function parentBlock(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'parent_block_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Block::class, 'parent_block_id')->orderBy('position');
    }

    public function blockType(): BelongsTo
    {
        return $this->belongsTo(BlockType::class, 'type', 'key');
    }
}
