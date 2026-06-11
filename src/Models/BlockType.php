<?php

namespace Pilot\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class BlockType extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'is_global' => 'boolean',
        ];
    }
}
