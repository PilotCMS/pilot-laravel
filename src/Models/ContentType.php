<?php

namespace Pilot\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentType extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'allowed_blocks' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
