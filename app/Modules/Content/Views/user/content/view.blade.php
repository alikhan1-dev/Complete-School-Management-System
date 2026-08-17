<div class="box box-primary">
    <div class="box-header ptbnull">
        <h3 class="box-title titlefix">{{ __('system.content') }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ url('user/content/list') }}" class="btn btn-default btn-sm">{{ __('system.content_list') }}</a>
        </div>
    </div>
    <div class="box-body">
        @if($isOpen)
            <h4 class="mt0">{{ $content->title }}</h4>
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4">
                    <label>{{ __('system.upload_date') }}</label> : {{ $portal->shares()->formatDate($content->share_date) }}
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4">
                    <label>{{ __('system.valid_upto') }}</label> : {{ $portal->shares()->formatDate($content->valid_upto) }}
                </div>
                @if($showSharedBy)
                    <div class="col-lg-4 col-md-4 col-sm-4">
                        <label>{{ __('system.shared_by') }}</label> :
                        {{ $portal->shares()->uploads()->staffFullName($content->name, $content->surname, $content->employee_id) }}
                    </div>
                @endif
                <div class="col-md-3">
                    <p>
                        <label>{{ __('system.share_date') }}</label>:
                        {{ $portal->shares()->formatDate($content->share_date) }}
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label>{{ __('system.description') }}</label> : {{ $content->description }}
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h4 class="box-title">{{ __('system.attachments') }}</h4>
                    @if($content->upload_contents->isEmpty())
                        <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
                    @else
                        <ul class="list-group content-share-list">
                            @foreach($content->upload_contents as $file)
                                <li class="list-group-item">
                                    @if($file->file_type === 'video')
                                        <a href="{{ $file->vid_url }}" target="_blank">{{ $file->vid_title }}</a>
                                    @else
                                        {{ $file->real_name }}
                                        <a href="{{ url('site/download_content/'.$file->upload_content_id.'/'.$portal->shares()->encryptedShareId((int) $file->share_content_id)) }}" data-toggle="tooltip" title="{{ __('system.download') }}">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <a href="{{ url('user/content/download_content/'.$file->upload_content_id) }}" class="btn btn-link btn-xs">
                                            {{ __('system.download') }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-danger">{{ __('system.sorry_this_link_is_invalid_or_expired_please_contact_to_system_admin') }}</div>
        @endif
    </div>
</div>
