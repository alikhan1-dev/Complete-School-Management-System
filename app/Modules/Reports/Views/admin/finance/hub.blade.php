<div class="box box-primary border0 mb0">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.finance') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            @if(!empty($canBalanceFeesStatement))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/reportduefees') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.balance_fees_statement') }}
                    </a>
                </div>
            @endif
            @if(!empty($canDailyCollection))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/reportdailycollection') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.daily_collection_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canFeesStatement))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/reportbyname') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.fees_statement') }}
                    </a>
                </div>
            @endif
            @if(!empty($canBalanceFeesReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/studentacademicreport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.balance_fees_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canFeesCollectionReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/collection_report') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.fees_collection_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canOnlineFeesCollection))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/onlinefees_report') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.online_fees_collection_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canBalanceFeesRemark))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/duefeesremark') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.balance_fees_report_with_remark') }}
                    </a>
                </div>
            @endif
            @if(!empty($canIncomeReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/income') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.income_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canExpenseReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/expense') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.expense_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canPayrollReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/payroll') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.payroll_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canIncomeGroupReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/incomegroup') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.income_group_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canExpenseGroupReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/expensegroup') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.expense_group_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canOnlineAdmissionFees))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/onlineadmission') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.online_admission_fees_collection_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canDueFeesReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('balancefees/index') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.due_fees_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canIncomeExpenseBalance))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('financereports/incomeexpensebalancereport') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.income_expense_balance_report') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
