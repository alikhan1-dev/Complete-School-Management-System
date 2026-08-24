{{-- CI print/print_fee_receipt_pdf (+ transport variant) for download-receipt token --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('system.fees_receipt') }}</title>
</head>
<body>
@foreach($copies as $index => $copyLabel)
    @if($index > 0)
        @if(!$singlePagePrint)
            <div class="page-break"></div>
        @endif
    @endif

    <div class="container" style="margin:5%;">
        @if($headerUrl)
            <div class="header">
                <img src="{{ $headerUrl }}" style="height:100px;width:100%;" alt="">
            </div>
        @endif

        <h2 style="border-top:1px solid gray;border-bottom:1px solid gray;text-align:center;font-size:13px;padding:5px 0;margin:8px 0 5px;">
            {{ $copyLabel }}
        </h2>

        <table width="100%" style="font-size:12px;">
            <tr>
                <td width="50%">
                    <strong>{{ $studentName }}</strong> ({{ $feeList->admission_no }})<br>
                    {{ __('system.father_name') }}: {{ $feeList->father_name }}<br>
                    {{ __('system.class') }}: {{ $feeList->class }} ({{ $feeList->section }})
                </td>
                <td width="50%" style="text-align:right;vertical-align:top;">
                    <strong>{{ __('system.date') }}: {{ $printDate }}</strong>
                </td>
            </tr>
        </table>

        <hr style="margin:0;">

        <table style="font-size:12px;margin-top:5px;width:100%;">
            <thead>
            <tr>
                <th style="text-align:left;">{{ __('system.fees') }}</th>
                <th>{{ __('system.due_date') }}</th>
                <th>{{ __('system.status') }}</th>
                <th style="text-align:right;">{{ __('system.amount') }}</th>
                <th style="text-align:center;">{{ __('system.payment_id') }}</th>
                <th style="text-align:center;">{{ __('system.mode') }}</th>
                <th>{{ __('system.date') }}</th>
                <th style="text-align:right;">{{ __('system.paid') }}</th>
                <th style="text-align:right;">{{ __('system.fine') }}</th>
                <th style="text-align:right;">{{ __('system.discount') }}</th>
                <th style="text-align:right;">{{ __('system.balance') }}</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $line['fee_line_label'] }}</td>
                <td>{{ $line['due_date'] }}</td>
                <td>{{ $groupStatusLabel($line['status']) }}</td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ $formatAmount($line['due_amount']) }}</td>
                <td colspan="3"></td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ $formatAmount($line['paid_amount']) }}</td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ $formatAmount($line['paid_fine']) }}</td>
                <td style="text-align:right;">{{ $currencySymbol }}{{ $formatAmount($line['paid_discount']) }}</td>
                <td style="text-align:right;">
                    @if($line['balance'] > 0)
                        {{ $currencySymbol }}{{ $formatAmount($line['balance']) }}
                    @endif
                </td>
            </tr>
            @foreach($line['payments'] as $payment)
                <tr>
                    <td colspan="4"></td>
                    <td style="text-align:center;">{{ $payment['payment_id'] }}</td>
                    <td style="text-align:center;">{{ $payment['payment_mode'] }}</td>
                    <td>{{ $payment['date'] }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ $formatAmount($payment['amount']) }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ $formatAmount($payment['amount_fine']) }}</td>
                    <td style="text-align:right;">{{ $currencySymbol }}{{ $formatAmount($payment['amount_discount']) }}</td>
                    <td></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        @if($footerHtml !== '')
            <div style="margin-top:12px;">{!! $footerHtml !!}</div>
        @endif
    </div>
@endforeach
</body>
</html>
