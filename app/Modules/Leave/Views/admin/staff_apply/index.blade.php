@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header ptbnull">
                <h3 class="box-title titlefix">Leaves</h3>
                <div class="box-tools pull-right">
                    @if(!empty($canAdd))
                        <a href="{{ route('leave.staff_apply.create') }}" class="btn btn-primary btn-sm">Apply Leave</a>
                    @endif
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Leave Type</th>
                            <th>Half Day</th>
                            <th>Leave Date</th>
                            <th>Days</th>
                            <th>Apply Date</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leave_request as $value)
                            @php
                                $status = $value['status'] ?? '';
                                if (in_array($status, ['approve', 'approved'], true)) {
                                    $statusKey = 'approve';
                                    $label = 'label-success';
                                    $canDelete = false;
                                } elseif ($status === 'pending') {
                                    $statusKey = 'pending';
                                    $label = 'label-warning';
                                    $canDelete = true;
                                } else {
                                    $statusKey = 'disapprove';
                                    $label = 'label-danger';
                                    $canDelete = true;
                                }
                                $half = $value['half_day_leave'] ?? '';
                                $halfLabel = $half === 'first_half' ? 'First Half' : ($half === 'second_half' ? 'Second Half' : ($half ?: '-'));
                            @endphp
                            <tr>
                                <td>{{ trim(($value['name'] ?? '').' '.($value['surname'] ?? '')).' ('.($value['employee_id'] ?? '').')' }}</td>
                                <td>{{ $value['type'] ?? '' }}</td>
                                <td>{{ $halfLabel }}</td>
                                <td>
                                    {{ !empty($value['leave_from']) ? date('d/m/Y', strtotime($value['leave_from'])) : '' }}
                                    -
                                    {{ !empty($value['leave_to']) ? date('d/m/Y', strtotime($value['leave_to'])) : '' }}
                                </td>
                                <td>{{ $value['leave_days'] ?? '' }}</td>
                                <td>{{ !empty($value['date']) ? date('d/m/Y', strtotime($value['date'])) : '' }}</td>
                                <td>
                                    <small class="label {{ $label }}">{{ $statusLabels[$statusKey] ?? $status }}</small>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('leave.staff_apply.view', $value['id']) }}" class="btn btn-primary btn-xs" title="View">
                                        <i class="fa fa-reorder"></i>
                                    </a>
                                    @if($canDelete)
                                        <a href="{{ route('leave.staff_apply.destroy', $value['id']) }}"
                                           class="btn btn-primary btn-xs" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this?')">
                                            <i class="fa fa-remove"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">No record found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
