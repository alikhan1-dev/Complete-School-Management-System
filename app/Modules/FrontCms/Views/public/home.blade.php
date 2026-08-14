@extends('frontcms::public.layout')

@section('content')
    @if(!empty($bannerImages))
        <div>
            @foreach($bannerImages as $image)
                <img src="{{ asset($image['thumb_path'] ?? $image['image'] ?? '') }}" alt="{{ $image['img_name'] ?? '' }}">
            @endforeach
        </div>
    @endif
    <h1>{{ $page['title'] ?? '' }}</h1>
    <div>{!! $page['description'] ?? '' !!}</div>
@endsection
