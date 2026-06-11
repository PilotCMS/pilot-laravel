@foreach($blocks as $block)
    @include('pilot::block', ['block' => $block])
@endforeach
