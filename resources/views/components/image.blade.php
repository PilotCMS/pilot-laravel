@props([
    'block' => [],
    'data' => [],
    'children' => [],
    'width' => 1280,
    'height' => 720,
    'widths' => [480, 768, 1024, 1280],
    'sizes' => '(min-width: 1280px) 1280px, 100vw',
])

<div class="image-block">
    @php
        $image = pilotAssetFormatted($data['image'] ?? null, $data);
        $src = pilotImage($data['image'] ?? null, $width, $height);
        $srcset = pilotImageSrcset($data['image'] ?? null, $width, $height, $widths);
        $alt = $data['alt'] ?? '';
        $alt = is_array($alt) ? ($alt['en'] ?? reset($alt) ?: '') : $alt;
    @endphp
    @if($image['url'])
        <img
            src="{{ $src }}"
            @if($srcset !== '') srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
            width="{{ $width }}"
            height="{{ $height }}"
            alt="{{ $alt }}"
            loading="lazy"
            decoding="async"
            class="rounded-xl w-full object-cover aspect-video"
            style="{{ $image['style'] }}"
        >
    @else
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-10 text-center text-gray-400 dark:text-gray-500">
            No image selected
        </div>
    @endif
</div>
