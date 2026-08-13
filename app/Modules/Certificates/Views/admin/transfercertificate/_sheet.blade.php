{{-- Shared TC sheet used by print (standalone) and verify (embedded). --}}
<div class="tc-sheet" style="width:100%;max-width:900px;margin:0 auto;padding:16px 20px 24px;box-sizing:border-box;font-family:Arial,Helvetica,sans-serif;color:#222;">
    @if($headerUrl)
        <img src="{{ $headerUrl }}" alt="Header" style="width:100%;max-height:120px;object-fit:contain;display:block;">
    @endif

    <h2 style="text-align:center;margin:16px 0;">
        Transfer Certificate
        @if($isRegenerate)
            <br><small><b>[Reissue]</b></small>
        @endif
    </h2>

    @if($showTcNo)
        <table style="width:100%;margin-bottom:12px;">
            <tr>
                <td style="vertical-align:top;">
                    @if($affiliationNo !== '')
                        <h4 style="margin:0;">Affiliation No : {{ $affiliationNo }}</h4>
                    @endif
                </td>
                <td style="text-align:right;vertical-align:top;">
                    <h4 style="margin:0;">TC No : {{ $tcNo }}</h4>
                </td>
            </tr>
        </table>
    @endif

    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="border:1px solid #333;padding:6px 8px;width:36px;text-align:center;">1</td>
            <td style="border:1px solid #333;padding:6px 8px;width:38%;font-weight:bold;">Name</td>
            <td style="border:1px solid #333;padding:6px 8px;">{{ $studentName }}</td>
        </tr>
        @foreach($fieldRows as $index => $row)
            <tr>
                <td style="border:1px solid #333;padding:6px 8px;text-align:center;">{{ $index + 2 }}</td>
                <td style="border:1px solid #333;padding:6px 8px;font-weight:bold;">{{ $row['label'] }}</td>
                <td style="border:1px solid #333;padding:6px 8px;">
                    @if(!empty($row['html']))
                        {!! $row['value'] !!}
                    @else
                        {{ $row['value'] }}
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <table style="width:100%;margin-top:28px;border-collapse:collapse;">
        <tr>
            <td style="width:33%;vertical-align:bottom;padding-top:24px;">
                @if($classTeacherSignatureUrl)
                    <img src="{{ $classTeacherSignatureUrl }}" alt="" style="height:60px;max-width:100%;"><br>
                    <strong>Class Teacher Signature</strong>
                @endif
            </td>
            <td style="width:33%;vertical-align:bottom;padding-top:24px;text-align:center;">
                @if($checkedByUrl)
                    <img src="{{ $checkedByUrl }}" alt="" style="height:60px;max-width:100%;"><br>
                    <strong>Checked By</strong>
                @endif
            </td>
            <td style="width:33%;vertical-align:bottom;padding-top:24px;text-align:right;">
                @if($principalSignatureUrl)
                    <img src="{{ $principalSignatureUrl }}" alt="" style="height:60px;max-width:100%;"><br>
                    <strong>Principal Signature</strong>
                @endif
            </td>
        </tr>
    </table>

    @if($footerContent !== '')
        <div style="margin-top:20px;font-size:12px;">
            <b>{!! $footerContent !!}</b>
        </div>
    @endif
</div>
