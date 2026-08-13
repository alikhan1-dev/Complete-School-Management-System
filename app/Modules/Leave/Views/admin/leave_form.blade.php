@php
    $editing = $editing ?? null;
    $isEdit = $editing !== null;
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
                <h3 class="box-title">{{ $isEdit ? 'Edit Leave Request' : 'Add Leave Request' }}</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('leave.requests.index') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <form method="post"
                  enctype="multipart/form-data"
                  action="{{ $isEdit ? route('leave.requests.update', $editing['id']) : route('leave.requests.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Role <span class="text-danger">*</span></label>
                        @if($isEdit)
                            <input type="hidden" name="role" value="{{ old('role', $selectedRole) }}">
                            <select id="role" class="form-control" disabled>
                                <option value="">Select</option>
                                @foreach($staffrole as $role)
                                    <option value="{{ $role->id }}" @selected((int) old('role', $selectedRole) === (int) $role->id)>
                                        {{ $role->type }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <select name="role" id="role" class="form-control" required
                                    onchange="window.location='{{ route('leave.requests.create') }}?role='+this.value">
                                <option value="">Select</option>
                                @foreach($staffrole as $role)
                                    <option value="{{ $role->id }}" @selected((int) old('role', $selectedRole) === (int) $role->id)>
                                        {{ $role->type }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        @if($isEdit)
                            <input type="hidden" name="empname" value="{{ old('empname', $selectedStaff) }}">
                            <select id="empname" class="form-control" disabled>
                                <option value="">Select</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp['id'] }}" @selected((int) old('empname', $selectedStaff) === (int) $emp['id'])>
                                        {{ trim(($emp['name'] ?? '').' '.($emp['surname'] ?? '')).' ('.($emp['employee_id'] ?? '').')' }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <select name="empname" id="empname" class="form-control" required
                                    onchange="window.location='{{ route('leave.requests.create') }}?role={{ (int) $selectedRole }}&empname='+this.value">
                                <option value="">Select</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp['id'] }}" @selected((int) old('empname', $selectedStaff) === (int) $emp['id'])>
                                        {{ trim(($emp['name'] ?? '').' '.($emp['surname'] ?? '')).' ('.($emp['employee_id'] ?? '').')' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Apply Date <span class="text-danger">*</span></label>
                        <input type="date" name="applieddate" class="form-control" required
                               value="{{ old('applieddate', $editing['date'] ?? date('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label>Leave From Date <span class="text-danger">*</span></label>
                        <input type="date" name="leave_from_date" class="form-control" required
                               value="{{ old('leave_from_date', $editing['leave_from'] ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Leave To Date <span class="text-danger">*</span></label>
                        <input type="date" name="leave_to_date" class="form-control" required
                               value="{{ old('leave_to_date', $editing['leave_to'] ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="half_day_leave" value="1"
                                @checked(old('half_day_leave', !empty($editing['half_day_leave'])))>
                            Half Day
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Available Leave <span class="text-danger">*</span></label>
                        <select name="leave_type" id="leave_type" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($availableLeaves as $leave)
                                <option value="{{ $leave['id'] }}"
                                    @selected((int) old('leave_type', $editing['leave_type_id'] ?? $editing['lid'] ?? 0) === (int) $leave['id'])>
                                    {{ $leave['type'] }} ({{ $leave['available'] }})
                                </option>
                            @endforeach
                        </select>
                        @if($selectedStaff <= 0)
                            <p class="help-block">Select role and staff to load available leave types.</p>
                        @elseif(count($availableLeaves) === 0)
                            <p class="help-block text-danger">No leave allotted for this staff in the current session.</p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" maxlength="200">{{ old('reason', $editing['employee_remark'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Note</label>
                        <textarea name="remark" class="form-control" maxlength="200">{{ old('remark', $editing['admin_remark'] ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="addstatus" class="form-control" required>
                            <option value="pending" @selected(old('addstatus', $editing['status'] ?? 'pending') === 'pending')">Pending</option>
                            <option value="approved" @selected(old('addstatus', $editing['status'] ?? '') === 'approved')">Approved</option>
                            <option value="disapprove" @selected(old('addstatus', $editing['status'] ?? '') === 'disapprove')">Disapproved</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Attach Document</label>
                        <input type="file" name="userfile" class="form-control">
                        @if(!empty($editing['document_file']))
                            <p class="help-block">Current: {{ $editing['document_file'] }}</p>
                        @endif
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
