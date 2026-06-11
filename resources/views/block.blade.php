@php
    $component = $block['component'] ?? $block['type'] ?? 'unknown';
    $componentName = (string) str($component)->replace(['.', '/', '\\'], '-')->kebab();
    $componentView = 'components.' . $componentName;
    $fallbackComponent = 'fallback';
    $fallbackView = 'components.' . $fallbackComponent;
    $data = $block['data'] ?? [];
    $children = $block['children'] ?? [];
@endphp

{!! $block['editor']['comment'] ?? '' !!}
<div {!! $block['editor']['attributes'] ?? '' !!}>
    @if(view()->exists($componentView))
        <x-dynamic-component :component="$componentName" :block="$block" :data="$data" :children="$children" />
    @elseif(view()->exists($fallbackView))
        <x-dynamic-component :component="$fallbackComponent" :block="$block" :data="$data" :children="$children" />
    @else
        @include('pilot::fallback', ['block' => $block, 'data' => $data])
    @endif
</div>
