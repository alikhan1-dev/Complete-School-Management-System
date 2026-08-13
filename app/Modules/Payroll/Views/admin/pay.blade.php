@php
    $payment = $payment ?? [];
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
                <h3 class="box-title">Proceed to Pay</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('payroll.index') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <form method="post" action="{{ route('payroll.payment_success') }}">
                @csrf
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Staff Name</label>
                            <input type="text" name="emp_name" readonly class="form-control"
                                   value="{{ trim(($payment['name'] ?? '').' '.($payment['surname'] ?? '')).' ('.($payment['employee_id'] ?? '').')' }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Payment Amount ({{ $currencySymbol ?? '$' }})</label>
                            <input type="text" name="amount" readonly class="form-control"
                                   value="{{ number_format((float) ($payment['net_salary'] ?? 0), 2, '.', '') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Month Year</label>
                            <input type="text" readonly class="form-control" value="{{ ($monthLabel ?? $month).'-'.$year }}">
                            <input type="hidden" name="paymentmonth" value="{{ $month }}">
                            <input type="hidden" name="paymentyear" value="{{ $year }}">
                            <input type="hidden" name="paymentid" value="{{ $payment['id'] }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($paymentModes as $pkey => $pvalue)
                                    <option value="{{ $pkey }}" @selected(old('payment_mode') === $pkey)>{{ $pvalue }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" required
                                   value="{{ old('payment_date', date('Y-m-d')) }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Note</label>
                            <textarea name="remarks" class="form-control">{{ old('remarks') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
