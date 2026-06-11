<?php

namespace Pilot\Laravel\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class BlockPayload
{
    /**
     * @param  Collection<int, BlockPayload>  $children
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $componentPath
     */
    public function __construct(
        public readonly string|int|null $id,
        public readonly string $component,
        public readonly array $data = [],
        public readonly Collection $children = new Collection,
        public readonly ?int $contentId = null,
        public readonly array $componentPath = [],
    ) {}

    public static function fromModel(Model $block, string $locale, array $componentPath = []): self
    {
        $componentPath[] = $block->type;

        return new self(
            id: $block->id,
            component: $block->type,
            data: self::localizedData($block->data ?? [], $locale),
            children: $block->children
                ->sortBy('position')
                ->map(fn (Model $child): self => self::fromModel($child, $locale, $componentPath))
                ->values(),
            contentId: $block->content_id,
            componentPath: $componentPath,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload, string $locale, array $componentPath = []): self
    {
        $component = (string) ($payload['component'] ?? $payload['type'] ?? 'unknown');
        $componentPath[] = $component;
        $data = $payload['data'] ?? Arr::except($payload, [
            '_uid',
            'id',
            'component',
            'type',
            'children',
            'body',
            'blocks',
        ]);

        $children = collect($payload['children'] ?? $payload['body'] ?? $payload['blocks'] ?? [])
            ->map(fn (array|Arrayable $child): self => self::fromArray(
                $child instanceof Arrayable ? $child->toArray() : $child,
                $locale,
                $componentPath
            ))
            ->values();

        return new self(
            id: $payload['_uid'] ?? $payload['id'] ?? null,
            component: $component,
            data: self::localizedData(is_array($data) ? $data : [], $locale),
            children: $children,
            contentId: isset($payload['content_id']) ? (int) $payload['content_id'] : null,
            componentPath: $componentPath,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            '_uid' => $this->id,
            'id' => $this->id,
            'type' => $this->component,
            'component' => $this->component,
            'data' => $this->data,
            'children' => $this->children->map(fn (self $child): array => $child->toArray())->values()->all(),
            'editor' => [
                'enabled' => $this->shouldRenderEditorLinks(),
                'attributes' => $this->editorAttributes()->toHtml(),
                'comment' => $this->editorComment()->toHtml(),
            ],
        ];
    }

    public function editorAttributes(): HtmlString
    {
        if (! $this->shouldRenderEditorLinks()) {
            return new HtmlString('');
        }

        return new HtmlString(collect([
            'data-pilot-editable' => 'block',
            'data-pilot-block-id' => $this->id,
            'data-pilot-component' => $this->component,
            'data-pilot-component-path' => implode('/', $this->componentPath),
        ])->map(fn (mixed $value, string $key): string => sprintf('%s="%s"', $key, e((string) $value)))->implode(' '));
    }

    public function editorComment(): HtmlString
    {
        if (! $this->shouldRenderEditorLinks()) {
            return new HtmlString('');
        }

        return new HtmlString(sprintf('<!-- pilot:block:%s:%s -->', e((string) $this->id), e($this->component)));
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->data);
    }

    public function field(string $field, mixed $default = null): mixed
    {
        return data_get($this->data, $field, $default);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function localizedData(array $data, string $locale): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && array_key_exists($locale, $value)) {
                $data[$key] = $value[$locale];
            }
        }

        return $data;
    }

    protected function shouldRenderEditorLinks(): bool
    {
        if (! config('pilot.editor_bridge.enabled', true)) {
            return false;
        }

        return Request::boolean('pilot_editor')
            || Request::boolean('pilot_preview')
            || Request::boolean('editor')
            || auth()->check()
            || Str::contains((string) Request::header('Referer'), '/admin/content/');
    }
}
