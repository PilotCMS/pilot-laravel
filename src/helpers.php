<?php

use Pilot\Laravel\Support\AssetUrl;

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
