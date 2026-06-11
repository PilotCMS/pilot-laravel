<?php

namespace Pilot\Laravel\Support;

use Illuminate\Database\Eloquent\Builder;
use Pilot\Laravel\Models\Content;
use Pilot\Laravel\Models\Space;

class ContentQuery
{
    protected Builder $query;

    public function __construct()
    {
        $this->query = Content::query()->where('type', 'page');
    }

    public function space(string|int|Space $space): self
    {
        if ($space instanceof Space) {
            $this->query->where('space_id', $space->id);

            return $this;
        }

        if (is_int($space)) {
            $this->query->where('space_id', $space);

            return $this;
        }

        $this->query->whereHas('space', fn (Builder $query) => $query->where('slug', $space));

        return $this;
    }

    public function slug(string $slug): self
    {
        $this->query->where('slug', trim($slug, '/'));

        return $this;
    }

    public function type(string $contentType): self
    {
        $this->query->whereHas('contentType', fn (Builder $query) => $query->where('key', $contentType));

        return $this;
    }

    public function published(): self
    {
        $this->query
            ->where('status', 'published')
            ->whereNotNull('published_at');

        return $this;
    }

    public function draft(): self
    {
        return $this;
    }

    public function whenPreviewing(): self
    {
        if (app()->bound('pilot.previewing') && app('pilot.previewing') === true) {
            return $this;
        }

        return $this->published();
    }

    public function withBlocks(): self
    {
        $this->query->with([
            'contentType',
            'blocks' => fn ($query) => $query->whereNull('parent_block_id')->orderBy('position'),
            'blocks.children',
        ]);

        return $this;
    }

    public function builder(): Builder
    {
        return $this->query;
    }

    public function first(): ?Content
    {
        return $this->query->first();
    }

    public function firstOrFail(): Content
    {
        return $this->query->firstOrFail();
    }
}
