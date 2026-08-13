@php
    $sch = $schSetting ?? null;
    $result = $result ?? [];
    $employee_payroll = $employee_payroll ?? [];
    $earnings = $earnings ?? [];
    $deductions = $deductions ?? [];
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
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header">
                <div class="row">
                    <div class="col-md-4">
                        <h3 class="box-title">Edit Payroll for : {{ $month }}</h3>
                    </div>
                    <div class="col-md-8">
                        <div class="btn-group pull-right">
                            <a href="{{ route('payroll.index') }}" class="btn btn-primary btn-xs"><i class="fa fa-arrow-left"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-body" style="padding-top:0;">
                @include('payroll::admin.partials.staff_header')
            </div>

            <form class="form-horizontal" action="{{ route('payroll.editpayroll') }}" method="post" id="employeeform">
                @csrf
                <input type="hidden" name="role" value="{{ $result['user_type'] ?? '' }}">
                <input type="hidden" name="id" value="{{ $employee_payroll['id'] }}">

                <div class="box-header">
                    <div class="row">
                        <div class="col-md-4 col-sm-4">
                            <h3 class="box-title">Earning</h3>
                            <button type="button" onclick="add_more()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i></button>
                            <div class="feebox">
                                <table class="table" id="tableID">
                                    @forelse($earnings as $i => $earning)
                                        <input type="hidden" name="allowance_prev_id[]" value="{{ $earning['id'] }}">
                                        <tr id="row{{ $i }}">
                                            <td><input type="text" class="form-control" name="allowance_type[]" value="{{ $earning['allowance_type'] }}" placeholder="Type"></td>
                                            <td><input type="text" name="allowance_amount[]" class="form-control" value="{{ $earning['amount'] }}"></td>
                                            <td><button type="button" onclick="delete_row({{ $i }})" class="btn btn-xs btn-danger"><i class="fa fa-remove"></i></button></td>
                                        </tr>
                                    @empty
                                        <tr id="row0">
                                            <td>
                                                <input type="hidden" name="allowance_prev_id[]" value="0">
                                                <input type="text" class="form-control" name="allowance_type[]" placeholder="Type">
                                            </td>
                                            <td><input type="text" name="allowance_amount[]" class="form-control" value="0"></td>
                                            <td><button type="button" onclick="delete_row(0)" class="btn btn-xs btn-danger"><i class="fa fa-remove"></i></button></td>
                                        </tr>
                                    @endforelse
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-4">
                            <h3 class="box-title">Deduction</h3>
                            <button type="button" onclick="add_more_deduction()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i></button>
                            <div class="feebox">
                                <table class="table" id="tableID2">
                                    @forelse($deductions as $i => $deduction)
                                        <input type="hidden" name="deduction_prev_id[]" value="{{ $deduction['id'] }}">
                                        <tr id="deduction_row{{ $i }}">
                                            <td><input type="text" name="deduction_type[]" class="form-control" value="{{ $deduction['allowance_type'] }}" placeholder="Type"></td>
                                            <td><input type="text" name="deduction_amount[]" class="form-control" value="{{ $deduction['amount'] }}"></td>
                                            <td><button type="button" onclick="delete_deduction_row({{ $i }})" class="btn btn-xs btn-danger"><i class="fa fa-remove"></i></button></td>
                                        </tr>
                                    @empty
                                        <tr id="deduction_row0">
                                            <td>
                                                <input type="hidden" name="deduction_prev_id[]" value="0">
                                                <input type="text" name="deduction_type[]" class="form-control" placeholder="Type">
                                            </td>
                                            <td><input type="text" name="deduction_amount[]" class="form-control" value="0"></td>
                                            <td><button type="button" onclick="delete_deduction_row(0)" class="btn btn-xs btn-danger"><i class="fa fa-remove"></i></button></td>
                                        </tr>
                                    @endforelse
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-4">
                            <h3 class="box-title">Payroll Summary ({{ $currencySymbol ?? '$' }})</h3>
                            <button type="button" onclick="add_allowance()" class="btn btn-default btn-xs"><i class="fa fa-calculator"></i> Calculate</button>
                            <div class="payrollbox feebox">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Basic Salary</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="basic" id="basic" type="text"
                                               value="{{ old('basic', $employee_payroll['basic'] ?? 0) }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Earning</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="total_allowance" id="total_allowance" type="text"
                                               value="{{ old('total_allowance', $employee_payroll['total_allowance'] ?? 0) }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Deduction</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="total_deduction" id="total_deduction" type="text"
                                               style="color:#f50000"
                                               value="{{ old('total_deduction', $employee_payroll['total_deduction'] ?? 0) }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Gross Salary</label>
                                    <div class="col-sm-8">
                                        @php
                                            $gross = (float)($employee_payroll['basic'] ?? 0)
                                                + (float)($employee_payroll['total_allowance'] ?? 0)
                                                - (float)($employee_payroll['total_deduction'] ?? 0);
                                        @endphp
                                        <input class="form-control" name="gross_salary" id="gross_salary" value="{{ $gross }}" type="text">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Tax</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="tax" id="tax" type="text"
                                               value="{{ old('tax', $employee_payroll['tax'] ?? 0) }}">
                                    </div>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Net Salary</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="net_salary" id="net_salary" type="text" required
                                               value="{{ old('net_salary', $employee_payroll['net_salary'] ?? '') }}">
                                        <input type="hidden" name="staff_id" value="{{ $result['id'] }}">
                                        <input type="hidden" name="month" value="{{ $month }}">
                                        <input type="hidden" name="year" value="{{ $year }}">
                                        <input type="hidden" name="status" value="{{ $employee_payroll['status'] ?? 'generated' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <button type="submit" class="btn btn-info pull-right">Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('payroll::admin.partials.payroll_calc_script')
