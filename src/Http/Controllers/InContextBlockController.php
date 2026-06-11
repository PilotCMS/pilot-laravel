<?php

namespace Pilot\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Pilot\Laravel\Models\Block;
use Pilot\Laravel\Models\Content;

class InContextBlockController extends Controller
{
    public function show(Request $request, int $block): JsonResponse
    {
        $block = Block::query()->findOrFail($block);

        $this->authorizePreviewRequest($request, $block);

        $block = $block->fresh(['blockType', 'content']) ?? $block;
        $locale = $this->locale($request);

        return response()->json([
            'block' => $this->blockPayload($block, $locale, includeRawData: true),
        ]);
    }

    public function contentSync(Request $request, int $content): JsonResponse
    {
        $content = Content::query()->findOrFail($content);

        $this->authorizeContentPreviewRequest($request, (int) $content->id);

        return response()->json([
            'content' => $this->contentPayload($content),
            'serverTime' => now()->toJSON(),
        ]);
    }

    public function update(Request $request, int $block): JsonResponse
    {
        $block = Block::query()->findOrFail($block);

        $this->authorizePreviewRequest($request, $block);

        $validated = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['nullable'],
            'locale' => ['nullable', 'string', 'max:12'],
        ]);

        $block = $block->fresh(['blockType', 'content']) ?? $block;

        $locale = $this->locale($request);
        $schemaFields = collect($block->blockType?->schema['fields'] ?? [])->keyBy('key');
        $data = $block->data ?? [];

        foreach ($validated['fields'] as $key => $value) {
            if (! $schemaFields->has($key)) {
                continue;
            }

            $field = $schemaFields->get($key);

            if (($field['translatable'] ?? false) === true) {
                $localizedValue = Arr::get($data, $key, []);
                $localizedValue = is_array($localizedValue) ? $localizedValue : [$locale => $localizedValue];
                $localizedValue[$locale] = $value;
                $data[$key] = $localizedValue;

                continue;
            }

            $data[$key] = $this->castFieldValue($field, $value);
        }

        $block->update(['data' => $data]);
        $block->content?->touch();

        return response()->json([
            'updated' => true,
            'block' => $this->blockPayload($block->fresh(['blockType', 'content']), $locale),
        ]);
    }

    protected function authorizePreviewRequest(Request $request, Block $block): void
    {
        if (auth()->check()) {
            return;
        }

        $contentId = $request->integer('pilot_content');
        $blockContentId = (int) Block::query()->whereKey($block->getKey())->value('content_id');

        abort_unless(
            $contentId
                && $blockContentId === $contentId
                && $this->previewSignatureIsValid($contentId, $request),
            403,
            'Invalid or expired Pilot preview link.'
        );
    }

    protected function authorizeContentPreviewRequest(Request $request, int $contentId): void
    {
        if (auth()->check()) {
            return;
        }

        abort_unless(
            $request->integer('pilot_content') === $contentId
                && $this->previewSignatureIsValid($contentId, $request),
            403,
            'Invalid or expired Pilot preview link.'
        );
    }

    protected function previewSignatureIsValid(int $contentId, Request $request): bool
    {
        $expiresAt = $request->integer('pilot_expires');
        $signature = $request->query('pilot_signature');

        if (! is_string($signature) || $signature === '' || $expiresAt < now()->timestamp) {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            "{$contentId}|{$expiresAt}",
            (string) (config('pilot.preview.secret') ?: config('app.key'))
        );

        return hash_equals($expected, $signature);
    }

    /**
     * @return array<string, mixed>
     */
    protected function blockPayload(Block $block, string $locale, bool $includeRawData = false): array
    {
        $payload = [
            'id' => $block->id,
            'type' => $block->type,
            'name' => $block->blockType?->name ?? $block->type,
            'updatedAt' => $block->updated_at?->toJSON(),
            'data' => $this->localizedData($block->data ?? [], $locale),
            'schema' => $block->blockType?->schema ?? ['fields' => []],
            'content' => $block->content ? $this->contentPayload($block->content) : null,
        ];

        if ($includeRawData) {
            $payload['rawData'] = $block->data ?? [];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function contentPayload(Content $content): array
    {
        return [
            'id' => $content->id,
            'name' => $content->name,
            'slug' => $content->slug,
            'updatedAt' => $content->updated_at?->toJSON(),
            'syncKey' => $this->contentSyncKey($content),
        ];
    }

    protected function contentSyncKey(Content $content): string
    {
        $blocks = $content->allBlocks()
            ->get(['id', 'parent_block_id', 'type', 'position', 'data', 'updated_at'])
            ->map(fn (Block $block): array => [
                'id' => $block->id,
                'parent_block_id' => $block->parent_block_id,
                'type' => $block->type,
                'position' => $block->position,
                'data' => $block->data ?? [],
                'updated_at' => $block->updated_at?->toJSON(),
            ])
            ->values()
            ->all();

        return hash('sha256', json_encode([
            'content' => [
                'id' => $content->id,
                'name' => $content->name,
                'slug' => $content->slug,
                'status' => $content->status,
                'workflow_status' => $content->workflow_status,
                'meta' => $content->meta ?? [],
                'updated_at' => $content->updated_at?->toJSON(),
            ],
            'blocks' => $blocks,
        ]) ?: '');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function localizedData(array $data, string $locale): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && array_key_exists($locale, $value)) {
                $data[$key] = $value[$locale];
            }
        }

        return $data;
    }

    protected function locale(Request $request): string
    {
        return $request->string('locale', config('pilot.default_locale', app()->getLocale()))->toString();
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function castFieldValue(array $field, mixed $value): mixed
    {
        return match ($field['type'] ?? 'text') {
            'number' => is_numeric($value) ? $value + 0 : null,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
