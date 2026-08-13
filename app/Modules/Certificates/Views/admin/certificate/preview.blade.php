<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_name }} — Preview</title>
    <style>
        body { margin: 0; padding: 20px; font-family: Arial, sans-serif; background: #f5f5f5; }
        .toolbar { margin-bottom: 12px; }
        .sheet {
            position: relative;
            margin: 0 auto;
            background: #fff;
            min-height: 600px;
            box-shadow: 0 0 8px rgba(0,0,0,.15);
            overflow: hidden;
            width: {{ max(400, (int) $certificate->content_width) }}px;
        }
        .sheet img.bg {
            position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0;
        }
        .sheet .content { position: relative; z-index: 1; padding: 20px; }
        table.layout { width: 100%; border-collapse: collapse; }
        .photo { width: 100px; height: auto; }
        @media print {
            .toolbar { display: none; }
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button onclick="window.print()">Print</button>
    <a href="{{ route('certificates.templates.index') }}">Back</a>
</div>
<div class="sheet">
    @if($backgroundUrl)
        <img class="bg" src="{{ $backgroundUrl }}" alt="">
    @endif
    <div class="content">
        <table class="layout">
            @if((int) $certificate->enable_student_image === 1)
                <tr>
                    <td colspan="3" style="text-align:center; position:relative; top: {{ (int) $certificate->enable_image_height }}px;">
                        <div class="photo" style="margin:0 auto; border:1px dashed #999; padding:20px;">Student Photo</div>
                    </td>
                </tr>
            @endif
            <tr>
                <td style="text-align:left; position:relative; top: {{ (int) $certificate->header_height }}px;">{!! $certificate->left_header !!}</td>
                <td style="text-align:center; position:relative; top: {{ (int) $certificate->header_height }}px;">{!! $certificate->center_header !!}</td>
                <td style="text-align:right; position:relative; top: {{ (int) $certificate->header_height }}px;">{!! $certificate->right_header !!}</td>
            </tr>
            <tr>
                <td colspan="3" style="position:relative; top: {{ (int) $certificate->content_height }}px; padding-top:20px;">
                    {!! $certificate->certificate_text !!}
                </td>
            </tr>
            <tr>
                <td style="text-align:left; position:relative; top: {{ (int) $certificate->footer_height }}px;">{!! $certificate->left_footer !!}</td>
                <td style="text-align:center; position:relative; top: {{ (int) $certificate->footer_height }}px;">{!! $certificate->center_footer !!}</td>
                <td style="text-align:right; position:relative; top: {{ (int) $certificate->footer_height }}px;">{!! $certificate->right_footer !!}</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
