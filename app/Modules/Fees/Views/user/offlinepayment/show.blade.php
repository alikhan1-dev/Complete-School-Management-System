@php $isTransport = filled($payment->student_transport_fee_id ?? null); @endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.payment_details') }}</h3>
        <div class="box-tools">
            <a href="{{ route('user.offlinepayment.requests') }}" class="btn btn-default btn-sm">{{ __('system.back') }}</a>
        </div>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tr>
                <th width="40%">{{ __('system.request_id') }}</th>
                <td>{{ $payment->id }}</td>
            </tr>
            <tr>
                <th>{{ __('system.payment_date') }}</th>
                <td>{{ $offline->formatDate($payment->payment_date) }}</td>
            </tr>
            <tr>
                <th>{{ __('system.submit_date') }}</th>
                <td>{{ $offline->formatDateTime($payment->submit_date) }}</td>
            </tr>
            <tr>
                <th>{{ __('system.amount') }}</th>
                <td>{{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
            <tr>
                <th>{{ __('system.payment_mode') }}</th>
                <td>{{ $payment->bank_from }}</td>
            </tr>
            <tr>
                <th>{{ __('system.payment_from') }}</th>
                <td>{{ $payment->bank_account_transferred }}</td>
            </tr>
            <tr>
                <th>{{ __('system.reference') }}</th>
                <td>{{ $payment->reference }}</td>
            </tr>
            <tr>
                <th>{{ __('system.status') }}</th>
                <td>{{ $offline->statusLabel($payment->is_active) }}</td>
            </tr>
            @if(filled($payment->reply))
                <tr>
                    <th>{{ __('system.comment_reason') }}</th>
                    <td>{{ $payment->reply }}</td>
                </tr>
            @endif
            @if($isTransport)
                <tr>
                    <th>Transport</th>
                    <td>{{ $payment->route_title }} / {{ $payment->pickup_point }}</td>
                </tr>
            @else
                <tr>
                    <th>Fee Group</th>
                    <td>{{ $payment->fee_group_name }}</td>
                </tr>
                <tr>
                    <th>Fee Type</th>
                    <td>{{ $payment->type }} ({{ $payment->code }})</td>
                </tr>
            @endif
            @if(filled($payment->attachment))
                <tr>
                    <th>{{ __('system.proof_of_payment') }}</th>
                    <td>
                        <a href="{{ route('user.offlinepayment.download', $payment->id) }}">
                            <i class="fa fa-download"></i> {{ __('system.download') }}
                        </a>
                    </td>
                </tr>
            @endif
        </table>
    </div>
</div>
