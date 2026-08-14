<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page['meta_title'] ?? $page['title'] ?? $schoolName }}</title>
    @if(!empty($page['meta_description']))
        <meta name="description" content="{{ $page['meta_description'] }}">
    @endif
    @if(!empty($page['meta_keyword']))
        <meta name="keywords" content="{{ $page['meta_keyword'] }}">
    @endif
    <link rel="stylesheet" href="{{ asset('backend/bootstrap/css/bootstrap.min.css') }}">
</head>
<body>
<nav class="navbar navbar-default">
    <div class="container">
        <div class="navbar-header">
            <a class="navbar-brand" href="{{ url('frontend') }}">{{ $schoolName }}</a>
        </div>
        <ul class="nav navbar-nav">
            @foreach($mainMenus as $item)
                @php
                    $href = !empty($item['ext_url']) ? $item['ext_url_link'] : url($item['page_url'] ?: ('page/'.($item['page_slug'] ?? '')));
                    if (!empty($item['is_homepage'])) {
                        $href = url('frontend');
                    }
                @endphp
                <li class="{{ ($activeMenu === ($item['page_slug'] ?? '')) ? 'active' : '' }}">
                    <a href="{{ $href }}" @if(!empty($item['open_new_tab'])) target="_blank" @endif>{{ $item['menu'] }}</a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
<div class="container">
    @if(!empty($bannerNotices))
        <p>
            @foreach($bannerNotices as $notice)
                <a href="{{ url('read/'.$notice['slug']) }}">{{ $notice['title'] }}</a>@if(!$loop->last) | @endif
            @endforeach
        </p>
    @endif
    @yield('content')
    @if(!empty($setting->footer_text))
        <hr>
        <p>{!! $setting->footer_text !!}</p>
    @endif
</div>
@if($cookieConsent !== '' && request()->cookie('sitecookies') !== '1')
    <div class="container">
        <form method="post" action="{{ url('welcome/setsitecookies') }}">
            @csrf
            <p>{{ $cookieConsent }}</p>
            <button type="submit" class="btn btn-primary">Accept</button>
        </form>
    </div>
@endif
</body>
</html>
