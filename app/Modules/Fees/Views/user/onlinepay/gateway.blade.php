{{-- CI user/gateway/{method} persist checkout — live charge deferred --}}
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.payment_details') }}</h3>
        <div class="box-tools">
            <a href="{{ route('user.fees.getfees') }}" class="btn btn-default btn-sm">{{ __('system.back') }}</a>
        </div>
    </div>
    <div class="box-body">
        <p><strong>{{ __('system.payment_methods') }}:</strong> {{ strtoupper($params['payment_type'] ?? $gateway) }}</p>
        <p><strong>{{ __('system.name') }}:</strong> {{ $params['student_name'] ?? '' }}</p>

        <table class="table table-bordered">
            <thead>
            <tr>
                <th>{{ __('system.fees') }}</th>
                <th class="text-right">{{ __('system.amount') }}</th>
                <th class="text-right">{{ __('system.fine') }}</th>
                <th class="text-right">{{ __('system.discount') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach(($params['lines'] ?? []) as $line)
                <tr>
                    <td>{{ $line['fee_group_name'] ?? '' }} ({{ $line['fee_type_code'] ?? '' }})</td>
                    <td class="text-right">{{ number_format((float) ($line['amount_balance'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float) ($line['fine_balance'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float) ($line['applied_fee_discount'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <th>{{ __('system.fees') }}</th>
                <th class="text-right" colspan="3">{{ $currencySymbol }}{{ number_format((float) ($params['fee_total'] ?? 0), 2) }}</th>
            </tr>
            <tr>
                <th>{{ __('system.fine') }}</th>
                <th class="text-right" colspan="3">{{ $currencySymbol }}{{ number_format((float) ($params['fine_total'] ?? 0), 2) }}</th>
            </tr>
            @if(((float) ($params['gateway_processing_charge'] ?? 0)) > 0)
                <tr>
                    <th>{{ __('system.processing_fees') }}</th>
                    <th class="text-right" colspan="3">{{ $currencySymbol }}{{ number_format((float) $params['gateway_processing_charge'], 2) }}</th>
                </tr>
            @endif
            <tr>
                <th>{{ __('system.total') }}</th>
                <th class="text-right" colspan="3">{{ $currencySymbol }}{{ number_format((float) ($params['total'] ?? 0), 2) }}</th>
            </tr>
            </tfoot>
        </table>

        <div class="alert alert-info">
            Live gateway charge is deferred. Checkout totals and session params are persisted for parity with CI <code>user/gateway/Payment::pay</code>.
        </div>
    </div>
</div>
