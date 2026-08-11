@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Search Fees Payment</h3>
        <div class="box-tools">
            <a href="{{ route('fees.studentfee.index') }}" class="btn btn-default btn-sm">Collect Fees</a>
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('fees.studentfee.searchpayment') }}" class="row">
            @csrf
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Payment ID</label>
                    <input type="text" name="payment_id" class="form-control" value="{{ $paymentId }}"
                           placeholder="e.g. 42/1" required>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-search"></i> Search</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($searched)
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Result</h3></div>
        <div class="box-body table-responsive">
            @if($payment)
                @php $entry = $payment->entry; $st = $payment->student; @endphp
                <table class="table table-bordered">
                    <tr><th>Payment ID</th><td>{{ $payment->payment_id }}</td></tr>
                    <tr><th>Student</th><td>{{ trim(($st->firstname ?? '').' '.($st->middlename ?? '').' '.($st->lastname ?? '')) }} ({{ $st->admission_no ?? '' }})</td></tr>
                    <tr><th>Class</th><td>{{ $st->class ?? '' }} ({{ $st->section ?? '' }})</td></tr>
                    <tr><th>Fees</th><td>{{ $st->fee_group_name ?? '' }} / {{ $st->fee_type ?? '' }} ({{ $st->fee_code ?? '' }})</td></tr>
                    <tr><th>Date</th><td>{{ $entry['date'] ?? '' }}</td></tr>
                    <tr><th>Amount</th><td>{{ number_format((float) ($entry['amount'] ?? 0), 2) }}</td></tr>
                    <tr><th>Discount</th><td>{{ number_format((float) ($entry['amount_discount'] ?? 0), 2) }}</td></tr>
                    <tr><th>Fine</th><td>{{ number_format((float) ($entry['amount_fine'] ?? 0), 2) }}</td></tr>
                    <tr><th>Mode</th><td>{{ $entry['payment_mode'] ?? '' }}</td></tr>
                    <tr><th>Collected By</th><td>{{ $entry['collected_by'] ?? '' }}</td></tr>
                    <tr><th>Note</th><td>{{ $entry['description'] ?? '' }}</td></tr>
                    <tr>
                        <th></th>
                        <td>
                            @if(!empty($st->student_session_id))
                                <a class="btn btn-info btn-sm" href="{{ route('fees.studentfee.addfee', $st->student_session_id) }}">Open Ledger</a>
                            @endif
                        </td>
                    </tr>
                </table>
            @else
                <p class="text-danger">No payment found for ID: {{ $paymentId }}</p>
            @endif
        </div>
    </div>
@endif
