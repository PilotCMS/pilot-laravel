<?php

namespace Pilot\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Pilot\Laravel\Support\ContentQuery content()
 * @method static \Pilot\Laravel\Support\ContentRenderer renderer()
 * @method static \Pilot\Laravel\Support\PreviewUrl previewUrl()
 */
class Pilot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'pilot';
    }
}
