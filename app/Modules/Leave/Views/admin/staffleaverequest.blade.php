@php
    $authStaffId = $authStaffId ?? 0;
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header ptbnull">
                <h3 class="box-title titlefix pt5">Approve Leave Request</h3>
                @if(!empty($canAdd))
                    <small class="pull-right">
                        <a href="{{ route('leave.requests.create') }}" class="btn btn-primary btn-sm">Add Leave Request</a>
                    </small>
                @endif
            </div>
            <div class="box-body">
                <div class="table-responsive">
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
                                    if ($status === 'approved') {
                                        $statusKey = 'approve';
                                        $label = 'label-success';
                                    } elseif ($status === 'pending') {
                                        $statusKey = 'pending';
                                        $label = 'label-warning';
                                    } else {
                                        $statusKey = 'disapprove';
                                        $label = 'label-danger';
                                    }
                                    $half = $value['half_day_leave'] ?? '';
                                    $halfLabel = $half === 'first_half' ? 'First Half' : ($half === 'second_half' ? 'Second Half' : ($half ?: '-'));
                                    $isOwn = (int) ($value['applied_by_id'] ?? 0) === (int) $authStaffId;
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
                                        <small class="label {{ $label }}" title="Submitted by: {{ $value['applied_by_label'] ?? '' }}">
                                            {{ $statusLabels[$statusKey] ?? $status }}
                                        </small>
                                    </td>
                                    <td class="text-right white-space-nowrap">
                                        <a href="{{ route('leave.requests.view', $value['id']) }}" class="btn btn-primary btn-xs" title="View">
                                            <i class="fa fa-reorder"></i>
                                        </a>
                                        @if($isOwn && !empty($canEdit))
                                            <a href="{{ route('leave.requests.edit', $value['id']) }}" class="btn btn-primary btn-xs" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endif
                                        @if(!empty($value['document_file']))
                                            <a href="{{ route('leave.requests.download', ['staffId' => $value['staff_id'], 'id' => $value['id']]) }}"
                                               class="btn btn-primary btn-xs" title="Download">
                                                <i class="fa fa-download"></i>
                                            </a>
                                        @endif
                                        @if($isOwn || !empty($canDelete))
                                            <a href="{{ url('admin/leaverequest/remove/'.$value['id'].'/'.$value['staff_id']) }}"
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
</div>
