@extends('frontcms::public.layout')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <h1>{{ $page['title'] ?? '' }}</h1>
    <div>{!! $pageHtml !!}</div>

    @if(!empty($page['category_items']))
        <div id="postList">
            @include('frontcms::public.ajax_pagination', [
                'categoryItems' => $page['category_items'],
                'pageContentType' => $page['page_content_type'] ?? '',
            ])
        </div>
    @endif

    @if($formName === 'contact_us')
        <form method="post" action="{{ url('page/'.($page['slug'] ?? request()->route('slug'))) }}">
            @csrf
            <input type="hidden" name="form_name" value="contact_us">
            <input type="hidden" name="email_title" value="New Inquiry from Contact US">
            <div class="form-group">
                <label>Name</label>
                <input class="form-control" name="name" value="{{ old('name') }}">
                @error('name')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Email</label>
                <input class="form-control" type="email" name="email" value="{{ old('email') }}">
                @error('email')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input class="form-control" name="subject" value="{{ old('subject') }}">
                @error('subject')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    @endif

    @if($formName === 'complain')
        <form method="post" action="{{ url('page/'.($page['slug'] ?? request()->route('slug'))) }}">
            @csrf
            <input type="hidden" name="form_name" value="complain">
            <input type="hidden" name="email_title" value="New Inquiry from Complain">
            <div class="form-group">
                <label>Name</label>
                <input class="form-control" name="name" value="{{ old('name') }}">
                @error('name')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Email</label>
                <input class="form-control" type="email" name="email" value="{{ old('email') }}">
                @error('email')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Contact no</label>
                <input class="form-control" name="contact_no" value="{{ old('contact_no') }}">
                @error('contact_no')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    @endif
@endsection
