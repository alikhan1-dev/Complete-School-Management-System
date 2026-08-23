{{-- Ledger table for printFeesByGroup / printFeesByGroupArray --}}
<table class="lines">
    <thead>
    <tr>
        <th>{{ __('system.fees') }}</th>
        <th>{{ __('system.due_date') }}</th>
        <th>{{ __('system.status') }}</th>
        <th class="text-right">{{ __('system.amount') }}</th>
        <th class="text-center">{{ __('system.payment_id') }}</th>
        <th class="text-center">{{ __('system.mode') }}</th>
        <th>{{ __('system.date') }}</th>
        <th class="text-right">{{ __('system.paid') }}</th>
        <th class="text-right">{{ __('system.fine') }}</th>
        <th class="text-right">{{ __('system.discount') }}</th>
        <th class="text-right">{{ __('system.balance') }}</th>
    </tr>
    </thead>
    <tbody>
    @forelse($lines as $line)
        <tr class="summary">
            <td>{{ $line['fee_line_label'] }}</td>
            <td>{{ $line['due_date'] }}</td>
            <td>{{ $groupStatusLabel($line['status']) }}</td>
            <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($line['due_amount']) }}</td>
            <td colspan="3"></td>
            <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($line['paid_amount']) }}</td>
            <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($line['paid_fine']) }}</td>
            <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($line['paid_discount']) }}</td>
            <td class="text-right">
                @if($line['balance'] > 0)
                    {{ $currencySymbol }}{{ $formatAmount($line['balance']) }}
                @endif
            </td>
        </tr>
        @foreach($line['payments'] as $payment)
            <tr class="payment">
                <td colspan="4"></td>
                <td class="text-center">{{ $payment['payment_id'] }}</td>
                <td class="text-center">{{ $payment['payment_mode'] }}</td>
                <td>{{ $payment['date'] }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($payment['amount']) }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($payment['amount_fine']) }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($payment['amount_discount']) }}</td>
                <td></td>
            </tr>
        @endforeach
    @empty
        <tr>
            <td colspan="11" class="text-center">{{ __('system.no_transaction_found') }}</td>
        </tr>
    @endforelse
    </tbody>
</table>
