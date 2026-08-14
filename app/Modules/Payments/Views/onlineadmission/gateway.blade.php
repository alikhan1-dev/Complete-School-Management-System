<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $setting->name ?? 'Payment Details' }}</title>
</head>
<body>
    <h1>Payment Details</h1>
    <p>Online Admission Form Fees: {{ number_format($amount, 2) }}</p>
    @if($processingFee > 0)
        <p>Processing Fees: {{ number_format($processingFee, 2) }}</p>
    @endif
    <p>Total: {{ number_format($total, 2) }}</p>
    <p><a href="javascript:history.back()">Back</a></p>
</body>
</html>
