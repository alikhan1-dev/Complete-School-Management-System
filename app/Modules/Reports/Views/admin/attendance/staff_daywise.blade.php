@include('reports::admin.attendance.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('attendencereports/staffdaywiseattendancereport') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.role') }} <small class="req">*</small></label>
                        <select name="role" class="form-control">
                            <option value="select" @selected((string) $filters['role'] === 'select')>{{ __('system.select') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected((string) $filters['role'] === (string) $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.date') }} <small class="req">*</small></label>
                        <input type="text" name="date" class="form-control" value="{{ $filters['date'] }}">
                        @error('date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.source') }}</label>
                        <select name="attendance_mode" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            <option value="1" @selected((string) $filters['attendance_mode'] === '1')>{{ __('system.manual') }}</option>
                            <option value="2" @selected((string) $filters['attendance_mode'] === '2')>{{ __('system.qrcode') }} / {{ __('system.barcode') }}</option>
                            <option value="3" @selected((string) $filters['attendance_mode'] === '3')>{{ __('system.biometric') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

@if($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.staff_day_wise_attendance_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.staff_id') }}</th>
                        <th>{{ __('system.name') }}</th>
                        <th>{{ __('system.role') }}</th>
                        <th>{{ __('system.attendance') }}</th>
                        <th>{{ __('system.note') }}</th>
                        <th>{{ __('system.source') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $staff)
                        <tr>
                            <td>{{ $staff->employee_id }}</td>
                            <td>{{ $staff->name }} {{ $staff->surname }}</td>
                            <td>{{ $staff->user_type }}</td>
                            <td>{{ $staff->att_type }}</td>
                            <td>{{ $staff->remark }}</td>
                            <td>{{ $reports->attendanceSourceLabel($staff) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
