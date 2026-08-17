@props([
    'block' => [],
    'data' => [],
    'children' => [],
    'renderChildren' => true,
])

@include('pilot::components.columns', compact('block', 'data', 'children', 'renderChildren'))
