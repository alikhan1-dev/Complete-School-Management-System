<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $idcard->title }} — Preview</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; background: #f5f5f5; padding: 20px; }
        .toolbar { margin-bottom: 12px; }
        .wrap { max-width: 420px; margin: 0 auto; background: #fff; box-shadow: 0 0 8px rgba(0,0,0,.15); }
        .tcmybg {
            background: top center; background-size: contain; position: absolute;
            left: 0; bottom: 10px; width: 200px; height: 200px; margin: 0 auto; right: 0; opacity: .1;
        }
        .studenttop { background: {{ $idcard->header_color ?: '#595959' }}; padding: 2px; color: #fff; overflow: hidden; position: relative; z-index: 1; }
        .sttext1 { font-size: 20px; font-weight: bold; line-height: 28px; }
        .stlist, .vertlist { padding: 0; margin: 0; list-style: none; color: #000; }
        .stlist li, .vertlist li { text-align: left; display: inline-block; width: 100%; padding: 0 5px 4px; }
        .stlist li span, .vertlist li span { width: 65%; float: right; }
        .stimg { width: 80px; height: auto; margin-left: 10px; }
        .stimg img { width: 100%; height: auto; border-radius: 2px; display: block; }
        .staround { padding: 3px 10px 3px 0; position: relative; overflow: hidden; }
        .cardleft { width: 20%; float: left; }
        .cardright { width: 77%; float: right; }
        .signature { border: 1px solid #ddd; display: block; text-align: center; padding: 5px 20px; margin-top: 10px; }
        .principal { margin-top: -40px; margin-right: 10px; float: right; }
        .barcodeimg { display: block; margin-top: 5px; text-align: left; }
        .placeholder-photo {
            width: 80px; height: 90px; border: 1px dashed #999; display: flex; align-items: center;
            justify-content: center; font-size: 10px; color: #666; background: #fafafa;
        }
        @media print {
            .toolbar { display: none; }
            body { background: #fff; padding: 0; }
            .wrap { box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" onclick="window.print()">Print</button>
    <a href="{{ route('certificates.idcard_templates.index') }}">Back</a>
</div>

<div class="wrap">
@if((int) $idcard->enable_vertical_card === 1)
    <table cellpadding="0" cellspacing="0" width="100%" style="background: {{ $idcard->header_color ?: '#595959' }}; position: relative;">
        @if($backgroundUrl)
            <tr><td valign="top"><img src="{{ $backgroundUrl }}" class="tcmybg" alt=""></td></tr>
        @endif
        <tr>
            <td valign="top" style="text-align:center;color:#fff;padding:10px;">
                <div class="sttext1">
                    @if($logoUrl)<img src="{{ $logoUrl }}" width="30" height="30" alt="">@endif
                    {{ $idcard->school_name }}
                </div>
                <div>{{ $idcard->school_address }}</div>
            </td>
        </tr>
        <tr>
            <td valign="top" style="background:#fff;">
                <table cellpadding="0" cellspacing="0" width="100%" style="margin-top:-40px; position:relative; z-index:1;">
                    <tr>
                        <td valign="top" style="text-align:center;">
                            <div class="stimg center-block" style="margin:0 auto;">
                                <div class="placeholder-photo" style="margin:0 auto; border:3px solid {{ $idcard->header_color ?: '#595959' }};">Photo</div>
                            </div>
                            <h4 style="margin:10px 0 0; text-transform:uppercase; font-weight:bold;">Student Name</h4>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <table cellpadding="0" cellspacing="0" width="90%" align="center" style="background:#fff; padding:20px; display:block; width:90%; margin:0 auto;">
                    <tr>
                        <td valign="top">
                            <ul class="vertlist">
                                @if((int) $idcard->enable_admission_no === 1)<li>Admission No<span>123456789</span></li>@endif
                                @if((int) $idcard->enable_student_rollno === 1)<li>Roll No<span>1015</span></li>@endif
                                @if((int) $idcard->enable_class === 1)<li>Class<span>Class 6 - A (2018-19)</span></li>@endif
                                @if((int) $idcard->enable_student_house_name === 1)<li>House<span>Red House</span></li>@endif
                                @if((int) $idcard->enable_fathers_name === 1)<li>Father Name<span>S.Tudent Name</span></li>@endif
                                @if((int) $idcard->enable_mothers_name === 1)<li>Mother Name<span>S.Tudent Name</span></li>@endif
                                @if((int) $idcard->enable_address === 1)<li>Address<span>D.No.1 Street Name</span></li>@endif
                                @if((int) $idcard->enable_phone === 1)<li>Phone<span>1234567890</span></li>@endif
                                @if((int) $idcard->enable_dob === 1)<li>D.O.B.<span>25.06.2006</span></li>@endif
                                @if((int) $idcard->enable_blood_group === 1)<li>Blood Group<span>A+</span></li>@endif
                            </ul>
                            @if($signUrl)
                                <div class="signature"><img src="{{ $signUrl }}" width="200" height="24" alt=""></div>
                            @endif
                            @if((int) $idcard->enable_student_barcode === 1)
                                <div class="signature" style="color:#666; font-size:11px;">
                                    {{ strtoupper($scanCodeType) }} placeholder (generated on print)
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
                <div class="studenttop">
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
                        <div class="stimg"><div class="placeholder-photo">Photo</div></div>
                        @if((int) $idcard->enable_student_barcode === 1)
                            <div class="barcodeimg" style="width:90%; margin:6px auto 0; color:#666; font-size:10px; text-align:center;">
                                {{ strtoupper($scanCodeType) }}
                            </div>
                        @endif
                    </div>
                    <div class="cardright">
                        <ul class="stlist">
                            @if((int) $idcard->enable_student_name === 1)<li>Student Name<span>S.Tudent Name</span></li>@endif
                            @if((int) $idcard->enable_admission_no === 1)<li>Admission No<span>123456789</span></li>@endif
                            @if((int) $idcard->enable_student_rollno === 1)<li>Roll No<span>1015</span></li>@endif
                            @if((int) $idcard->enable_class === 1)<li>Class<span>Class 6 - A (2018-19)</span></li>@endif
                            @if((int) $idcard->enable_student_house_name === 1)<li>House<span>Red House</span></li>@endif
                            @if((int) $idcard->enable_fathers_name === 1)<li>Father Name<span>S.Tudent Name</span></li>@endif
                            @if((int) $idcard->enable_mothers_name === 1)<li>Mother Name<span>S.Tudent Name</span></li>@endif
                            @if((int) $idcard->enable_address === 1)<li>Address<span>D.No.1 Street Name</span></li>@endif
                            @if((int) $idcard->enable_phone === 1)<li>Phone<span>1234567890</span></li>@endif
                            @if((int) $idcard->enable_dob === 1)<li>D.O.B.<span>25.06.2006</span></li>@endif
                            @if((int) $idcard->enable_blood_group === 1)<li>Blood Group<span>A+</span></li>@endif
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
        @if($signUrl)
            <tr>
                <td valign="top" align="right" class="principal">
                    <img src="{{ $signUrl }}" width="66" height="40" alt="">
                </td>
            </tr>
        @endif
    </table>
@endif
</div>
</body>
</html>
