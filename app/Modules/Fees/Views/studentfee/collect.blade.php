@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Collect Fee — {{ $balance['fee_group_name'] }} / {{ $balance['fee_type'] }} ({{ $balance['fee_code'] }})</h3>
        <div class="box-tools">
            <a href="{{ route('fees.studentfee.addfee', $student->student_session_id) }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body">
        <p>
            <strong>Student:</strong>
            {{ trim($student->firstname.' '.($student->middlename ?? '').' '.$student->lastname) }}
            ({{ $student->admission_no }}) —
            {{ $student->class }} ({{ $student->section }})
        </p>
        <p>
            <strong>Due:</strong> {{ number_format($balance['due'], 2) }} &nbsp;
            <strong>Balance:</strong> {{ number_format($balance['balance'], 2) }} &nbsp;
            <strong>Fine due:</strong> {{ number_format($balance['remaining_fine'], 2) }}
        </p>

        <form method="post" action="{{ route('fees.studentfee.addstudentfee') }}" id="collect_fee_form">
            @csrf
            <input type="hidden" name="student_session_id" value="{{ $student->student_session_id }}">
            <input type="hidden" name="student_fees_master_id" value="{{ $studentFeesMasterId }}">
            <input type="hidden" name="fee_groups_feetype_id" value="{{ $feeGroupsFeetypeId }}">
            <input type="hidden" name="fee_session_group_id" value="{{ $balance['fee_session_group_id'] ?? 0 }}">
            <input type="hidden" name="transport_fees_id" value="{{ $transportFeesId ?? 0 }}">
            <input type="hidden" name="fee_category" value="{{ $feeCategory ?? 'fees' }}">

            @if(count($availableDiscounts))
                <div class="form-group">
                    <label>Apply Discount</label>
                    @foreach($availableDiscounts as $d)
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" class="grp_discount" name="discounts[]" value="{{ $d->id }}"
                                       data-type="{{ $d->type }}"
                                       data-amount="{{ $d->type === 'percentage' ? $d->percentage : $d->amount }}">
                                {{ $d->name }} ({{ $d->code }}) —
                                @if($d->type === 'percentage')
                                    {{ number_format((float) $d->percentage, 2) }}%
                                @else
                                    {{ number_format((float) $d->amount, 2) }}
                                @endif
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control"
                               value="{{ old('amount', number_format($balance['balance'], 2, '.', '')) }}" required>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Discount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount_discount" id="amount_discount" class="form-control"
                               value="{{ old('amount_discount', '0.00') }}" required>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Fine <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount_fine" id="amount_fine" class="form-control"
                               value="{{ old('amount_fine', number_format($balance['remaining_fine'], 2, '.', '')) }}" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode" class="form-control" required>
                            @foreach($paymentModes as $mode)
                                <option value="{{ $mode }}" @selected(old('payment_mode', 'Cash') === $mode)>{{ $mode }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-8">
                    <div class="form-group">
                        <label>Note</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Collect Fees</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var balance = {{ json_encode((float) $balance['balance']) }};
    function recalc() {
        var discount = 0;
        $('.grp_discount:checked').each(function () {
            var type = $(this).data('type');
            var val = parseFloat($(this).data('amount')) || 0;
            if (type === 'percentage') {
                discount += (balance * val / 100);
            } else {
                discount += val;
            }
        });
        discount = Math.round(discount * 100) / 100;
        if (discount > balance) discount = balance;
        $('#amount_discount').val(discount.toFixed(2));
        $('#amount').val(Math.max(0, balance - discount).toFixed(2));
    }
    $(document).on('change', '.grp_discount', recalc);
})();
</script>
@endpush
