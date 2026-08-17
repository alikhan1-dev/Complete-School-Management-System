@php
    $today = $shares->formatDate(date('Y-m-d'));
@endphp

@if(session('success'))
    <div class="alert alert-success text-left">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('system.share_date') }} / {{ __('system.send_to') }}</h3>
            </div>
            <form action="{{ url('admin/content/share') }}" method="post">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>{{ __('system.title') }} <small class="req">*</small></label>
                        <input type="text" name="title" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{ __('system.share_date') }} <small class="req">*</small></label>
                        <input type="text" name="share_date" class="form-control" value="{{ $today }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('system.valid_upto') }}</label>
                        <input type="text" name="valid_upto" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{ __('system.description') }}</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>{{ __('system.send_to') }} <small class="req">*</small></label>
                        <div>
                            <label class="radio-inline"><input type="radio" name="send_to" value="group" checked> {{ __('system.group') }}</label>
                            <label class="radio-inline"><input type="radio" name="send_to" value="class"> {{ __('system.class') }}</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __('system.group') }}</label>
                        <div class="checkbox"><label><input type="checkbox" name="user[]" value="student"> {{ __('system.students') }}</label></div>
                        <div class="checkbox"><label><input type="checkbox" name="user[]" value="parent"> {{ __('system.guardians') }}</label></div>
                        @foreach($roles as $role)
                            <div class="checkbox"><label><input type="checkbox" name="user[]" value="{{ $role->id }}"> {{ $role->name }}</label></div>
                        @endforeach
                    </div>
                    <div class="form-group">
                        <label>{{ __('system.class') }} / {{ __('system.section') }}</label>
                        @foreach($classSections as $section)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="class_section_id[]" value="{{ $section->id }}">
                                    {{ $section->class }} ({{ $section->section }})
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-group">
                        <label>{{ __('system.contents') }} <small class="req">*</small></label>
                        <select name="selected_contents[]" class="form-control" multiple size="6">
                            @foreach($uploads as $file)
                                <option value="{{ $file->id }}">{{ $file->file_type === 'video' ? $file->vid_title : $file->real_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-info pull-right">{{ __('system.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header ptbnull">
                <h3 class="box-title titlefix">{{ __('system.content_share_list') }}</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover content-list">
                        <thead>
                            <tr>
                                <th>{{ __('system.title') }}</th>
                                <th>{{ __('system.send_to') }}</th>
                                <th>{{ __('system.share_date') }}</th>
                                <th>{{ __('system.valid_upto') }}</th>
                                <th>{{ __('system.shared_by') }}</th>
                                <th>{{ __('system.description') }}</th>
                                <th class="text-right noExport">{{ __('system.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $row->title }}</td>
                                    <td>{{ __('system.'.$row->send_to) }}</td>
                                    <td>{{ $shares->formatDate($row->share_date) }}</td>
                                    <td>{{ $shares->formatDate($row->valid_upto) }}</td>
                                    <td>{{ $shares->uploads()->staffFullName($row->name, $row->surname, $row->employee_id) }}</td>
                                    <td>{{ $row->description === null || $row->description === '' ? __('system.no_description') : $row->description }}</td>
                                    <td class="text-right">
                                        @if($row->send_to === 'public')
                                            <a href="{{ $shares->publicShareUrl((int) $row->id) }}" class="btn btn-primary btn-xs" target="_blank" title="{{ __('system.link') }}">
                                                <i class="fa fa-link"></i>
                                            </a>
                                        @endif
                                        @if(!empty($canDelete))
                                            <a href="{{ url('admin/content/delete_content/'.$row->id) }}"
                                               class="btn btn-primary btn-xs" title="{{ __('system.delete') }}"
                                               onclick="return confirm('{{ __('system.delete_confirm') }}')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">{{ __('system.no_record_found') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
