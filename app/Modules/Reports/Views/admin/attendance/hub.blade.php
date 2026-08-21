<div class="box box-primary border0 mb0">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.attendance_report') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            @if(!empty($canAttendanceReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('attendencereports/classattendencereport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.attendance_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canAttendanceTypeReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('attendencereports/attendancereport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_attendance_type_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canDailyAttendanceReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('attendencereports/daily_attendance_report') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.daily_attendance_report') }}
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('attendencereports/daywiseattendancereport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_day_wise_attendance_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canStaffAttendanceReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('attendencereports/staffdaywiseattendancereport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.staff_day_wise_attendance_report') }}
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('attendencereports/staffattendancereport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.staff_attendance_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canPeriodAttendanceReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('attendencereports/reportbymonth') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.period_attendance_report') }}
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('attendencereports/reportbymonthstudent') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_period_attendance') }}
                    </a>
                </div>
            @endif
            @if(!empty($canBiometricLog))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('attendencereports/biometric_attlog') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.biometric_attendance_log') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
