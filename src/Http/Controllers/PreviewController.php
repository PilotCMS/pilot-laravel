<?php

namespace Pilot\Laravel\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Pilot\Laravel\Facades\Pilot;
use Pilot\Laravel\Models\Content;

class PreviewController extends Controller
{
    public function __invoke(int $content): View
    {
        $content = Content::query()
            ->with([
                'space',
                'contentType',
                'blocks' => fn ($query) => $query->whereNull('parent_block_id')->orderBy('position'),
                'blocks.children',
            ])
            ->findOrFail($content);

        $payload = Pilot::renderer()->fromModel($content);

        return Pilot::renderer()->pageView($payload, space: $content->space);
    }
}
