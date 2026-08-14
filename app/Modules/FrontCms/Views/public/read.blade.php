@extends('frontcms::public.layout')

@section('content')
    <h1>{{ $page['title'] ?? '' }}</h1>
    @if(!empty($page['feature_image']))
        <p><img src="{{ asset($page['feature_image']) }}" alt=""></p>
    @endif
    @if(!empty($page['event_venue']))
        <p>{{ $page['event_venue'] }}</p>
    @endif
    <div>{!! $page['description'] ?? '' !!}</div>
    @if(!empty($page['page_contents']))
        <div>
            @foreach($page['page_contents'] as $photo)
                <img src="{{ asset($photo['thumb_path'] ?? $photo['image'] ?? '') }}" alt="{{ $photo['img_name'] ?? '' }}">
            @endforeach
        </div>
    @endif
@endsection
