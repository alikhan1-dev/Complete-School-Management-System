<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <link rel="stylesheet" href="{{ asset('backend/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/dist/css/AdminLTE.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo"><b>Staff</b> Reset Password</div>
    <div class="login-box-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('staff.reset_password.submit', $verification_code) }}">
            @csrf
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Reset</button>
        </form>
    </div>
</div>
</body>
</html>
