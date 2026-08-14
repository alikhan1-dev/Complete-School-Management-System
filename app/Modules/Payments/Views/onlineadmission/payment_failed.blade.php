<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $setting->name ?? 'Payment Failed' }}</title>
</head>
<body>
    <h1>Payment Failed</h1>
    <p>Your transaction has failed due to some technical error</p>
    <p>Please try again</p>
    <p><a href="{{ url('welcome/online_admission_review/'.$reference_no) }}">Try Again</a></p>
</body>
</html>
