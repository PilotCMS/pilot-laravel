@props([
    'block' => [],
    'data' => [],
])

<div class="hero-section bg-gradient-to-r from-blue-600 to-purple-600 text-white py-20 px-8 md:px-12 rounded-xl">
    @php
        $backgroundImage = pilotAssetFormatted($data['background_image'] ?? null, $data, 'background_image');
        $title = $data['title'] ?? 'Hero Title';
        $title = is_array($title) ? ($title['en'] ?? reset($title) ?: 'Hero Title') : $title;
        $subtitle = $data['subtitle'] ?? 'Hero subtitle';
        $subtitle = is_array($subtitle) ? ($subtitle['en'] ?? reset($subtitle) ?: 'Hero subtitle') : $subtitle;
        $pilotBlock = is_array($block) ? $block : ['id' => $block->id ?? null, '_uid' => $block->id ?? null];
        $inContext = app(\Pilot\Laravel\Support\InContext::class);
    @endphp
    @if($backgroundImage['url'])
        <div class="absolute inset-0 bg-cover opacity-20 rounded-lg" style="{{ $backgroundImage['background_image_style'] }}"></div>
    @endif
    <div class="relative space-y-4">
        <h2 class="text-4xl md:text-5xl font-bold" {!! $inContext->field($pilotBlock, 'title') !!}>{{ $title }}</h2>
        <p class="text-xl text-white/90" {!! $inContext->field($pilotBlock, 'subtitle', 'textarea') !!}>{{ $subtitle }}</p>
    </div>
</div>
