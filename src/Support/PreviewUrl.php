<?php

namespace Pilot\Laravel\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

class PreviewUrl
{
    public function __construct(
        protected PreviewToken $tokens,
    ) {}

    public function forContent(Model $content, string $baseUrl, ?int $expiresMinutes = null): string
    {
        $expiresAt = now()->addMinutes($expiresMinutes ?? (int) config('pilot.preview.expires_minutes', 60))->timestamp;
        $contentId = $content->getKey();
        $query = Arr::query([
            'pilot_preview' => 1,
            'pilot_content' => $contentId,
            'pilot_expires' => $expiresAt,
            'pilot_signature' => $this->tokens->make($contentId, $expiresAt),
        ]);

        return rtrim($baseUrl, '/').'/'.trim((string) config('pilot.preview.path', '_pilot/preview'), '/').'/'.$contentId.'?'.$query;
    }

    public function requestIsValid(?int $contentId = null): bool
    {
        $contentId ??= Request::integer('pilot_content');

        return $this->tokens->valid(
            $contentId,
            Request::integer('pilot_expires'),
            Request::query('pilot_signature'),
        );
    }
}
