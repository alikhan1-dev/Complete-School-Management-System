{{-- CI print/printFeesByName (+ transport variant fields via FeeReceiptService) --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('system.fees_receipt') }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 16px; }
        .invoice { margin-bottom: 24px; }
        .header img { height: 100px; width: 100%; object-fit: contain; }
        .copy-title { text-align: center; font-weight: bold; margin: 8px 0 12px; }
        .meta { width: 100%; margin-bottom: 8px; }
        .meta td { vertical-align: top; padding: 2px 0; }
        .meta .right { text-align: right; }
        table.lines { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 8px; }
        table.lines th, table.lines td { border: 1px solid #ccc; padding: 6px 8px; }
        table.lines th { background: #f5f5f5; text-align: left; }
        .text-right { text-align: right; }
        .note, .footer { margin-top: 12px; }
        .page-break { display: block; page-break-before: always; }
        hr.sep { width: 100%; margin: 24px 0; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
@php
    $collectedBy = '';
    if (! empty($payment->received_by) || ! empty($payment->collected_by)) {
        $collectedBy = (string) ($payment->collected_by ?? '');
    }
@endphp

<div class="no-print" style="margin-bottom:12px;">
    <button type="button" onclick="window.print()">{{ __('system.print') }}</button>
</div>

@foreach($copies as $index => $copyLabel)
    @if($index > 0)
        @if($singlePagePrint)
            <br><br><hr class="sep">
        @else
            <div class="page-break"></div>
        @endif
    @endif

    <div class="invoice">
        @if($headerUrl)
            <div class="header">
                <img src="{{ $headerUrl }}" alt="">
            </div>
        @endif

        <div class="copy-title">{{ $copyLabel }}</div>

        <table class="meta">
            <tr>
                <td>
                    <strong>{{ $studentName }}</strong> ({{ $feeList->admission_no }})<br>
                    {{ __('system.father_name') }}: {{ $student->father_name }}<br>
                    {{ __('system.class') }}: {{ $feeList->class }} ({{ $feeList->section }})
                </td>
                <td class="right">
                    <strong>{{ __('system.date') }}: {{ $printDate }}</strong><br>
                    <strong>{{ __('system.payment_id') }}: {{ $feeList->id }}/{{ $sub_invoice_id }}</strong><br>
                    <strong>{{ __('system.collected_by') }}: {{ $collectedBy }}</strong>
                </td>
            </tr>
        </table>

        <table class="lines">
            <thead>
            <tr>
                <th>{{ __('system.date') }}</th>
                <th>{{ __('system.fees') }}</th>
                <th>{{ __('system.mode') }}</th>
                <th class="text-right">{{ __('system.amount') }}</th>
                <th class="text-right">{{ __('system.discount') }}</th>
                <th class="text-right">{{ __('system.fine') }}</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $paymentDate }}</td>
                <td>{{ $feeLineLabel }}</td>
                <td>{{ $paymentModeLabel }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($payment->amount ?? 0) }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($payment->amount_discount ?? 0) }}</td>
                <td class="text-right">{{ $currencySymbol }}{{ $formatAmount($payment->amount_fine ?? 0) }}</td>
            </tr>
            </tbody>
        </table>

        <div class="note">
            {{ __('system.note') }}: {{ $payment->description ?? '' }}
        </div>

        @if($footerHtml !== '')
            <div class="footer">{!! $footerHtml !!}</div>
        @endif
    </div>
@endforeach
</body>
</html>
