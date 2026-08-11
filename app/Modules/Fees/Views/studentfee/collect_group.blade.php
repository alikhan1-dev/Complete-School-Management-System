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
        <h3 class="box-title">Collect Fees (Group)</h3>
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

        <form method="post" action="{{ route('fees.studentfee.addfeegrp') }}" id="collect_group_form">
            @csrf
            <input type="hidden" name="student_session_id" value="{{ $student->student_session_id }}">

            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="collected_date" class="form-control" value="{{ old('collected_date', date('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode_fee" class="form-control" required>
                            @foreach($paymentModes as $mode)
                                <option value="{{ $mode }}" @selected(old('payment_mode_fee', 'Cash') === $mode)>{{ $mode }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Note</label>
                        <input type="text" name="fee_gupcollected_note" class="form-control" value="{{ old('fee_gupcollected_note') }}">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Fees Group</th>
                        <th>Fees Code</th>
                        <th>Balance</th>
                        <th>Fine</th>
                        <th>Paying Amount</th>
                        <th>Fine Paying</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php $totalPaying = 0; @endphp
                    @foreach($lines as $i => $line)
                        @php
                            $row = $i + 1;
                            $payAmount = max(0, (float) $line->balance);
                            $payFine = max(0, (float) $line->remaining_fine);
                            $totalPaying += $payAmount + $payFine;
                        @endphp
                        <tr>
                            <td>
                                {{ $line->fee_group_name }}
                                <input type="hidden" name="row_counter[]" value="{{ $row }}">
                                <input type="hidden" name="student_fees_master_id_{{ $row }}" value="{{ $line->student_fees_master_id }}">
                                <input type="hidden" name="fee_groups_feetype_id_{{ $row }}" value="{{ $line->fee_groups_feetype_id }}">
                                <input type="hidden" name="fee_session_group_id_{{ $row }}" value="{{ $line->fee_session_group_id }}">
                                <input type="hidden" name="fee_category_{{ $row }}" value="fees">
                                <input type="hidden" name="trans_fee_id_{{ $row }}" value="0">
                            </td>
                            <td>{{ $line->fee_type }} ({{ $line->fee_code }})</td>
                            <td>{{ number_format($line->balance, 2) }}</td>
                            <td>{{ number_format($line->remaining_fine, 2) }}</td>
                            <td>
                                <input type="number" step="0.01" min="0" max="{{ $line->balance }}"
                                       name="fee_amount_{{ $row }}" class="form-control fee_amount"
                                       value="{{ number_format($payAmount, 2, '.', '') }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0"
                                       name="fee_groups_feetype_fine_amount_{{ $row }}" class="form-control fee_fine"
                                       value="{{ number_format($payFine, 2, '.', '') }}">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total Paying</th>
                        <th colspan="2"><span id="total_paying_display">{{ number_format($totalPaying, 2) }}</span>
                            <input type="hidden" name="total_paying" id="total_paying" value="{{ number_format($totalPaying, 2, '.', '') }}">
                        </th>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Collect selected fees?');">Collect Fees</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function recalc() {
        var total = 0;
        $('.fee_amount, .fee_fine').each(function () {
            total += parseFloat($(this).val()) || 0;
        });
        total = Math.round(total * 100) / 100;
        $('#total_paying').val(total.toFixed(2));
        $('#total_paying_display').text(total.toFixed(2));
    }
    $(document).on('input change', '.fee_amount, .fee_fine', recalc);
})();
</script>
@endpush
