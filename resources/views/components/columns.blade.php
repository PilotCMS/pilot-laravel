@props([
    'block' => [],
    'data' => [],
    'children' => [],
    'renderChildren' => true,
])

@php
    $columnCount = (int) ($data['columns'] ?? 2);
    $columnCount = max(1, min(4, $columnCount));
    $parentType = is_array($block) ? ($block['type'] ?? $block['component'] ?? 'block') : ($block->type ?? 'block');
    $blockChildren = is_array($block) ? ($block['children'] ?? []) : ($block->children ?? []);
    $children = collect($children ?: $blockChildren)->values();
    $childrenForColumn = function (int $columnIndex) use ($children, $columnCount) {
        return $children->filter(function ($child, $index) use ($columnIndex, $columnCount) {
            $childData = is_array($child) ? ($child['data'] ?? []) : ($child->data ?? []);
            $childColumn = array_key_exists('_column', $childData)
                ? (int) $childData['_column']
                : $index % $columnCount;

            return $childColumn === $columnIndex;
        });
    };
    $columnClasses = [
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
    ][$columnCount];
@endphp

<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="mb-3 flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Columns</span>
        <span class="rounded-full bg-white px-2 py-1 text-xs text-slate-500">{{ $columnCount }} columns</span>
    </div>

    @if($renderChildren && $children->isNotEmpty())
        <div class="grid gap-4 {{ $columnClasses }}">
            @foreach(range(0, $columnCount - 1) as $columnIndex)
                <div class="space-y-4 rounded-lg border border-dashed border-slate-200 bg-white p-3">
                    @foreach($childrenForColumn($columnIndex) as $child)
                        @php
                            $childId = is_array($child) ? ($child['id'] ?? $child['_uid'] ?? null) : ($child->id ?? null);
                            $childType = is_array($child) ? ($child['component'] ?? $child['type'] ?? 'unknown') : ($child->type ?? 'unknown');
                            $childComponentName = (string) str($childType)->replace(['.', '/', '\\'], '-')->kebab();
                            $childComponentView = 'components.' . $childComponentName;
                            $childPackageComponentView = 'pilot::components.' . $childComponentName;
                            $childData = is_array($child) ? ($child['data'] ?? []) : ($child->data ?? []);
                            $childChildren = is_array($child) ? ($child['children'] ?? []) : ($child->children ?? []);
                        @endphp

                        <div
                            @if($childId)
                                data-pilot-editable="block"
                                data-pilot-block-id="{{ $childId }}"
                                data-pilot-component="{{ $childType }}"
                                data-pilot-component-path="{{ $parentType }}/{{ $childType }}"
                            @endif
                            class="rounded-lg border border-transparent transition-colors hover:border-blue-300 hover:bg-blue-50/30"
                        >
                            @if(view()->exists($childComponentView))
                                <x-dynamic-component :component="$childComponentName" :block="$child" :data="$childData" :children="$childChildren" />
                            @elseif(view()->exists($childPackageComponentView))
                                @include($childPackageComponentView, ['block' => $child, 'data' => $childData, 'children' => $childChildren])
                            @else
                                @include('pilot::components.fallback', ['block' => $child, 'data' => $childData, 'children' => $childChildren])
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @else
        <div class="grid gap-4 {{ $columnClasses }}">
            @foreach(range(1, $columnCount) as $column)
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-400">
                    Column {{ $column }}
                </div>
            @endforeach
        </div>
    @endif
</div>
