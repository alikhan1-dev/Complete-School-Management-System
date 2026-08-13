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
                <h3 class="box-title">Apply Leave</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('leave.staff_apply.index') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <form method="post" enctype="multipart/form-data" action="{{ route('leave.staff_apply.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Apply Date <span class="text-danger">*</span></label>
                        <input type="date" name="applieddate" class="form-control" required
                               value="{{ old('applieddate', date('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label>Leave From Date <span class="text-danger">*</span></label>
                        <input type="date" name="leave_from_date" class="form-control" required value="{{ old('leave_from_date') }}">
                    </div>
                    <div class="form-group">
                        <label>Leave To Date <span class="text-danger">*</span></label>
                        <input type="date" name="leave_to_date" class="form-control" required value="{{ old('leave_to_date') }}">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="half_day_leave" value="1" @checked(old('half_day_leave'))>
                            Half Day
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Available Leave <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($availableLeaves as $leave)
                                <option value="{{ $leave['id'] }}" @selected((int) old('leave_type') === (int) $leave['id'])>
                                    {{ $leave['type'] }} ({{ $leave['available'] }})
                                </option>
                            @endforeach
                        </select>
                        @if(count($availableLeaves) === 0)
                            <p class="help-block text-danger">No leave allotted for you in the current session.</p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" maxlength="200">{{ old('reason') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Attach Document</label>
                        <input type="file" name="userfile" class="form-control">
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
