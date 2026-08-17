@props([
    'block' => [],
    'data' => [],
    'children' => [],
])

<div class="image-block">
    @php
        $image = pilotAssetFormatted($data['image'] ?? null, $data);
        $alt = $data['alt'] ?? '';
        $alt = is_array($alt) ? ($alt['en'] ?? reset($alt) ?: '') : $alt;
    @endphp
    @if($image['url'])
        <img src="{{ $image['url'] }}" alt="{{ $alt }}" class="rounded-xl w-full object-cover aspect-video" style="{{ $image['style'] }}">
    @else
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-10 text-center text-gray-400 dark:text-gray-500">
            No image selected
        </div>
    @endif
</div>
