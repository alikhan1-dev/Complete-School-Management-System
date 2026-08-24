{{-- CI user/student/getProcessingfees — pending online gateway fee lines --}}
@php
    $studentName = trim(($student->firstname ?? '').' '.($student->middlename ?? '').' '.($student->lastname ?? ''));
    $hasFeeLines = false;
    foreach ($student_due_fee as $feeGroup) {
        if (! empty($feeGroup->fees)) {
            $hasFeeLines = true;
            break;
        }
    }
    $hasRows = $hasFeeLines || ! empty($transport_fees);
@endphp

<div class="table-responsive">
    <div class="download_label">{{ __('system.student_fees') }}: {{ $studentName }}</div>

    @unless($hasRows)
        <div class="alert alert-danger">No fees Found.</div>
    @else
        <table class="table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th>{{ __('system.fees_group') }}</th>
                <th>{{ __('system.fees_code') }}</th>
                <th class="text-center">{{ __('system.due_date') }}</th>
                <th>{{ __('system.status') }}</th>
                <th class="text-right">{{ __('system.amount') }} ({{ $currencySymbol }})</th>
                <th>{{ __('system.payment_id') }}</th>
                <th>{{ __('system.mode') }}</th>
                <th>{{ __('system.date') }}</th>
                <th class="text-right">{{ __('system.discount') }} ({{ $currencySymbol }})</th>
                <th class="text-right">{{ __('system.fine') }} ({{ $currencySymbol }})</th>
                <th class="text-right">{{ __('system.paid') }} ({{ $currencySymbol }})</th>
                <th class="text-right">{{ __('system.balance') }} ({{ $currencySymbol }})</th>
            </tr>
            </thead>
            <tbody>
            @php
                $totalAmount = 0; $totalPaid = 0; $totalFine = 0; $totalDiscount = 0; $totalBalance = 0;
            @endphp

            @foreach($student_due_fee as $feeGroup)
                @foreach($feeGroup->fees as $line)
                    @php
                        $totalAmount += $line->amount;
                        $totalDiscount += $line->paid_discount;
                        $totalPaid += $line->paid_amount;
                        $totalFine += $line->paid_fine;
                        if ($line->balance > 0) {
                            $totalBalance += $line->balance;
                        }
                        $isOverdue = $line->due_date
                            && $line->due_date !== '0000-00-00'
                            && strtotime((string) $line->due_date) < strtotime(date('Y-m-d'));
                    @endphp
                    <tr class="{{ $isOverdue && $line->balance > 0 ? 'danger' : 'dark-gray' }}">
                        <td>{{ $line->name }} ({{ $line->type }})</td>
                        <td>{{ $line->code }}</td>
                        <td class="text-center">{{ $line->due_date ?: '—' }}</td>
                        <td><span class="label label-danger">{{ __('system.processing') }}</span></td>
                        <td class="text-right">
                            {{ number_format($line->amount, 2) }}
                            @if($isOverdue && $line->overdue_fine > 0)
                                <span class="text-danger"> + {{ number_format($line->overdue_fine, 2) }}</span>
                            @endif
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-right">{{ number_format($line->paid_discount, 2) }}</td>
                        <td class="text-right">{{ number_format($line->paid_fine, 2) }}</td>
                        <td class="text-right">{{ number_format($line->paid_amount, 2) }}</td>
                        <td class="text-right">{{ $line->balance > 0 ? number_format($line->balance, 2) : '' }}</td>
                    </tr>
                    @if($line->payment)
                        <tr class="white-td">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-right">↳</td>
                            <td>
                                {{ $line->unique_id }}
                                @if($line->payment['description'] === '')
                                    <small class="text-danger d-block">{{ __('system.no_description') }}</small>
                                @else
                                    <small class="d-block">{{ $line->payment['description'] }}</small>
                                @endif
                            </td>
                            <td>{{ $line->payment['payment_mode'] }}</td>
                            <td>{{ $line->payment['date'] }}</td>
                            <td class="text-right">{{ number_format($line->payment['amount_discount'], 2) }}</td>
                            <td class="text-right">{{ number_format($line->payment['amount_fine'], 2) }}</td>
                            <td class="text-right">{{ number_format($line->payment['amount'], 2) }}</td>
                            <td></td>
                        </tr>
                    @endif
                @endforeach
            @endforeach

            @foreach($transport_fees as $tline)
                @php
                    $totalAmount += $tline->fees;
                    $totalDiscount += $tline->paid_discount;
                    $totalPaid += $tline->paid_amount;
                    $totalFine += $tline->paid_fine;
                    $totalBalance += $tline->balance;
                    $isOverdue = $tline->due_date
                        && $tline->due_date !== '0000-00-00'
                        && strtotime((string) $tline->due_date) < strtotime(date('Y-m-d'));
                @endphp
                <tr class="{{ $isOverdue ? 'danger' : 'dark-gray' }}">
                    <td>{{ __('system.transport_fees') }}</td>
                    <td>{{ $tline->month }}</td>
                    <td>{{ $tline->due_date ?: '—' }}</td>
                    <td><span class="label label-danger">{{ __('system.processing') }}</span></td>
                    <td class="text-right">
                        {{ number_format($tline->fees, 2) }}
                        @if($isOverdue && $tline->overdue_fine > 0)
                            <span class="text-danger"> + {{ number_format($tline->overdue_fine, 2) }}</span>
                        @endif
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ number_format($tline->paid_discount, 2) }}</td>
                    <td class="text-right">{{ number_format($tline->paid_fine, 2) }}</td>
                    <td class="text-right">{{ number_format($tline->paid_amount, 2) }}</td>
                    <td class="text-right">{{ $tline->balance > 0 ? number_format($tline->balance, 2) : '' }}</td>
                </tr>
                @if($tline->payment)
                    <tr class="white-td">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-right">↳</td>
                        <td>
                            {{ $tline->unique_id }}
                            @if($tline->payment['description'] === '')
                                <small class="text-danger d-block">{{ __('system.no_description') }}</small>
                            @else
                                <small class="d-block">{{ $tline->payment['description'] }}</small>
                            @endif
                        </td>
                        <td>{{ $tline->payment['payment_mode'] }}</td>
                        <td>{{ $tline->payment['date'] }}</td>
                        <td class="text-right">{{ number_format($tline->payment['amount_discount'], 2) }}</td>
                        <td class="text-right">{{ number_format($tline->payment['amount_fine'], 2) }}</td>
                        <td class="text-right">{{ number_format($tline->payment['amount'], 2) }}</td>
                        <td></td>
                    </tr>
                @endif
            @endforeach

            <tr class="box box-solid total-bg">
                <td></td>
                <td></td>
                <td></td>
                <td>{{ __('system.grand_total') }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ number_format($totalAmount, 2) }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right">{{ $currencySymbol }}{{ number_format($totalDiscount, 2) }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ number_format($totalFine, 2) }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ number_format($totalPaid, 2) }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ number_format($totalBalance, 2) }}</td>
            </tr>
            </tbody>
        </table>
    @endunless
</div>
