{{-- mPDF-oriented TC body (CI print_transfer_certificate HTML subset). --}}
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<div style="width:100%;">
    @if($headerUrl)
        <div style="text-align:center;margin-bottom:8px;">
            <img src="{{ $headerUrl }}" style="width:100%;height:90px;">
        </div>
    @endif

    <h2 style="text-align:center;margin:10px 0;">
        Transfer Certificate
        @if($isRegenerate)
            <br><b>[Reissue]</b>
        @endif
    </h2>

    @if($showTcNo)
        <table width="100%" style="border:none;margin-bottom:10px;">
            <tr>
                <td style="border:none;text-align:left;">
                    @if($affiliationNo !== '')
                        <h4 style="margin:0;">Affiliation No : {{ $affiliationNo }}</h4>
                    @endif
                </td>
                <td style="border:none;text-align:right;">
                    <h4 style="margin:0;">TC No : {{ $tcNo }}</h4>
                </td>
            </tr>
        </table>
    @endif

    <table class="denifittable" width="100%" cellspacing="0" cellpadding="4">
        <tr>
            <td width="30" style="text-align:center;">1</td>
            <td width="38%"><strong>Name</strong></td>
            <td>{{ $studentName }}</td>
        </tr>
        @foreach($fieldRows as $index => $row)
            <tr>
                <td style="text-align:center;">{{ $index + 2 }}</td>
                <td><strong>{{ $row['label'] }}</strong></td>
                <td>
                    @if(!empty($row['html']))
                        {!! $row['value'] !!}
                    @else
                        {{ $row['value'] }}
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <table width="100%" style="border:none;margin-top:28px;">
        <tr>
            <td width="33%" style="border:none;vertical-align:bottom;">
                @if($classTeacherSignatureUrl)
                    <img src="{{ $classTeacherSignatureUrl }}" height="60"><br>
                    <strong>Class Teacher Signature</strong>
                @endif
            </td>
            <td width="33%" style="border:none;text-align:center;vertical-align:bottom;">
                @if($checkedByUrl)
                    <img src="{{ $checkedByUrl }}" height="60"><br>
                    <strong>Checked By</strong>
                @endif
            </td>
            <td width="33%" style="border:none;text-align:right;vertical-align:bottom;">
                @if($principalSignatureUrl)
                    <img src="{{ $principalSignatureUrl }}" height="60"><br>
                    <strong>Principal Signature</strong>
                @endif
            </td>
        </tr>
    </table>

    @if($footerContent !== '')
        <div style="margin-top:18px;font-size:11px;">
            <b>{!! $footerContent !!}</b>
        </div>
    @endif
</div>
</body>
</html>
