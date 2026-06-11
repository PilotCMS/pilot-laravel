<?php

namespace Pilot\Laravel\Support;

class AssetUrl
{
    public function url(mixed $path): string
    {
        $path = $this->normalizePath($path);

        if ($path === null || $path === '') {
            return '';
        }

        if ($this->isAbsoluteUrl($path)) {
            return $path;
        }

        $baseUrl = $this->baseUrl();

        if ($baseUrl === null) {
            return $path;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{url: string, src: string, focal_x: float, focal_y: float, position: string, object_position: string, style: string, object_position_style: string, background_position_style: string, background_image_style: string}
     */
    public function formatted(mixed $path, array $data = [], string $field = 'image'): array
    {
        $focalX = $this->focalValue($data[$field.'_focal_x'] ?? null);
        $focalY = $this->focalValue($data[$field.'_focal_y'] ?? null);
        $position = $focalX.'% '.$focalY.'%';
        $url = $this->url($path);

        return [
            'url' => $url,
            'src' => $url,
            'focal_x' => $focalX,
            'focal_y' => $focalY,
            'position' => $position,
            'object_position' => $position,
            'style' => 'object-position: '.$position.';',
            'object_position_style' => 'object-position: '.$position.';',
            'background_position_style' => 'background-position: '.$position.';',
            'background_image_style' => $url !== '' ? "background-image: url('".$url."'); background-position: ".$position.';' : '',
        ];
    }

    public function baseUrl(): ?string
    {
        $baseUrl = config('pilot.assets.base_url');

        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            return null;
        }

        return trim($baseUrl);
    }

    protected function isAbsoluteUrl(string $path): bool
    {
        return preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/\/|#)/i', $path) === 1;
    }

    protected function focalValue(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 50.0;
        }

        return max(0.0, min(100.0, (float) $value));
    }

    protected function normalizePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $locale = config('pilot.default_locale', app()->getLocale());
            $path = $path[$locale] ?? reset($path);
        }

        if ($path === null || $path === '') {
            return null;
        }

        return (string) $path;
    }
}
