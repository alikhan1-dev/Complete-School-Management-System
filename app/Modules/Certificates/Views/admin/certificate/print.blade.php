<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_name }}</title>
    <style>
        * { padding: 0; margin: 0; }
        body { font-family: Arial, sans-serif; }
        .toolbar { padding: 10px 16px; background: #f5f5f5; border-bottom: 1px solid #ddd; }
        .page {
            position: relative;
            text-align: center;
            font-family: Arial, sans-serif;
            page-break-after: always;
            min-height: 100vh;
            overflow: hidden;
        }
        .page:last-child { page-break-after: auto; }
        .page img.bg {
            width: 100%;
            height: 100vh;
            object-fit: cover;
            display: block;
        }
        .page table.layout {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            margin-left: auto;
            margin-right: auto;
            border-collapse: collapse;
            width: {{ max(400, (int) $certificate->content_width) }}px;
        }
        .page table.layout td { vertical-align: top; }
        .cert-body {
            font-size: 14px;
            line-height: 24px;
            text-align: center;
        }
        @media print {
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" onclick="window.print()">Print</button>
    <button type="button" onclick="window.close()">Close</button>
</div>

@foreach($rows as $row)
    <div class="page">
        @if($backgroundUrl)
            <img class="bg" src="{{ $backgroundUrl }}" alt="">
        @endif
        <table class="layout" cellspacing="0" cellpadding="0">
            <tr>
                <td style="position: absolute; right: 0;">
                    @if((int) $certificate->enable_student_image === 1 && $row['photoUrl'])
                        <img
                            style="position: relative; top: {{ (int) $certificate->enable_image_height }}px;"
                            src="{{ $row['photoUrl'] }}"
                            width="100"
                            height="auto"
                            alt=""
                        >
                    @endif
                </td>
            </tr>
            <tr>
                <td valign="top" style="text-align:left; position: relative; top: {{ (int) $certificate->header_height }}px;">{!! $certificate->left_header !!}</td>
                <td valign="top" style="text-align:center; position: relative; top: {{ (int) $certificate->header_height }}px;">{!! $certificate->center_header !!}</td>
                <td valign="top" style="text-align:right; position: relative; top: {{ (int) $certificate->header_height }}px;">{!! $certificate->right_header !!}</td>
            </tr>
            <tr>
                <td colspan="3" valign="top" style="position: relative; top: {{ (int) $certificate->content_height }}px;">
                    <p class="cert-body">{!! $row['body'] !!}</p>
                </td>
            </tr>
            <tr>
                <td valign="top" style="text-align:left; position: relative; top: {{ (int) $certificate->footer_height }}px;">{!! $certificate->left_footer !!}</td>
                <td valign="top" style="text-align:center; position: relative; top: {{ (int) $certificate->footer_height }}px;">{!! $certificate->center_footer !!}</td>
                <td valign="top" style="text-align:right; position: relative; top: {{ (int) $certificate->footer_height }}px;">{!! $certificate->right_footer !!}</td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
