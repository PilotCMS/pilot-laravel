@props([
    'block' => [],
    'data' => [],
    'children' => [],
])

@php
    $formatValue = function (mixed $value): string {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    };
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">{{ $block['type'] ?? $block['component'] ?? $block->type ?? 'block' }}</h3>
        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">Fallback preview</span>
    </div>
    <div class="grid gap-2 text-sm text-slate-700">
        @foreach(($data ?? []) as $key => $value)
            <div class="rounded border border-slate-100 bg-slate-50 px-2.5 py-1.5">
                <span class="mb-1 block font-medium text-slate-500">{{ $key }}:</span>
                <pre class="overflow-x-auto whitespace-pre-wrap break-words font-mono text-xs leading-5 text-slate-700">{{ $formatValue($value) }}</pre>
            </div>
        @endforeach
    </div>
</div>
