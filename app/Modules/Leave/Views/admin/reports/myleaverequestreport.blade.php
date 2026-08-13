@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
    </div>
    <form method="post" action="{{ route('leave.reports.my_request') }}">
        @csrf
        <div class="box-body row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" value="{{ $from_date }}" class="form-control">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" value="{{ $to_date }}" class="form-control">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="leave_status">
                        <option value="">Select</option>
                        @foreach($statusOptions as $key => $label)
                            <option value="{{ $key }}" @selected((string) $leave_status === (string) $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                    <i class="fa fa-search"></i> Search
                </button>
            </div>
        </div>
    </form>

    <div class="box-header with-border">
        <h3 class="box-title">My Leave Request Report</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Half Day</th>
                    <th>Apply Date</th>
                    <th>Leave Date</th>
                    <th>Days</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resultlist as $value)
                    @php
                        $status = $value['status'] ?? '';
                        if ($status === 'approved') {
                            $label = 'label-success';
                            $text = 'Approved';
                        } elseif ($status === 'pending') {
                            $label = 'label-warning';
                            $text = 'Pending';
                        } else {
                            $label = 'label-danger';
                            $text = 'Disapproved';
                        }
                        $half = $value['half_day_leave'] ?? '';
                        $halfLabel = $half === 'first_half' ? 'First Half' : ($half === 'second_half' ? 'Second Half' : ($half ?: '-'));
                    @endphp
                    <tr>
                        <td>{{ $value['type'] ?? '' }}</td>
                        <td>{{ $halfLabel }}</td>
                        <td>{{ !empty($value['date']) ? date('d/m/Y', strtotime($value['date'])) : '' }}</td>
                        <td>
                            {{ !empty($value['leave_from']) ? date('d/m/Y', strtotime($value['leave_from'])) : '' }}
                            -
                            {{ !empty($value['leave_to']) ? date('d/m/Y', strtotime($value['leave_to'])) : '' }}
                        </td>
                        <td>{{ $value['leave_days'] ?? '' }}</td>
                        <td><small class="label {{ $label }}">{{ $text }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">
                            {{ !empty($searched) ? 'No record found.' : 'Use filters and search.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
