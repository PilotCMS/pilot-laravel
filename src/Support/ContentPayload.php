<?php

namespace Pilot\Laravel\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class ContentPayload
{
    /**
     * @param  Collection<int, BlockPayload>  $blocks
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string|int|null $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly ?string $contentType,
        public readonly string $status,
        public readonly ?string $publishedAt,
        public readonly array $meta,
        public readonly Collection $blocks,
        public readonly string $source = 'mysql',
    ) {}

    public static function fromModel(Model $content, string $locale): self
    {
        return new self(
            id: $content->id,
            slug: $content->slug,
            name: $content->name,
            contentType: $content->contentType?->key,
            status: $content->status,
            publishedAt: $content->published_at?->toIso8601String(),
            meta: $content->meta ?? [],
            blocks: $content->blocks
                ->sortBy('position')
                ->map(fn (Model $block): BlockPayload => BlockPayload::fromModel($block, $locale, [$content->type]))
                ->values(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload, string $locale): self
    {
        $content = $payload['content'] ?? $payload['story'] ?? $payload;
        $fields = $content['content'] ?? $content;
        $blocks = $fields['body'] ?? $fields['blocks'] ?? $content['body'] ?? $content['blocks'] ?? [];

        return new self(
            id: $content['id'] ?? $content['_uid'] ?? null,
            slug: (string) ($content['slug'] ?? $content['full_slug'] ?? 'preview'),
            name: (string) ($content['name'] ?? $fields['name'] ?? 'Preview'),
            contentType: $content['content_type'] ?? null,
            status: (string) ($content['status'] ?? 'draft'),
            publishedAt: $content['published_at'] ?? null,
            meta: is_array($content['meta'] ?? null) ? $content['meta'] : [],
            blocks: collect($blocks)
                ->map(fn (array|Arrayable $block): BlockPayload => BlockPayload::fromArray(
                    $block instanceof Arrayable ? $block->toArray() : $block,
                    $locale,
                    [(string) ($content['type'] ?? 'page')]
                ))
                ->values(),
            source: 'headless',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'full_slug' => $this->slug,
            'name' => $this->name,
            'content_type' => $this->contentType,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'meta' => $this->meta,
            'source' => $this->source,
            'content' => [
                'component' => 'page',
                'body' => $this->blocks->map(fn (BlockPayload $block): array => $block->toArray())->values()->all(),
            ],
            'body' => $this->blocks->map(fn (BlockPayload $block): array => $block->toArray())->values()->all(),
            'links' => [
                'url' => URL::to($this->slug === config('pilot.home_slug', 'home') ? '/' : '/'.$this->slug),
                'editor' => $this->id ? URL::to('/admin/content/'.$this->id.'/edit') : null,
            ],
        ];
    }
}
