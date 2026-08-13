<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $idcard->title }}</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
        .toolbar { padding: 10px 16px; background: #f5f5f5; border-bottom: 1px solid #ddd; }
        .cards { width: 100%; border-collapse: collapse; }
        .cards td.width32 { width: 32%; vertical-align: top; padding: 3px; }
        .tcmybg {
            background: top center; background-size: contain; position: absolute;
            left: 0; bottom: 10px; width: 200px; height: 200px; margin: 0 auto; right: 0; opacity: .15;
        }
        .studenttop { padding: 2px; color: #fff; overflow: visible; position: relative; z-index: 1; }
        .sttext1 { font-size: 16px; font-weight: bold; line-height: 24px; }
        .stlist, .vertlist { padding: 0; margin: 0; list-style: none; color: #000; }
        .stlist li, .vertlist li { text-align: left; display: inline-block; width: 100%; padding: 0 5px 3px; }
        .stlist li span, .vertlist li span { width: 60%; float: right; }
        .stimg { width: 80px; height: auto; margin: 0 auto; }
        .stimg img { width: 100%; height: auto; border-radius: 2px; display: block; }
        .staround { padding: 3px 10px 3px 0; position: relative; overflow: visible; min-height: 110px; }
        .cardleft { width: 22%; float: left; }
        .cardright { width: 75%; float: right; }
        .signature { border: 1px solid #ddd; display: block; text-align: center; padding: 4px 10px; margin-top: 8px; }
        .principal { text-align: right; padding: 4px 10px 8px; }
        .barcodeimg { display: block; margin-top: 5px; text-align: center; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" onclick="window.print()">Print</button>
    <button type="button" onclick="window.close()">Close</button>
</div>

@php $vertical = (int) $idcard->enable_vertical_card === 1; @endphp

<table class="cards" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        @foreach($rows as $index => $row)
            @php
                $staff = $row['staff'];
                $i = $index + 1;
            @endphp
            <td class="width32" valign="top">
                @if($vertical)
                    <table cellpadding="0" cellspacing="0" width="100%" style="background: {{ $idcard->header_color ?: '#9b1818' }};">
                        <tr>
                            <td valign="top" style="text-align:center;color:#fff;padding:8px;">
                                <div class="sttext1">
                                    @if($logoUrl)<img src="{{ $logoUrl }}" width="30" height="30" style="vertical-align:middle;" alt="">@endif
                                    {{ $idcard->school_name }}
                                </div>
                                <div>{{ $idcard->school_address }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="background:#fff;">
                                <table cellpadding="0" cellspacing="0" width="100%" style="margin-top:-45px; position:relative; z-index:1;">
                                    <tr>
                                        <td valign="top" align="center">
                                            <div class="stimg">
                                                <img src="{{ $row['photoUrl'] }}" alt=""
                                                     style="border-radius:8px; border:3px solid {{ $idcard->header_color ?: '#9b1818' }};">
                                            </div>
                                            <h4 style="margin:10px 0 0; text-transform:uppercase; font-weight:bold;">{{ $row['fullName'] }}</h4>
                                            @if((int) $idcard->enable_designation === 1)
                                                <p style="font-size:15px; color:#9b1818;">{{ $staff->designation }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top">
                                <table cellpadding="0" cellspacing="0" width="90%" align="center"
                                       style="background:#fff; padding:8px; display:block; width:90%; margin:0 auto;">
                                    <tr>
                                        <td valign="top">
                                            <ul class="vertlist">
                                                @if((int) $idcard->enable_staff_id === 1)
                                                    <li>Staff ID<span>{{ $staff->employee_id }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_staff_department === 1)
                                                    <li>Department<span>{{ $staff->department }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_fathers_name === 1)
                                                    <li>Father Name<span>{{ $staff->father_name }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_mothers_name === 1)
                                                    <li>Mother Name<span>{{ $staff->mother_name }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_date_of_joining === 1)
                                                    <li>Date of Joining<span>{{ $row['joiningFormatted'] }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_permanent_address === 1)
                                                    <li>Address<span>{{ $staff->local_address }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_staff_phone === 1)
                                                    <li>Phone<span>{{ $staff->contact_no }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_staff_dob === 1)
                                                    <li>Date of Birth<span>{{ $row['dobFormatted'] }}</span></li>
                                                @endif
                                            </ul>
                                            @if($signUrl)
                                                <div class="signature"><img src="{{ $signUrl }}" width="150" height="24" alt=""></div>
                                            @endif
                                            @if((int) $idcard->enable_staff_barcode === 1 && $row['scanUrl'])
                                                <div class="signature">
                                                    <img src="{{ $row['scanUrl'] }}" style="max-width:80px; max-height:80px;" alt="">
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                @else
                    <table cellpadding="0" cellspacing="0" width="100%" style="background:#efefef; position:relative;">
                        @if($backgroundUrl)
                            <tr><td valign="top"><img src="{{ $backgroundUrl }}" class="tcmybg" alt=""></td></tr>
                        @endif
                        <tr>
                            <td valign="top">
                                <div class="studenttop" style="background: {{ $idcard->header_color ?: '#9b1818' }};">
                                    <div class="sttext1">
                                        @if($logoUrl)<img src="{{ $logoUrl }}" width="30" height="30" alt="">@endif
                                        {{ $idcard->school_name }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" align="center" style="padding:4px 0; position:relative; z-index:1;">
                                <p>{{ $idcard->school_address }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="color:#fff; font-size:16px; text-align:center; padding:4px 0; position:relative; z-index:1; background:{{ $idcard->header_color ?: '#9b1818' }}; text-transform:uppercase;">
                                {{ $idcard->title }}
                            </td>
                        </tr>
                        <tr>
                            <td valign="top">
                                <div class="staround">
                                    <div class="cardleft">
                                        <div class="stimg"><img src="{{ $row['photoUrl'] }}" alt=""></div>
                                        @if((int) $idcard->enable_staff_barcode === 1 && $row['scanUrl'])
                                            <div class="barcodeimg" style="width:90%; margin:6px auto 0;">
                                                <img src="{{ $row['scanUrl'] }}" style="max-width:65px; margin:0 auto; height:auto;" alt="">
                                            </div>
                                        @endif
                                    </div>
                                    <div class="cardright">
                                        <ul class="stlist">
                                            @if((int) $idcard->enable_name === 1)
                                                <li>Staff Name<span>{{ $row['fullName'] }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_staff_id === 1)
                                                <li>Staff ID<span>{{ $staff->employee_id }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_designation === 1)
                                                <li>Designation<span>{{ $staff->designation }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_staff_department === 1)
                                                <li>Department<span>{{ $staff->department }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_fathers_name === 1)
                                                <li>Father Name<span>{{ $staff->father_name }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_mothers_name === 1)
                                                <li>Mother Name<span>{{ $staff->mother_name }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_date_of_joining === 1)
                                                <li>Date of Joining<span>{{ $row['joiningFormatted'] }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_permanent_address === 1)
                                                <li>Address<span>{{ $staff->local_address }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_staff_phone === 1)
                                                <li>Phone<span>{{ $staff->contact_no }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_staff_dob === 1)
                                                <li>Date of Birth<span>{{ $row['dobFormatted'] }}</span></li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @if($signUrl)
                            <tr>
                                <td valign="top" class="principal">
                                    <img src="{{ $signUrl }}" width="66" height="40" alt="">
                                </td>
                            </tr>
                        @endif
                    </table>
                @endif
            </td>
            @if($i % 3 === 0 && ! $loop->last)
                </tr><tr>
            @endif
        @endforeach
    </tr>
</table>
</body>
</html>
