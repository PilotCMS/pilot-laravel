<?php

namespace Pilot\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class BlockType extends Model
{
    protected $guarded = [];

    protected $casts = [
        'schema' => 'array',
        'is_global' => 'boolean',
    ];
}
