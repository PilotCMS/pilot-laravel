<?php

namespace Pilot\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pilot\Laravel\Support\PreviewUrl;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidPilotPreview
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app(PreviewUrl::class)->requestIsValid($request->route('content')), 403, 'Invalid or expired Pilot preview link.');

        app()->instance('pilot.previewing', true);

        return $next($request);
    }
}
