<?php

use Illuminate\Support\HtmlString;
use Pilot\Laravel\Support\AssetUrl;
use Pilot\Laravel\Support\ContentPayload;
use Pilot\Laravel\Support\ContentRenderer;

if (! function_exists('pilotBlocks')) {
    function pilotBlocks(ContentPayload $content, ?string $view = null): HtmlString
    {
        return app(ContentRenderer::class)->renderBlocks($content, $view);
    }
}

if (! function_exists('pilotAsset')) {
    function pilotAsset(mixed $path): string
    {
        return app(AssetUrl::class)->url($path);
    }
}

if (! function_exists('pilotAssets')) {
    function pilotAssets(mixed $path): string
    {
        return pilotAsset($path);
    }
}

if (! function_exists('pilotImage')) {
    /** @param array{fit?: string, format?: string, quality?: int} $options */
    function pilotImage(mixed $path, int $width, int $height, array $options = []): string
    {
        return app(AssetUrl::class)->image($path, $width, $height, $options);
    }
}

if (! function_exists('pilotImageSrcset')) {
    /**
     * @param  array<int, int>  $widths
     * @param  array{fit?: string, format?: string, quality?: int}  $options
     */
    function pilotImageSrcset(
        mixed $path,
        int $width,
        int $height,
        array $widths = [480, 768, 1024, 1280],
        array $options = [],
    ): string {
        return app(AssetUrl::class)->srcset($path, $width, $height, $widths, $options);
    }
}

if (! function_exists('pilotAssetFormatted')) {
    /**
     * @param  array<string, mixed>  $data
     * @return array{url: string, src: string, focal_x: float, focal_y: float, position: string, object_position: string, style: string, object_position_style: string, background_position_style: string, background_image_style: string}
     */
    function pilotAssetFormatted(mixed $path, array $data = [], string $field = 'image'): array
    {
        return app(AssetUrl::class)->formatted($path, $data, $field);
    }
}

if (! function_exists('pilotAssetsFormatted')) {
    /**
     * @param  array<string, mixed>  $data
     * @return array{url: string, src: string, focal_x: float, focal_y: float, position: string, object_position: string, style: string, object_position_style: string, background_position_style: string, background_image_style: string}
     */
    function pilotAssetsFormatted(mixed $path, array $data = [], string $field = 'image'): array
    {
        return pilotAssetFormatted($path, $data, $field);
    }
}
