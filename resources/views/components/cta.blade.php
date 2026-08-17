@props([
    'block' => [],
    'data' => [],
])

@php
    $title = $data['title'] ?? 'Call to action';
    $title = is_array($title) ? ($title['en'] ?? reset($title) ?: 'Call to action') : $title;
    $buttonText = $data['button_text'] ?? 'Learn more';
    $buttonText = is_array($buttonText) ? ($buttonText['en'] ?? reset($buttonText) ?: 'Learn more') : $buttonText;
    $buttonUrl = $data['button_url'] ?? '#';
    $buttonUrl = is_array($buttonUrl) ? ($buttonUrl['en'] ?? reset($buttonUrl) ?: '#') : $buttonUrl;
    $style = $data['style'] ?? 'primary';
    $pilotBlock = is_array($block) ? $block : ['id' => $block->id ?? null, '_uid' => $block->id ?? null];
    $inContext = app(\Pilot\Laravel\Support\InContext::class);
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Call to action</p>
            <h3 class="mt-1 text-xl font-semibold text-slate-900" {!! $inContext->field($pilotBlock, 'title') !!}>{{ $title }}</h3>
        </div>
        <a
            href="{{ $buttonUrl }}"
            class="inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors {{ $style === 'outline' ? 'border border-slate-300 text-slate-700 hover:bg-slate-50' : ($style === 'secondary' ? 'bg-slate-100 text-slate-900 hover:bg-slate-200' : 'bg-accent text-on-accent hover:bg-accent-hover') }}"
            {!! $inContext->field($pilotBlock, 'button_text') !!}
        >
            {{ $buttonText }}
        </a>
    </div>
</div>
