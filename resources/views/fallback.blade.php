<section>
    <h2>{{ $block['component'] ?? $block['type'] ?? 'Block' }}</h2>

    @if(! empty($data))
        <dl>
            @foreach($data as $key => $value)
                <div>
                    <dt>{{ $key }}</dt>
                    <dd>{{ is_array($value) ? json_encode($value) : $value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</section>
