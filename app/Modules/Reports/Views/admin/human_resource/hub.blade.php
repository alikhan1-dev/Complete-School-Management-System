<div class="box box-primary border0 mb0">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.human_resource_report') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            @if(!empty($canStaffReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/staff_report') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.staff_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canPayrollReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('admin/payroll/payrollreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.payroll_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canStaffLeaveRequestReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/leaverequestreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.leave_request_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canMyLeaveRequestReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/myleaverequestreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.my_leave_request_report') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
