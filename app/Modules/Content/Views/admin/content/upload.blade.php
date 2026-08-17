@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $val = fn (string $key, $default = '') => $old[$key] ?? old($key, $default);
    $count = $count ?? ['number' => 0, 'file_size' => 0];
@endphp

@if(session('success'))
    <div class="alert alert-success text-left">{{ session('success') }}</div>
@endif

<div class="row">
    @if(!empty($canAdd))
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('system.upload') }}</h3>
                </div>
                <form action="{{ url('admin/content/upload') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>{{ __('system.content_type') }} <small class="req">*</small></label>
                            <select name="content_type" class="form-control">
                                <option value="">{{ __('system.select') }}</option>
                                @foreach($content_types as $type)
                                    <option value="{{ $type->id }}" @selected((string) $val('content_type') === (string) $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @if(!empty($formErrors['content_type']))
                                <span class="text-danger">{{ $formErrors['content_type'] }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>{{ __('system.upload_your_file') }}</label>
                            <input type="file" name="upload[]" class="form-control">
                            @if(!empty($formErrors['file']))
                                <span class="text-danger">{{ $formErrors['file'] }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>{{ __('system.upload_youtube_video_link') }}</label>
                            <input type="text" name="url" class="form-control" value="{{ $val('url') }}">
                            @if(!empty($formErrors['url']))
                                <span class="text-danger">{{ $formErrors['url'] }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right">{{ __('system.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-md-{{ !empty($canAdd) ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header ptbnull">
                <h3 class="box-title titlefix">{{ __('system.content_list') }}</h3>
            </div>
            <div class="box-body">
                <p>{{ __('system.total_documents') }}: <span class="total_files">{{ $count['number'] }}</span>
                    &nbsp; {{ __('system.size') }}: <span class="total_size">{{ $uploads->formatFileSize($count['file_size']) }}</span>
                </p>
                <form method="get" action="{{ url('admin/content/upload') }}" class="form-inline" style="margin-bottom:12px;">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('system.search') }}" value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-primary">{{ __('system.search') }}</button>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('system.document') }}</th>
                                <th>{{ __('system.content_type') }}</th>
                                <th>{{ __('system.size') }}</th>
                                <th>{{ __('system.upload_by') }}</th>
                                <th>{{ __('system.created_on') }}</th>
                                <th class="text-right">{{ __('system.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $row->file_type === 'video' ? $row->vid_title : $row->real_name }}</td>
                                    <td>{{ $row->content_type }}</td>
                                    <td>{{ $row->file_type === 'video' ? __('system.n_a') : $uploads->formatFileSize($row->file_size) }}</td>
                                    <td>{{ $uploads->staffFullName($row->staff_name, $row->surname, $row->employee_id) }}</td>
                                    <td>{{ $uploads->formatCreatedAt($row->created_at) }}</td>
                                    <td class="text-right">
                                        @if($row->file_type !== 'video')
                                            <a href="{{ url('admin/content/download_content/'.$row->id) }}" class="btn btn-default btn-xs" title="{{ __('system.download') }}">
                                                <i class="fa fa-download"></i>
                                            </a>
                                        @endif
                                        @if(!empty($canDelete))
                                            <form action="{{ url('admin/content/delete') }}" method="post" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $row->id }}">
                                                <button type="submit" class="btn btn-primary btn-xs" title="{{ __('system.delete') }}"
                                                        onclick="return confirm('{{ __('system.delete_confirm') }}')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">{{ __('system.no_record_found') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
