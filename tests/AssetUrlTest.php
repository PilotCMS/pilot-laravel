<?php

namespace Pilot\Laravel\Tests;

use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Pilot\Laravel\Support\AssetUrl;

class AssetUrlTest extends TestCase
{
    public function test_it_builds_a_pilot_image_transformation_url(): void
    {
        config(['pilot.assets.base_url' => 'https://cms.example.com']);

        $url = app(AssetUrl::class)->image('/assets/42/hero image.jpg', 800, 450, [
            'fit' => 'cover',
            'format' => 'webp',
            'quality' => 82,
        ]);

        $this->assertSame(
            'https://cms.example.com/assets/42/hero image.jpg?size=800x450&fit=cover&format=webp&quality=82',
            $url,
        );
    }

    public function test_it_preserves_existing_query_parameters_and_fragments(): void
    {
        $url = app(AssetUrl::class)->image('/assets/42/hero.jpg?v=abc#preview', 400, 400);

        $this->assertSame('/assets/42/hero.jpg?v=abc&size=400x400#preview', $url);
    }

    public function test_it_leaves_legacy_and_external_asset_urls_unchanged(): void
    {
        $assets = app(AssetUrl::class);

        $this->assertSame('/storage/assets/hero.jpg', $assets->image('/storage/assets/hero.jpg', 400, 400));
        $this->assertSame('https://images.example.com/hero.jpg', $assets->image('https://images.example.com/hero.jpg', 400, 400));
        $this->assertSame('', $assets->srcset('/storage/assets/hero.jpg', 800, 450));
    }

    public function test_it_builds_a_sorted_deduplicated_responsive_srcset(): void
    {
        $srcset = app(AssetUrl::class)->srcset('/assets/42/hero.jpg', 1600, 900, [1280, 480, 768, 480]);

        $this->assertSame(
            '/assets/42/hero.jpg?size=480x270 480w, '.
            '/assets/42/hero.jpg?size=768x432 768w, '.
            '/assets/42/hero.jpg?size=1280x720 1280w',
            $srcset,
        );
    }

    public function test_it_rejects_invalid_dimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(AssetUrl::class)->image('/assets/42/hero.jpg', 0, 400);
    }

    public function test_the_default_image_component_renders_responsive_variants(): void
    {
        $html = Blade::render(
            '<x-pilot::image :data="$data" />',
            ['data' => [
                'image' => '/assets/42/hero.jpg',
                'alt' => 'Mountain sunrise',
                'image_focal_x' => 30,
                'image_focal_y' => 60,
            ]],
        );

        $this->assertStringContainsString('src="/assets/42/hero.jpg?size=1280x720"', $html);
        $this->assertStringContainsString('srcset="/assets/42/hero.jpg?size=480x270 480w, /assets/42/hero.jpg?size=768x432 768w', $html);
        $this->assertStringContainsString('sizes="(min-width: 1280px) 1280px, 100vw"', $html);
        $this->assertStringContainsString('style="object-position: 30% 60%;"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
    }

    public function test_the_default_hero_component_uses_a_transformed_background(): void
    {
        $html = view('pilot::components.hero', [
            'data' => [
                'background_image' => '/assets/42/hero.jpg',
                'background_image_focal_x' => 25,
                'background_image_focal_y' => 75,
            ],
        ])->render();

        $this->assertStringContainsString('background-image: url(&#039;/assets/42/hero.jpg?size=1600x900&#039;)', $html);
        $this->assertStringContainsString('background-position: 25% 75%;', $html);
    }
}
