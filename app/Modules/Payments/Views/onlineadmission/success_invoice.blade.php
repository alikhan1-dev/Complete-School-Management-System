<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $setting->name ?? 'Success' }}</title>
</head>
<body>
    <h1>Success</h1>
    <p>Your online admission fees is successfully submitted</p>
    <p>Thank you for payment</p>
    <p><a href="{{ url('welcome/online_admission_review/'.$reference_no) }}">Payment Status</a></p>
</body>
</html>
