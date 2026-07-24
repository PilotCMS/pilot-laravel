# Pilot Laravel

Laravel frontend adapter for reading and rendering Pilot CMS content.

This package is intended for Laravel applications that consume Pilot-managed content from a shared database or a Pilot-compatible headless payload. It provides Eloquent models, a content query API, Blade renderers, preview routes, in-context editing routes, and asset URL helpers.

## Requirements

- PHP 8.0.2 or higher
- Laravel 9.2, 10, 11, or 12

## Installation

Pilot Laravel is an adapter, so it does not install database tables, register a catch-all page route, or replace your application's existing rendering structure. You can use only the query API, only the payload renderer, or the complete Blade and preview integration.

### 1. Install the package

Until a tagged release is available, add the GitHub repository to your application's `composer.json`:

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

When developing the package and application together, you can use a local Composer path repository instead:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../pilot-laravel",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

Adjust the relative path to match your project layout, then run:

```bash
composer require pilot/laravel:@dev
```

### 2. Choose how the application receives content

For the query API, the package reads Pilot's `spaces`, `contents`, `content_types`, `blocks`, and `block_types` tables through Laravel's default database connection. Configure that connection with Laravel's normal `DB_*` environment variables. The package does not run migrations or modify your database schema.

If your application already retrieves headless content through an API client, you do not need the shared database query API. Pass the resulting array directly to the renderer:

```php
$payload = Pilot::renderer()->fromHeadless($responseData);
```

The package does not prescribe the HTTP client, caching strategy, routes, controllers, or application architecture used to retrieve that payload.

### 3. Configure Pilot defaults

Publishing configuration is optional. The package can be configured entirely with environment variables, or you can publish `config/pilot.php` when you need application-specific settings:

```bash
php artisan vendor:publish --tag=pilot-config
```

For a shared-database integration, the typical minimum is:

```dotenv
PILOT_DEFAULT_SPACE=website
PILOT_CMS_URL=https://cms.example.com
PILOT_ASSET_URL=https://cms.example.com
```

`PILOT_DEFAULT_SPACE` is used automatically by content queries and can be overridden per query with `inSpace()`.

### 4. Integrate Pilot into the existing application

Use the facade from an existing route, controller, service, Livewire component, or other application boundary:

```php
use Pilot\Laravel\Facades\Pilot;

$content = Pilot::content()
    ->slug('home')
    ->whenPreviewing()
    ->withBlocks()
    ->firstOrFail()
    ->toPayload();

return view('content', compact('content'));
```

You remain in control of route patterns, middleware, caching, error handling, and which application view renders the content.

Render the payload's blocks from your application view with the package component:

```blade
<x-pilot::blocks :content="$content" />
```

The equivalent helper syntax is also available:

```blade
{{ pilotBlocks($content) }}
```

### 5. Enable previews when needed

Preview and in-context editing are optional. In Pilot, add the frontend application's base URL as a preview target for the space, then copy the displayed secret into the frontend application:

```dotenv
PILOT_PREVIEW_SECRET=copy-the-secret-from-pilot
```

The same secret must be configured in Pilot and the frontend or signed preview URLs will return `403`. After changing it in a cached production environment, run:

```bash
php artisan optimize:clear
```

## Configuration Reference

All available environment variables:

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

## Querying Content

Use the `Pilot` facade or the `pilot` container binding to query content.

```php
use Pilot\Laravel\Facades\Pilot;

$content = Pilot::content()
    ->slug('home')
    ->published()
    ->withBlocks()
    ->firstOrFail();
```

Queries use `PILOT_DEFAULT_SPACE` (`website` by default). Override it for an individual query with `inSpace()`:

```php
$content = Pilot::content()
    ->inSpace('marketing')
    ->slug('home')
    ->published()
    ->withBlocks()
    ->firstOrFail();
```

Available query methods:

- `inSpace(string|int|Space $space)`
- `space(string|int|Space $space)` (backward-compatible alias)
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

Convert an Eloquent content model to a presentation-ready payload, then pass it to any application view:

```php
$content = Pilot::content()
    ->slug('home')
    ->whenPreviewing()
    ->withBlocks()
    ->firstOrFail()
    ->toPayload();

return view('content', compact('content'));
```

`toPayload()` accepts an optional locale override:

```php
$content = $contentModel->toPayload('es');
```

`bang()` is available as a shorter alias and accepts the same optional locale:

```php
$content = $contentModel->bang();
$content = $contentModel->bang('es');
```

You can also use the package's bundled page view:

```php
return Pilot::renderer()->pageView($content);
```

Render blocks only:

```php
echo Pilot::renderer()->renderBlocks($content);
```

You may also render headless API payloads:

```php
$payload = Pilot::renderer()->fromHeadless($data);

return Pilot::renderer()->pageView($payload);
```

By default, the package looks for app-level views named `page` and `blocks`. If they do not exist, it falls back to the package views.

## Views

Render all blocks from a `ContentPayload` with the namespaced package component:

```blade
<x-pilot::blocks :content="$content" />
```

Or use the rendering helper when component syntax is not convenient:

```blade
{{ pilotBlocks($content) }}
```

Both options return the same rendered block markup. `pilotBlocks()` returns an `HtmlString`, so Blade's regular escaped echo syntax is safe and no `{!! !!}` output is necessary.

The default block renderer maps a Pilot component key to an application Blade component. For example, a `feature-grid` block resolves to:

```text
resources/views/components/feature-grid.blade.php
```

The component receives the complete `$block` array together with `$data` and `$children`:

```blade
<section>
    <h2>{{ $data['heading'] ?? '' }}</h2>
</section>
```

This convention does not require publishing package views. Existing applications can instead provide their own page and blocks views or publish the package views as a starting point.

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
