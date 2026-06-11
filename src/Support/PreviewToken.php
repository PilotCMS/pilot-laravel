<?php

namespace Pilot\Laravel\Support;

class PreviewToken
{
    public function make(int|string $contentId, int $expiresAt): string
    {
        return hash_hmac('sha256', $this->payload($contentId, $expiresAt), $this->secret());
    }

    public function valid(int|string $contentId, int $expiresAt, ?string $signature): bool
    {
        if (! $signature || $expiresAt < now()->timestamp) {
            return false;
        }

        return hash_equals($this->make($contentId, $expiresAt), $signature);
    }

    protected function payload(int|string $contentId, int $expiresAt): string
    {
        return "{$contentId}|{$expiresAt}";
    }

    protected function secret(): string
    {
        return (string) (config('pilot.preview.secret') ?: config('app.key'));
    }
}
