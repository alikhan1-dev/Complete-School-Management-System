@php
    $record = $record ?? [];
    $status = $record['status'] ?? 'pending';
    if (in_array($status, ['approve', 'approved'], true)) {
        $statusKey = 'approve';
    } elseif ($status === 'pending') {
        $statusKey = 'pending';
    } else {
        $statusKey = 'disapprove';
    }
@endphp

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Leave Details</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('leave.staff_apply.index') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Leave Type</th>
                        <td>{{ $record['type'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>Leave Date</th>
                        <td>
                            {{ !empty($record['leave_from']) ? date('d/m/Y', strtotime($record['leave_from'])) : '' }}
                            -
                            {{ !empty($record['leave_to']) ? date('d/m/Y', strtotime($record['leave_to'])) : '' }}
                            ({{ $record['days'] ?? $record['leave_days'] ?? '' }} days)
                        </td>
                    </tr>
                    <tr>
                        <th>Apply Date</th>
                        <td>{{ !empty($record['date']) ? date('d/m/Y', strtotime($record['date'])) : '' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>{{ $statusLabels[$statusKey] ?? $status }}</td>
                    </tr>
                    <tr>
                        <th>Reason</th>
                        <td>{{ $record['employee_remark'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>Admin Remark</th>
                        <td>{{ $record['admin_remark'] ?? '' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
