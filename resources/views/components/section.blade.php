@props([
    'block' => [],
    'data' => [],
    'children' => [],
])

<div class="section-block rounded-lg" style="background-color: {{ $data['background_color'] ?? '#ffffff' }}; padding: {{ $data['padding'] ?? 24 }}px;">
    <div class="space-y-6">
        <p class="text-gray-500">Section container (blocks can be nested here)</p>
    </div>
</div>
