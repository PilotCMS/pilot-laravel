# Pilot Laravel

Laravel frontend adapter for reading and rendering Pilot CMS content.

This package is intended for Laravel applications that consume Pilot-managed content from a shared database or a Pilot-compatible headless payload. It provides Eloquent models, a content query API, Blade renderers, preview routes, in-context editing routes, and asset URL helpers.

## Requirements

- PHP 8.0.2 or higher
- Laravel 9.2, 10, 11, or 12

## Installation

Add the GitHub repository to your application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/PilotCMS/pilot-laravel.git"
        }
    ]
}
```

Require the package:

```bash
composer require pilot/laravel:dev-main
```

Laravel will auto-discover the service provider and facade.

## Configuration

Publish the config file when you need to customize defaults:

```bash
php artisan vendor:publish --tag=pilot-config
```

Available environment variables:

```dotenv
PILOT_DEFAULT_SPACE=website
PILOT_HOME_SLUG=home
PILOT_DEFAULT_LOCALE=en
PILOT_CMS_URL=https://cms.example.com
PILOT_ASSET_URL=https://cms.example.com

PILOT_PAGE_VIEW=page
PILOT_BLOCKS_VIEW=blocks

PILOT_EDITOR_BRIDGE_ENABLED=true
PILOT_LIVE_PREVIEW_ENABLED=true
PILOT_LIVE_PREVIEW_ROOT="[data-pilot-live-root]"

PILOT_IN_CONTEXT_ENABLED=true
PILOT_IN_CONTEXT_PATH=_pilot/in-context

PILOT_PREVIEW_ROUTES=true
PILOT_PREVIEW_PATH=_pilot/preview
PILOT_PREVIEW_SECRET=
PILOT_PREVIEW_EXPIRES_MINUTES=60
```

Set `PILOT_PREVIEW_SECRET` in any environment that uses signed preview URLs.

## Querying Content

Use the `Pilot` facade or the `pilot` container binding to query content.

```php
use Pilot\Laravel\Facades\Pilot;

$content = Pilot::content()
    ->space('website')
    ->slug('home')
    ->published()
    ->withBlocks()
    ->firstOrFail();
```

Available query methods:

- `space(string|int|Space $space)`
- `slug(string $slug)`
- `type(string $contentType)`
- `published()`
- `draft()`
- `whenPreviewing()`
- `withBlocks()`
- `builder()`
- `first()`
- `firstOrFail()`

## Rendering

Convert an Eloquent content model to a renderable payload:

```php
use Pilot\Laravel\Facades\Pilot;

$payload = Pilot::renderer()->fromModel($content);
```

Render a full page:

```php
return Pilot::renderer()->pageView($payload);
```

Render blocks only:

```php
echo Pilot::renderer()->renderBlocks($payload);
```

You may also render headless API payloads:

```php
$payload = Pilot::renderer()->fromHeadless($data);

return Pilot::renderer()->pageView($payload);
```

By default, the package looks for app-level views named `page` and `blocks`. If they do not exist, it falls back to the package views.

## Views

Publish package views if you want to customize the default renderers:

```bash
php artisan vendor:publish --tag=pilot-views
```

Published views are copied to:

```text
resources/views/vendor/pilot
```

You can also point the renderer at custom app views with:

```dotenv
PILOT_PAGE_VIEW=themes.marketing.page
PILOT_BLOCKS_VIEW=themes.marketing.blocks
```

## Asset Helpers

The package includes helpers for building asset URLs from Pilot asset paths:

```php
pilotAsset($path);
pilotAssets($path);
```

For images with focal-point metadata:

```php
$image = pilotAssetFormatted($path, $blockData);

echo $image['src'];
echo $image['object_position_style'];
echo $image['background_image_style'];
```

The asset base URL comes from `PILOT_ASSET_URL`, falling back to `PILOT_CMS_URL`.

## Preview URLs

Generate a signed preview URL for a content model:

```php
use Pilot\Laravel\Facades\Pilot;

$url = Pilot::previewUrl()->forContent($content, config('app.url'));
```

The package registers this preview route by default:

```text
GET /_pilot/preview/{content}
```

You can disable package preview routes with:

```dotenv
PILOT_PREVIEW_ROUTES=false
```

## In-Context Editing

When enabled, the package registers in-context editing routes under `/_pilot/in-context`:

```text
GET   /_pilot/in-context/contents/{content}/sync
GET   /_pilot/in-context/blocks/{block}
PATCH /_pilot/in-context/blocks/{block}
```

Change the route prefix with:

```dotenv
PILOT_IN_CONTEXT_PATH=_pilot/in-context
```

## Versioning

This package is currently installed from `dev-main`. Before production use in multiple applications, tag a release and require that version:

```bash
composer require pilot/laravel:^0.1
```

## License

MIT
