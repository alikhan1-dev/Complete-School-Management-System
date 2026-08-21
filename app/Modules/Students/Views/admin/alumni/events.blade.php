@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.event_list') }}</h3>
        <div class="box-tools pull-right">
            @if($canAdd)
                <a href="{{ url('admin/alumni/event/create') }}" class="btn btn-primary btn-sm">{{ __('system.add_event') }}</a>
            @endif
            <a href="{{ url('admin/alumni/alumnilist') }}" class="btn btn-default btn-sm">{{ __('system.alumni_student') }}</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.event_title') }}</th>
                    <th>{{ __('system.class') }} / {{ __('system.section') }}</th>
                    <th>{{ __('system.pass_out_session') }}</th>
                    <th>{{ __('system.from') }}</th>
                    <th>{{ __('system.to') }}</th>
                    <th class="text-right">{{ __('system.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eventlist as $row)
                    <tr>
                        <td>{{ $row->title }}</td>
                        <td>
                            @if($row->event_for === 'class')
                                {{ $row->class_name }}
                                @if($row->section_labels !== [])
                                    <br>{{ implode(', ', $row->section_labels) }}
                                @endif
                            @else
                                {{ __('system.all_alumni') }}
                            @endif
                        </td>
                        <td>{{ $row->event_for === 'class' ? $row->session_name : '' }}</td>
                        <td>{{ $events->formatDate($row->from_date) }}</td>
                        <td>{{ $events->formatDate($row->to_date) }}</td>
                        <td class="text-right">
                            @if($canEdit)
                                <a href="{{ url('admin/alumni/event/edit/'.$row->id) }}" class="btn btn-primary btn-xs" title="{{ __('system.edit') }}">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            @endif
                            @if($canDelete)
                                <a href="{{ url('admin/alumni/delete_event/'.$row->id) }}"
                                   class="btn btn-primary btn-xs"
                                   title="{{ __('system.delete') }}"
                                   onclick="return confirm(@json(__('system.delete_confirm')));">
                                    <i class="fa fa-remove"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">{{ __('system.no_record_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
