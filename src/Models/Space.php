<?php

namespace Pilot\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Space extends Model
{
    protected $guarded = [];

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function previewTargets(): HasMany
    {
        return $this->hasMany(SpacePreviewTarget::class)->orderBy('sort_order')->orderBy('name');
    }
}
