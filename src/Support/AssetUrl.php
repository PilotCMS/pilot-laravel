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

    /**
     * Build a focal-point-aware Pilot image transformation URL.
     *
     * Legacy storage paths and third-party image URLs are returned unchanged.
     *
     * @param  array{fit?: string, format?: string, quality?: int}  $options
     */
    public function image(mixed $path, int $width, int $height, array $options = []): string
    {
        $url = $this->url($path);

        if ($url === '' || ! $this->isTransformableImageUrl($url)) {
            return $url;
        }

        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException('Pilot image width and height must be positive integers.');
        }

        $query = ['size' => "{$width}x{$height}"];

        foreach (['fit', 'format', 'quality'] as $option) {
            if (isset($options[$option])) {
                $query[$option] = $options[$option];
            }
        }

        return $this->withQuery($url, $query);
    }

    /**
     * Build a responsive srcset using the aspect ratio of the requested size.
     *
     * @param  array<int, int>  $widths
     * @param  array{fit?: string, format?: string, quality?: int}  $options
     */
    public function srcset(
        mixed $path,
        int $width,
        int $height,
        array $widths = [480, 768, 1024, 1280],
        array $options = [],
    ): string {
        $url = $this->url($path);

        if ($url === '' || ! $this->isTransformableImageUrl($url)) {
            return '';
        }

        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException('Pilot image width and height must be positive integers.');
        }

        $ratio = $height / $width;
        $widths = array_values(array_unique(array_filter(
            array_map('intval', $widths),
            fn (int $candidate): bool => $candidate > 0,
        )));
        sort($widths);

        return implode(', ', array_map(
            fn (int $candidate): string => $this->image(
                $path,
                $candidate,
                max(1, (int) round($candidate * $ratio)),
                $options,
            )." {$candidate}w",
            $widths,
        ));
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

    protected function isTransformableImageUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && preg_match('#/assets/[1-9][0-9]*/[^/]+$#', $path) === 1;
    }

    /** @param array<string, mixed> $parameters */
    protected function withQuery(string $url, array $parameters): string
    {
        [$withoutFragment, $fragment] = array_pad(explode('#', $url, 2), 2, null);
        [$path, $queryString] = array_pad(explode('?', $withoutFragment, 2), 2, '');
        parse_str($queryString, $query);
        $query = [...$query, ...$parameters];
        $result = $path.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $fragment === null ? $result : $result.'#'.$fragment;
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
