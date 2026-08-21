@include('reports::admin.attendance.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('attendencereports/staffattendancereport') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.role') }}</label>
                        <select name="role" class="form-control">
                            <option value="select" @selected((string) $filters['role'] === 'select' || (string) $filters['role'] === '')>{{ __('system.select') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected((string) $filters['role'] === (string) $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.month') }} <small class="req">*</small></label>
                        <select name="month" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($monthlist as $mKey => $monthLabel)
                                <option value="{{ $mKey }}" @selected((string) $filters['month'] === (string) $mKey)>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                        @error('month')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.year') }} <small class="req">*</small></label>
                        <select name="year" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($yearlist as $yearRow)
                                <option value="{{ $yearRow->year }}" @selected((string) $filters['year'] === (string) $yearRow->year)>{{ $yearRow->year }}</option>
                            @endforeach
                        </select>
                        @error('year')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
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

@if($searched && $resultlist !== null)
    <div class="box box-primary">
        <div class="box-header with-border">
            <div class="row">
                <div class="col-md-4">
                    <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.staff_attendance_report') }}</h3>
                </div>
                <div class="col-md-8">
                    <div class="lateday">
                        @foreach($attendencetypeslist as $valueType)
                            &nbsp;&nbsp;<b>{{ $valueType->type }}: {{ $valueType->key_value }}</b>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="box-body table-responsive">
            @if(empty($resultlist))
                <div class="alert alert-info">{{ __('system.no_attendance_prepared') }}</div>
            @else
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('system.staff_date') }}</th>
                            <th><br/><span data-toggle="tooltip" title="{{ __('system.gross_present_percentage') }}">(%)</span></th>
                            @if(!empty($attendence_array))
                                @foreach($attendencetypeslist as $value)
                                    <th>
                                        <br/><span data-toggle="tooltip" title="{{ __('system.total') }} {{ $value->type }}">{{ strip_tags((string) $value->key_value) }}</span>
                                    </th>
                                @endforeach
                            @endif
                            @foreach($attendence_array as $atValue)
                                @php $hdr = $reports->dayHeader($atValue); @endphp
                                <th class="tdcls text text-center">
                                    @if($hdr['is_sunday'])
                                        <a href="#">{{ $hdr['d'] }}<br/>{{ __('system.'.$hdr['dow_key']) }}</a>
                                    @else
                                        {{ $hdr['d'] }}<br/>{{ __('system.'.$hdr['dow_key']) }}
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($student_array))
                            <tr>
                                <td colspan="32" class="text-danger text-center">{{ __('system.no_record_found') }}</td>
                            </tr>
                        @else
                            @foreach($student_array as $i => $staff)
                                @php
                                    $staffId = (int) $staff->id;
                                    $counts = $monthAttendance[$i][$staffId] ?? [];
                                    $pct = $reports->staffPresentPercentage($counts);
                                @endphp
                                <tr>
                                    <td class="tdclsname">
                                        {{ $staff->name }} {{ $staff->surname }} ({{ $staff->employee_id }})
                                    </td>
                                    <th><label class="{{ $pct['class'] }}">{{ $pct['print'] }}</label></th>
                                    <th>{{ (int) ($counts['present'] ?? 0) }}</th>
                                    <th>{{ (int) ($counts['late'] ?? 0) }}</th>
                                    <th>{{ (int) ($counts['absent'] ?? 0) }}</th>
                                    <th>{{ (int) ($counts['half_day'] ?? 0) }}</th>
                                    <th>{{ (int) ($counts['holiday'] ?? 0) }}</th>
                                    <th>{{ (int) ($counts['half_day_second_shift'] ?? 0) }}</th>
                                    @foreach($attendence_array as $atValue)
                                        @php
                                            $cell = $resultlist[$atValue][$staffId] ?? null;
                                            $key = $cell->key ?? null;
                                            $remark = $cell->remark ?? '';
                                        @endphp
                                        <th class="tdcls text text-center" title="{{ $remark }}">{{ $key }}</th>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif
