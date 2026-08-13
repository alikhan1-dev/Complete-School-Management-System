<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transfer Certificate{{ $isRegenerate ? ' [Reissue]' : '' }} — {{ $studentName }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; color: #222; }
        .toolbar { padding: 10px 16px; background: #f5f5f5; border-bottom: 1px solid #ddd; }
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

@include('certificates::admin.transfercertificate._sheet')
</body>
</html>
