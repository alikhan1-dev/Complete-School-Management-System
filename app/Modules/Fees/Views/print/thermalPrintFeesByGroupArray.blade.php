{{-- CI print/thermalPrintFeesByGroupArray --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('system.fees_receipt') }}</title>
    @include('fees::print._thermalStyles')
</head>
<body>
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
                    {{ __('system.name') }}: {{ $studentName }} ({{ $headerFeeList->admission_no }})
                </td>
            </tr>
            <tr>
                <td colspan="2" style="font-weight:bold;">
                    {{ __('system.class') }}: {{ $headerFeeList->class }} ({{ $headerFeeList->section }})
                </td>
            </tr>
        </table>

        @forelse($lines as $line)
            <table>
                <tr><td class="title-around-span"><span>{{ __('system.fees') }}</span></td></tr>
            </table>

            <table>
                <tr>
                    <td align="center" style="font-weight:bold;">{{ $line['fee_line_label'] }}</td>
                </tr>
            </table>

            <table style="border-top: 2px #000 dashed; line-height: 11px; padding-top: 2px;">
                <tr>
                    <th style="text-align:left;">{{ __('system.amount') }}({{ $currencySymbol }})</th>
                    <th style="text-align:center;">{{ __('system.paid') }}({{ $currencySymbol }})</th>
                    <th style="text-align:right;">{{ __('system.balance') }}({{ $currencySymbol }})</th>
                </tr>
                <tr>
                    <td style="text-align:left;">{{ $formatAmount($line['due_amount']) }}</td>
                    <td style="text-align:center;">{{ $formatAmount($line['paid_amount']) }}</td>
                    <td style="text-align:right;">
                        @if($line['balance'] > 0)
                            {{ $formatAmount($line['balance']) }}
                        @endif
                    </td>
                </tr>
            </table>

            <table style="line-height: 11px;">
                <tr><td class="title-around-span" colspan="5"><span>{{ __('system.partial_payment') }}</span></td></tr>
                <tr>
                    <th width="25%" style="text-align:left">{{ __('system.date') }}</th>
                    <th width="20%" style="text-align:center">{{ __('system.pay_id') }}</th>
                    <th width="20%" style="text-align:center">{{ __('system.discount') }}({{ $currencySymbol }})</th>
                    <th width="20%" style="text-align:center">{{ __('system.fine') }}({{ $currencySymbol }})</th>
                    <th width="15%" style="text-align:right">{{ __('system.paid') }}({{ $currencySymbol }})</th>
                </tr>
                @foreach($line['payments'] as $payment)
                    <tr>
                        <td style="text-align:left">{{ $payment['date'] }}</td>
                        <td style="text-align:center">{{ $payment['payment_id'] }}</td>
                        <td style="text-align:center">{{ $formatAmount($payment['amount_discount']) }}</td>
                        <td style="text-align:center">{{ $formatAmount($payment['amount_fine']) }}</td>
                        <td style="text-align:right">{{ $formatAmount($payment['amount']) }}</td>
                    </tr>
                @endforeach
            </table>
        @empty
            <table>
                <tr>
                    <td class="text-danger text-center">{{ __('system.no_transaction_found') }}</td>
                </tr>
            </table>
        @endforelse

        <p style="padding-top:3px; font-size:8pt; border-top: 1px #000 solid; font-style: italic;">
            {{ $thermal_print['footer_text'] ?? '' }}
        </p>
    </div>
@endforeach
</body>
</html>
