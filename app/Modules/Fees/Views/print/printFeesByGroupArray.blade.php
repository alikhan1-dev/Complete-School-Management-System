{{-- CI print/printFeesByGroupArray --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('system.fees_receipt') }}</title>
    @include('fees::print._groupReceiptStyles')
</head>
<body>
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
            <div class="header"><img src="{{ $headerUrl }}" alt=""></div>
        @endif

        <div class="copy-title">{{ $copyLabel }}</div>

        <table class="meta">
            <tr>
                <td>
                    <strong>{{ $studentName }}</strong> ({{ $headerFeeList->admission_no }})<br>
                    {{ __('system.father_name') }}: {{ $headerFeeList->father_name }}<br>
                    {{ __('system.class') }}: {{ $headerFeeList->class }} ({{ $headerFeeList->section }})
                </td>
                <td class="right">
                    <strong>{{ __('system.date') }}: {{ $printDate }}</strong>
                </td>
            </tr>
        </table>

        @include('fees::print._groupReceiptTable', ['lines' => $lines])

        @if($footerHtml !== '')
            <div class="footer">{!! $footerHtml !!}</div>
        @endif
    </div>
@endforeach
</body>
</html>
