<div class="">
    <div class="row">
        <div class="col-md-9">
            <h4 class="box-title mt0 bmedium font16">{{ $shared_contents->title }}</h4>
            <div class="row">
                <div class="col-md-4">
                    <p><label>{{ __('system.upload_date') }}</label>: {{ $shares->formatDate($shared_contents->share_date) }}</p>
                </div>
                @if(!empty($shared_contents->valid_upto) && $shared_contents->valid_upto !== '0000-00-00')
                    <div class="col-md-4">
                        <p><label>{{ __('system.valid_upto') }}</label>: {{ $shares->formatDate($shared_contents->valid_upto) }}</p>
                    </div>
                @endif
                <div class="col-md-4">
                    <p><label>{{ __('system.share_date') }}</label>: {{ $shares->formatDate($shared_contents->share_date) }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <p><label>{{ __('system.shared_by') }}</label>: {{ $uploads->staffFullName($shared_contents->name, $shared_contents->surname, $shared_contents->employee_id) }}</p>
                </div>
                <div class="col-md-4">
                    <p><label>{{ __('system.send_to') }}</label>: {{ __('system.'.$shared_contents->send_to) }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label>{{ __('system.description') }}</label>: {{ $shared_contents->description }}
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 ptt10">
                    <h4 class="box-title bmedium mb0 font16">{{ __('system.attachments') }}</h4>
                    <ul class="list-group content-share-list">
                        @forelse($shared_contents->upload_contents as $file)
                            <li class="list-group-item overflow-hidden mb5">
                                @if($file->file_type === 'video')
                                    <a href="{{ $file->vid_url }}" target="_blank">{{ $file->vid_title }}</a>
                                @else
                                    {{ $file->real_name }}
                                    <a href="{{ url('site/download_content/'.$file->upload_content_id.'/'.$shares->encryptedShareId((int) $file->share_content_id)) }}" data-toggle="tooltip" title="{{ __('system.download') }}">
                                        &nbsp; <i class="fa fa-download"></i>
                                    </a>
                                @endif
                            </li>
                        @empty
                            <div class="alert alert-danger">{{ __('system.no_record_found') }}</div>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <h4 class="box-title bmedium mt10 font16">{{ __('system.shared_groups_users') }}</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <tbody>
                        @foreach($result_array_labels as $label)
                            <tr><td>{{ ucfirst($label) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
