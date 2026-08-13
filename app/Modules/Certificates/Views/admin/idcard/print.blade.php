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
        .studenttop { padding: 2px; color: #fff; overflow: hidden; position: relative; z-index: 1; }
        .sttext1 { font-size: 16px; font-weight: bold; line-height: 24px; }
        .stlist, .vertlist { padding: 0; margin: 0; list-style: none; color: #000; }
        .stlist li, .vertlist li { text-align: left; display: inline-block; width: 100%; padding: 0 5px 3px; }
        .stlist li span, .vertlist li span { width: 60%; float: right; }
        .stimg { width: 80px; height: auto; margin-left: 8px; }
        .stimg img { width: 100%; height: auto; border-radius: 2px; display: block; }
        .staround { padding: 3px 10px 3px 0; position: relative; overflow: hidden; min-height: 110px; }
        .cardleft { width: 22%; float: left; }
        .cardright { width: 75%; float: right; }
        .signature { border: 1px solid #ddd; display: block; text-align: center; padding: 4px 10px; margin-top: 8px; }
        .principal { text-align: right; padding: 4px 10px 8px; }
        .barcodeimg { display: block; margin-top: 5px; text-align: center; }
        .page-break { page-break-after: always; }
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
                $student = $row['student'];
                $i = $index + 1;
            @endphp
            <td class="width32" valign="top">
                @if($vertical)
                    <table cellpadding="0" cellspacing="0" width="100%" style="background: {{ $idcard->header_color ?: '#595959' }};">
                        <tr>
                            <td valign="top" style="text-align:center;color:#fff;padding:8px;">
                                <div class="sttext1">
                                    @if($logoUrl)<img src="{{ $logoUrl }}" width="30" height="24" style="vertical-align:middle;" alt="">@endif
                                    {{ $idcard->school_name }}
                                </div>
                                <div>{{ $idcard->school_address }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="background:#fff;">
                                <table cellpadding="0" cellspacing="0" width="100%" style="margin-top:-40px; position:relative; z-index:1;">
                                    <tr>
                                        <td valign="top" align="center">
                                            <div class="stimg" style="margin:0 auto;">
                                                <img src="{{ $row['photoUrl'] }}" alt=""
                                                     style="border-radius:8px; border:3px solid {{ $idcard->header_color ?: '#595959' }};">
                                            </div>
                                            <h4 style="margin:8px 0 0; text-transform:uppercase; font-weight:bold;">{{ $row['fullName'] }}</h4>
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
                                                @if((int) $idcard->enable_admission_no === 1)
                                                    <li>Admission No<span>{{ $student->admission_no }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_student_rollno === 1)
                                                    <li>Roll No<span>{{ $student->roll_no }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_class === 1)
                                                    <li>Class<span>{{ $student->class }} - {{ $student->section }}@if($sessionName) ({{ $sessionName }})@endif</span></li>
                                                @endif
                                                @if((int) $idcard->enable_student_house_name === 1)
                                                    <li>House<span>{{ $student->house_name }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_fathers_name === 1)
                                                    <li>Father Name<span>{{ $student->father_name }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_mothers_name === 1)
                                                    <li>Mother Name<span>{{ $student->mother_name }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_address === 1)
                                                    <li>Address<span>{{ $student->current_address }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_phone === 1)
                                                    <li>Phone<span>{{ $student->mobileno }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_dob === 1)
                                                    <li>D.O.B.<span>{{ $row['dobFormatted'] }}</span></li>
                                                @endif
                                                @if((int) $idcard->enable_blood_group === 1)
                                                    <li>Blood Group<span>{{ $student->blood_group }}</span></li>
                                                @endif
                                            </ul>
                                            @if($signUrl)
                                                <div class="signature"><img src="{{ $signUrl }}" width="150" height="24" alt=""></div>
                                            @endif
                                            @if((int) $idcard->enable_student_barcode === 1 && $row['scanUrl'])
                                                <div class="signature"><img src="{{ $row['scanUrl'] }}" style="max-width:65px; margin:0 auto; height:auto; display:block;" alt=""></div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                @else
                    <table cellpadding="0" cellspacing="0" width="100%" class="tc-container" style="background:#efefef; position:relative;">
                        @if($backgroundUrl)
                            <tr><td valign="top"><img src="{{ $backgroundUrl }}" class="tcmybg" alt=""></td></tr>
                        @endif
                        <tr>
                            <td valign="top">
                                <div class="studenttop" style="background: {{ $idcard->header_color ?: '#595959' }};">
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
                            <td valign="top" style="color:#fff; font-size:16px; padding:4px 0; position:relative; z-index:1; background:{{ $idcard->header_color ?: '#595959' }}; text-transform:uppercase;">
                                {{ $idcard->title }}
                            </td>
                        </tr>
                        <tr>
                            <td valign="top">
                                <div class="staround">
                                    <div class="cardleft">
                                        <div class="stimg"><img src="{{ $row['photoUrl'] }}" alt=""></div>
                                        @if((int) $idcard->enable_student_barcode === 1 && $row['scanUrl'])
                                            <div class="barcodeimg" style="width:90%; margin:6px auto 0;">
                                                <img src="{{ $row['scanUrl'] }}" style="max-width:65px; margin:0 auto; height:auto;" alt="">
                                            </div>
                                        @endif
                                    </div>
                                    <div class="cardright">
                                        <ul class="stlist">
                                            @if((int) $idcard->enable_student_name === 1)
                                                <li>Student Name<span>{{ $row['fullName'] }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_admission_no === 1)
                                                <li>Admission No<span>{{ $student->admission_no }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_student_rollno === 1)
                                                <li>Roll No<span>{{ $student->roll_no }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_class === 1)
                                                <li>Class<span>{{ $student->class }} - {{ $student->section }}@if($sessionName) ({{ $sessionName }})@endif</span></li>
                                            @endif
                                            @if((int) $idcard->enable_student_house_name === 1)
                                                <li>House<span>{{ $student->house_name }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_fathers_name === 1)
                                                <li>Father Name<span>{{ $student->father_name }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_mothers_name === 1)
                                                <li>Mother Name<span>{{ $student->mother_name }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_address === 1)
                                                <li>Address<span>{{ $student->current_address }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_phone === 1)
                                                <li>Phone<span>{{ $student->mobileno }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_dob === 1)
                                                <li>D.O.B.<span>{{ $row['dobFormatted'] }}</span></li>
                                            @endif
                                            @if((int) $idcard->enable_blood_group === 1)
                                                <li>Blood Group<span>{{ $student->blood_group }}</span></li>
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
