<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.teachers_rating_list') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th>{{ __('system.staff_id') }}</th>
                <th>{{ __('system.name') }}</th>
                <th>{{ __('system.rating') }}</th>
                <th>{{ __('system.comment') }}</th>
                <th>{{ __('system.status') }}</th>
                <th>{{ __('system.student_name') }}</th>
                <th class="text-right">{{ __('system.action') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($resultlist ?? [] as $row)
                <tr>
                    <td>{{ $row['employee_id'] ?? '' }}</td>
                    <td>
                        <a href="{{ route('staff.profile', $row['id']) }}">{{ $row['name'] ?? '' }}</a>
                    </td>
                    <td>
                        @for($i = 1; $i <= 5; $i++)
                            <span class="fa fa-star" @if($i <= (int) ($row['rate'] ?? 0)) style="color:orange" @endif></span>
                        @endfor
                    </td>
                    <td>{{ $row['comment'] ?? '' }}</td>
                    <td>
                        @if((string) ($row['status'] ?? '') === '0')
                            <small class="label label-warning">{{ __('system.pending') }}</small>
                        @else
                            <small class="label label-success">{{ __('system.approved') }}</small>
                        @endif
                    </td>
                    <td>{{ $row['student_name'] ?? '' }}</td>
                    <td class="text-right">
                        @if(($canEdit ?? false) && (string) ($row['status'] ?? '') === '0')
                            <a class="label label-info btn btn-xs"
                               href="{{ route('staff.rating.approve', $row['rate_id']) }}">{{ __('system.approve') }}</a>
                        @endif
                        @if($canDelete ?? false)
                            <a class="btn btn-primary btn-xs"
                               onclick="return confirm(@json(__('system.delete_confirm')));"
                               href="{{ route('staff.rating.destroy', $row['rate_id']) }}">
                                <i class="fa fa-remove"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">{{ __('system.no_record_found') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
