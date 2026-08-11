@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Student Fees</h3>
        <div class="box-tools">
            <a href="{{ route('fees.studentfee.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3"><strong>Admission No:</strong> {{ $student->admission_no }}</div>
            <div class="col-md-3"><strong>Name:</strong> {{ trim($student->firstname.' '.($student->middlename ?? '').' '.$student->lastname) }}</div>
            <div class="col-md-3"><strong>Class:</strong> {{ $student->class }} ({{ $student->section }})</div>
            <div class="col-md-3"><strong>Session:</strong> {{ $student->session }}</div>
        </div>
        <div class="row" style="margin-top:8px;">
            <div class="col-md-3"><strong>Father:</strong> {{ $student->father_name }}</div>
            <div class="col-md-3"><strong>Mobile:</strong> {{ $student->mobileno }}</div>
            <div class="col-md-3"><strong>Guardian Phone:</strong> {{ $student->guardian_phone }}</div>
        </div>
    </div>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Fees</h3>
        <div class="box-tools">
            <button type="button" class="btn btn-primary btn-sm" id="btn_collect_selected">Collect Selected</button>
        </div>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th style="width:36px;"><input type="checkbox" id="select_all_fees"></th>
                <th>Fees Group</th>
                <th>Fees Code</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Discount</th>
                <th>Fine</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @php $grandDue = 0; $grandDiscount = 0; $grandFine = 0; $grandPaid = 0; $grandBalance = 0; @endphp
            @forelse($ledger as $line)
                @php
                    $grandDue += $line->due_amount;
                    $grandDiscount += $line->paid_discount;
                    $grandFine += $line->paid_fine;
                    $grandPaid += $line->paid_amount;
                    $grandBalance += max(0, $line->balance);
                    $status = $line->balance <= 0 ? 'Paid' : ($line->paid_amount > 0 ? 'Partial' : 'Unpaid');
                    $selectValue = $line->student_fees_master_id.':'.$line->fee_groups_feetype_id;
                @endphp
                <tr>
                    <td>
                        @if($line->balance > 0)
                            <input type="checkbox" class="fee_line_cb" value="{{ $selectValue }}">
                        @endif
                    </td>
                    <td>{{ $line->fee_group_name }}</td>
                    <td>{{ $line->fee_type }} ({{ $line->fee_code }})</td>
                    <td>{{ $line->due_date ?: '—' }}</td>
                    <td>
                        @if($status === 'Paid')
                            <span class="label label-success">Paid</span>
                        @elseif($status === 'Partial')
                            <span class="label label-info">Partial</span>
                        @else
                            <span class="label label-danger">Unpaid</span>
                        @endif
                    </td>
                    <td>{{ number_format($line->due_amount, 2) }}</td>
                    <td>{{ number_format($line->paid_discount, 2) }}</td>
                    <td>{{ number_format($line->paid_fine, 2) }}@if($line->remaining_fine > 0) <small class="text-danger">(+{{ number_format($line->remaining_fine, 2) }} due)</small>@endif</td>
                    <td>{{ number_format($line->paid_amount, 2) }}</td>
                    <td>{{ number_format(max(0, $line->balance), 2) }}</td>
                    <td>
                        @if($line->balance > 0)
                            <a class="btn btn-primary btn-xs"
                               href="{{ route('fees.studentfee.collect', [
                                   'student_session_id' => $student->student_session_id,
                                   'student_fees_master_id' => $line->student_fees_master_id,
                                   'fee_groups_feetype_id' => $line->fee_groups_feetype_id,
                               ]) }}">Collect</a>
                        @endif
                    </td>
                </tr>
                @foreach($line->payments as $pay)
                    <tr class="bg-gray-light">
                        <td></td>
                        <td colspan="4" class="text-right">
                            Payment {{ $pay->payment_id }} — {{ $pay->date }} — {{ $pay->payment_mode }}
                            @if($pay->collected_by) <small>({{ $pay->collected_by }})</small>@endif
                            @if($pay->description) <br><em>{{ $pay->description }}</em>@endif
                        </td>
                        <td></td>
                        <td>{{ number_format($pay->amount_discount, 2) }}</td>
                        <td>{{ number_format($pay->amount_fine, 2) }}</td>
                        <td>{{ number_format($pay->amount, 2) }}</td>
                        <td></td>
                        <td>
                            @if($canDelete)
                                <form method="post" action="{{ route('fees.studentfee.deleteFee') }}" style="display:inline;"
                                      onsubmit="return confirm('Delete this payment?');">
                                    @csrf
                                    <input type="hidden" name="main_invoice" value="{{ $pay->invoice_id }}">
                                    <input type="hidden" name="sub_invoice" value="{{ $pay->sub_invoice_id }}">
                                    <input type="hidden" name="student_session_id" value="{{ $student->student_session_id }}">
                                    <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="11" class="text-center">No fees assigned</td></tr>
            @endforelse
            @if(count($ledger))
                <tr>
                    <th></th>
                    <th colspan="4" class="text-right">Grand Total</th>
                    <th>{{ number_format($grandDue, 2) }}</th>
                    <th>{{ number_format($grandDiscount, 2) }}</th>
                    <th>{{ number_format($grandFine, 2) }}</th>
                    <th>{{ number_format($grandPaid, 2) }}</th>
                    <th>{{ number_format($grandBalance, 2) }}</th>
                    <th></th>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

<form method="post" action="{{ route('fees.studentfee.collect_group') }}" id="multi_collect_select_form" style="display:none;">
    @csrf
    <input type="hidden" name="student_session_id" value="{{ $student->student_session_id }}">
    <div id="multi_collect_selected_fields"></div>
</form>

@push('scripts')
<script>
$(function () {
    $('#select_all_fees').on('change', function () {
        $('.fee_line_cb').prop('checked', $(this).prop('checked'));
    });
    $('#btn_collect_selected').on('click', function () {
        var $checked = $('.fee_line_cb:checked');
        if ($checked.length === 0) {
            alert('Select at least one unpaid fee line.');
            return;
        }
        if (!confirm('Collect selected fees?')) {
            return;
        }
        var $fields = $('#multi_collect_selected_fields').empty();
        $checked.each(function () {
            $fields.append($('<input>', {type: 'hidden', name: 'selected[]', value: $(this).val()}));
        });
        $('#multi_collect_select_form').trigger('submit');
    });
});
</script>
@endpush

@if($discounts->isNotEmpty())
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Assigned Discounts</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Amount / %</th>
                    <th>Expire</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($discounts as $d)
                    <tr>
                        <td>{{ $d->name }}</td>
                        <td>{{ $d->code }}</td>
                        <td>{{ $d->type }}</td>
                        <td>
                            @if($d->type === 'percentage')
                                {{ number_format((float) $d->percentage, 2) }}%
                            @else
                                {{ number_format((float) $d->amount, 2) }}
                            @endif
                        </td>
                        <td>{{ $d->expire_date ?: '—' }}</td>
                        <td>{{ $d->status }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
