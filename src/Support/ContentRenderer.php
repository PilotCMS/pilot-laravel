<?php

namespace Pilot\Laravel\Support;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ContentRenderer
{
    public function fromModel(Model $content, ?string $locale = null): ContentPayload
    {
        $content->loadMissing([
            'contentType',
            'blocks' => fn ($query) => $query->whereNull('parent_block_id')->orderBy('position'),
            'blocks.children',
        ]);

        return ContentPayload::fromModel($content, $locale ?? config('pilot.default_locale', app()->getLocale()));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fromHeadless(array $payload, ?string $locale = null): ContentPayload
    {
        return ContentPayload::fromArray($payload, $locale ?? config('pilot.default_locale', app()->getLocale()));
    }

    public function pageView(ContentPayload $content, ?string $view = null, mixed $space = null): View
    {
        $view = $this->resolveView($view ?? config('pilot.views.page', 'page'), 'pilot::page');

        return view($view, [
            'content' => $content,
            'space' => $space,
            'blocks' => $this->blockArrays($content->blocks),
        ]);
    }

    public function renderPage(ContentPayload $content, ?string $view = null): HtmlString
    {
        return new HtmlString($this->pageView($content, $view)->render());
    }

    public function renderBlocks(ContentPayload $content, ?string $view = null): HtmlString
    {
        $view = $this->resolveView($view ?? config('pilot.views.blocks', 'blocks'), 'pilot::blocks');

        return new HtmlString(view($view, [
            'blocks' => $this->blockArrays($content->blocks),
        ])->render());
    }

    protected function resolveView(string $preferredView, string $fallbackView): string
    {
        return view()->exists($preferredView) ? $preferredView : $fallbackView;
    }

    /**
     * @param  Collection<int, BlockPayload>  $blocks
     * @return Collection<int, array<string, mixed>>
     */
    protected function blockArrays(Collection $blocks): Collection
    {
        return $blocks->map(fn (BlockPayload $block): array => $block->toArray())->values();
    }
}
