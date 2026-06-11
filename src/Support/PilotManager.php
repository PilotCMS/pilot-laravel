<?php

namespace Pilot\Laravel\Support;

use Illuminate\Contracts\Foundation\Application;

class PilotManager
{
    public function __construct(
        protected Application $app,
    ) {}

    public function content(): ContentQuery
    {
        return new ContentQuery;
    }

    public function renderer(): ContentRenderer
    {
        return $this->app->make(ContentRenderer::class);
    }

    public function previewUrl(): PreviewUrl
    {
        return $this->app->make(PreviewUrl::class);
    }
}
