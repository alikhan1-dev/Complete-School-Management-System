@php
    $record = $record ?? [];
    $status = $record['status'] ?? 'pending';
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Leave Details</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('leave.requests.index') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Staff</th>
                        <td>{{ trim(($record['name'] ?? '').' '.($record['surname'] ?? '')).' ('.($record['employee_id'] ?? '').')' }}</td>
                    </tr>
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
                        <th>Reason</th>
                        <td>{{ $record['employee_remark'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>Submitted By</th>
                        <td>{{ $record['applied_by_label'] ?? '' }}</td>
                    </tr>
                </table>

                @if(!empty($canEdit))
                    <form method="post" action="{{ route('leave.requests.status', $record['id']) }}">
                        @csrf
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="pending" @selected($status === 'pending')>Pending</option>
                                <option value="approved" @selected($status === 'approved')>Approved</option>
                                <option value="disapprove" @selected(in_array($status, ['disapprove', 'disapproved'], true))>Disapproved</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Admin Remark</label>
                            <textarea name="detailremark" class="form-control" maxlength="200">{{ old('detailremark', $record['admin_remark'] ?? '') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary pull-right">Save</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
