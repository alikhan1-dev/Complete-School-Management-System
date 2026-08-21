@php $currency = $reports->currencySymbol(); @endphp
<div class="table-responsive">
    <h4>{{ __('system.balance_fees_report_with_remark') }}
        @if(!empty($class['class']) && !empty($section['section']))
            — {{ $class['class'] }} ({{ $section['section'] }})
        @endif
    </h4>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>{{ __('system.student_name') }}</th>
                <th>{{ __('system.class') }}</th>
                <th>{{ __('system.fees') }}</th>
                <th class="text text-right">{{ __('system.amount') }} ({{ $currency }})</th>
                <th class="text text-right">{{ __('system.paid') }} ({{ $currency }})</th>
                <th class="text text-right">{{ __('system.balance') }} ({{ $currency }})</th>
                <th class="text text-right">{{ __('system.remark') }}</th>
            </tr>
        </thead>
        <tbody>
            @php $totalamount = 0; $totalpaid = 0; $totalbalance = 0; @endphp
            @forelse($student_remain_fees as $student)
                @php
                    $amount = 0; $amount_deposite = 0; $amount_discount = 0;
                    foreach ($student['fees'] as $fee) {
                        $amount += (float) $fee['amount'];
                        $amount_deposite += (float) $fee['amount_deposite'];
                        $amount_discount += (float) $fee['amount_discount'];
                    }
                    $paid = $amount_deposite + $amount_discount;
                    $balance = $amount - $paid;
                    $totalamount += $amount;
                    $totalpaid += $paid;
                    $totalbalance += $balance;
                @endphp
                <tr>
                    <td>{{ $reports->fullName((object) $student) }} ({{ $student['admission_no'] }})</td>
                    <td>{{ $student['class'] }}-{{ $student['section'] }}</td>
                    <td>
                        @foreach($student['fees'] as $fee)
                            @if($fee['is_system'])
                                {{ __('system.'.$fee['fee_group']) }} ({{ __('system.'.$fee['fee_type']) }})
                            @else
                                {{ $fee['fee_group'] }} ({{ $fee['fee_type'] }} : {{ $fee['fee_code'] }})
                            @endif
                            @if(!$loop->last)<br/>@endif
                        @endforeach
                    </td>
                    <td class="text text-right">{{ $reports->formatAmount($amount) }}</td>
                    <td class="text text-right">{{ $reports->formatAmount($paid) }}</td>
                    <td class="text text-right">{{ $reports->formatAmount($balance) }}</td>
                    <td class="text text-right"><div style="height:100px;"></div></td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('system.no_record_found') }}</td></tr>
            @endforelse
            @if(!empty($student_remain_fees))
                <tr>
                    <th colspan="2"></th>
                    <th class="text text-right">{{ __('system.grand_total') }}</th>
                    <th class="text text-right">{{ $reports->formatAmount($totalamount) }}</th>
                    <th class="text text-right">{{ $reports->formatAmount($totalpaid) }}</th>
                    <th class="text text-right">{{ $reports->formatAmount($totalbalance) }}</th>
                    <th></th>
                </tr>
            @endif
        </tbody>
    </table>
</div>
