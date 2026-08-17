<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('backend/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/dist/css/font-awesome.min.css') }}">
</head>
<body style="background:#f6f6f6;">
<div class="container" style="margin-top:40px;">
    <div class="row">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    @if($isOpen && $share_data)
                        <h1>{{ $share_data->title }}</h1>
                        <p><b>{{ __('system.upload_date') }}</b> : {{ $shares->formatDate($share_data->share_date) }}</p>
                        <p><b>{{ __('system.valid_upto') }}</b> : {{ $shares->formatDate($share_data->valid_upto) }}</p>
                        <p><b>{{ __('system.shared_by') }}</b> : {{ $uploads->staffFullName($share_data->name, $share_data->surname, $share_data->employee_id) }}</p>
                        <ul class="list-group">
                            @foreach($share_data->upload_contents as $file)
                                <li class="list-group-item">
                                    @if($file->file_type === 'video')
                                        <a href="{{ $file->vid_url }}" target="_blank">{{ $file->vid_title }}</a>
                                    @else
                                        {{ $uploads->fileview((string) $file->img_name) }}
                                        <a href="{{ url('site/download_content/'.$file->upload_content_id.'/'.$shares->encryptedShareId((int) $file->share_content_id)) }}">
                                            <i class="fa fa-download"></i> {{ __('system.download') }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <h1>{{ __('system.this_link_is_invalid_or_expired') }}</h1>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
