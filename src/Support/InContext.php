<?php

namespace Pilot\Laravel\Support;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class InContext
{
    public function enabled(): bool
    {
        if (! config('pilot.in_context.enabled', true)) {
            return false;
        }

        if (Request::has('pilot_in_context')) {
            return Request::boolean('pilot_in_context');
        }

        return Request::boolean('pilot_preview')
            || Request::boolean('pilot_editor')
            || Request::boolean('editor')
            || auth()->check()
            || Str::contains((string) Request::header('Referer'), '/admin/content/');
    }

    public function field(array $block, string $field, string $type = 'text'): HtmlString
    {
        if (! $this->enabled()) {
            return new HtmlString('');
        }

        $blockId = $block['id'] ?? $block['_uid'] ?? null;

        if (! $blockId) {
            return new HtmlString('');
        }

        return new HtmlString(collect([
            'data-pilot-editable' => 'field',
            'data-pilot-block-id' => $blockId,
            'data-pilot-field' => $field,
            'data-pilot-field-type' => $type,
        ])->map(fn (mixed $value, string $key): string => sprintf('%s="%s"', $key, e((string) $value)))->implode(' '));
    }
}
