{{-- CI print/thermalPrintFeesByName (+ transport via same layout) --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('system.fees_receipt') }}</title>
    @include('fees::print._thermalStyles')
</head>
<body>
@php
    $collectedBy = '';
    if (! empty($payment->received_by) || ! empty($payment->collected_by)) {
        $collectedBy = (string) ($payment->collected_by ?? '');
    }
    $amountDue = (float) ($feeList->amount ?? $feeList->fees ?? 0);
    if (! empty($feeList->is_system) && isset($feeList->student_fees_master_amount)) {
        $amountDue = (float) $feeList->student_fees_master_amount;
    }
    $paid = (float) ($payment->amount ?? 0);
    $discount = (float) ($payment->amount_discount ?? 0);
    $fine = (float) ($payment->amount_fine ?? 0);
    // CI thermal name receipt sums all deposit lines for amount/paid/balance display.
    $sumPaid = 0.0;
    $sumDiscount = 0.0;
    $detail = $feeList->amount_detail ?? null;
    if (is_string($detail) && $detail !== '' && $detail !== '0') {
        $decoded = json_decode($detail);
        if (is_object($decoded)) {
            foreach ($decoded as $entry) {
                $sumPaid += (float) ($entry->amount ?? 0);
                $sumDiscount += (float) ($entry->amount_discount ?? 0);
            }
        }
    }
    $balance = $amountDue - ($sumPaid + $sumDiscount);
@endphp

<div class="no-print" style="margin-bottom:12px;">
    <button type="button" onclick="window.print()">{{ __('system.print') }}</button>
</div>

@foreach($copies as $index => $copyLabel)
    @if($index > 0)
        @if($singlePagePrint)
            <br><br>
        @else
            <div class="page-break"></div>
        @endif
    @endif

    <div style="margin: 0 auto; padding: 0;">
        <h1 style="text-align: center; padding-bottom: 5px;">{{ $thermal_print['school_name'] ?? '' }}</h1>
        <p style="text-align:center; font-weight:bold;">{{ $thermal_print['address'] ?? '' }}</p>

        <h2 style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:center; font-size: 12pt; padding: 5px 0; margin: 8px 0 5px;">
            {{ $copyLabel }}
        </h2>

        <table>
            <tr>
                <td colspan="2" style="font-weight:bold;">
                    {{ __('system.name') }}: {{ $studentName }} ({{ $feeList->admission_no }})
                </td>
            </tr>
            <tr>
                <td colspan="2" style="font-weight:bold;">
                    {{ __('system.class') }}: {{ $feeList->class }} ({{ $feeList->section }})
                </td>
            </tr>
            <tr><td class="title-around-span"><span>{{ __('system.fees') }}</span></td></tr>
        </table>

        <table>
            <tr>
                <td align="center" style="font-weight:bold;">{{ $feeLineLabel }}</td>
            </tr>
        </table>

        <table style="border-top: 2px #000 dashed; line-height: 11px; padding-top: 2px;">
            <tr>
                <th style="text-align:left;">{{ __('system.amount') }}({{ $currencySymbol }})</th>
                <th style="text-align:center;">{{ __('system.paid') }}({{ $currencySymbol }})</th>
                <th style="text-align:right;">{{ __('system.balance') }}({{ $currencySymbol }})</th>
            </tr>
            <tr>
                <td style="text-align:left;">{{ $formatAmount($amountDue) }}</td>
                <td style="text-align:center;">{{ $formatAmount($sumPaid) }}</td>
                <td style="text-align:right;">{{ $formatAmount($balance) }}</td>
            </tr>
        </table>

        <table style="line-height: 11px;">
            <tr><td class="title-around-span" colspan="5"><span>{{ __('system.partial_payment') }}</span></td></tr>
            <tr>
                <th width="20%">{{ __('system.date') }}</th>
                <th width="20%" style="text-align:right">{{ __('system.pay_id') }}</th>
                <th width="20%" style="text-align:right">{{ __('system.discount') }}</th>
                <th width="20%" style="text-align:right">{{ __('system.fine') }}({{ $currencySymbol }})</th>
                <th width="20%" style="text-align:right">{{ __('system.paid') }}({{ $currencySymbol }})</th>
            </tr>
            <tr>
                <td>{{ $paymentDate }}</td>
                <td style="text-align:right">{{ $feeList->id }}/{{ $sub_invoice_id }}</td>
                <td style="text-align:right">{{ $formatAmount($discount) }}</td>
                <td style="text-align:right">{{ $formatAmount($fine) }}</td>
                <td style="text-align:right">{{ $formatAmount($paid) }}</td>
            </tr>
        </table>

        <table style="border-top: 2px #000 dashed; line-height: 11px; padding-top: 10px;">
            <tr>
                <td style="text-align:left;">{{ __('system.mode') }}: {{ $paymentModeLabel }}</td>
                <td style="text-align:right;">{{ __('system.collected_by') }}: {{ $collectedBy }}</td>
            </tr>
        </table>

        <p style="padding-top:3px; font-size:8pt; border-top: 1px #000 solid; font-style: italic;">
            {{ $thermal_print['footer_text'] ?? '' }}
        </p>
    </div>
@endforeach
</body>
</html>
