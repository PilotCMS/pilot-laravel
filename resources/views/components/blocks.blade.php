@props(['content'])

@include('pilot::blocks', ['blocks' => $content->blocks])
