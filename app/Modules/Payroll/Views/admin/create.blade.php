@php
    $sch = $schSetting ?? null;
    $result = $result ?? [];
    $imageFile = !empty($result['image']) ? $result['image'] : 'no_image.png';
    $imageUrl = asset('uploads/staff_images/'.$imageFile);
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
                        <h3 class="box-title">Staff Details</h3>
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

            <form class="form-horizontal" action="{{ route('payroll.payslip') }}" method="post" id="employeeform">
                @csrf
                <div class="box-header">
                    <div class="row">
                        <div class="col-md-4 col-sm-4">
                            <h3 class="box-title">Earning</h3>
                            <button type="button" onclick="add_more()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i></button>
                            <div class="feebox">
                                <table class="table" id="tableID">
                                    <tr id="row0">
                                        <td><input type="text" class="form-control" name="allowance_type[]" placeholder="Type"></td>
                                        <td><input type="text" name="allowance_amount[]" class="form-control" value="0"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-4">
                            <h3 class="box-title">Deduction</h3>
                            <button type="button" onclick="add_more_deduction()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i></button>
                            <div class="feebox">
                                <table class="table" id="tableID2">
                                    <tr id="deduction_row0">
                                        <td><input type="text" name="deduction_type[]" class="form-control" placeholder="Type"></td>
                                        <td><input type="text" name="deduction_amount[]" class="form-control" value="0"></td>
                                    </tr>
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
                                               value="{{ old('basic', $result['basic_salary'] ?? 0) }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Earning</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="total_allowance" id="total_allowance" type="text" value="{{ old('total_allowance', '0') }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Deduction</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="total_deduction" id="total_deduction" type="text" value="{{ old('total_deduction', '0') }}" style="color:#f50000">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Gross Salary</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="gross_salary" id="gross_salary" value="0" type="text">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Tax</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="tax" id="tax" value="{{ old('tax', '0') }}" type="text">
                                    </div>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Net Salary</label>
                                    <div class="col-sm-8">
                                        <input class="form-control" name="net_salary" id="net_salary" type="text" value="{{ old('net_salary') }}" required>
                                        <input type="hidden" name="staff_id" value="{{ $result['id'] }}">
                                        <input type="hidden" name="month" value="{{ $month }}">
                                        <input type="hidden" name="year" value="{{ $year }}">
                                        <input type="hidden" name="status" value="generated">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12">
                            <button type="submit" id="contact_submit" class="btn btn-info pull-right">Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('payroll::admin.partials.payroll_calc_script')
