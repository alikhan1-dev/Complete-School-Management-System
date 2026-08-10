<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="{{ asset('backend/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/dist/css/AdminLTE.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo"><b>Staff</b> Forgot Password</div>
    <div class="login-box-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('staff.forgot_password.submit') }}">
            @csrf
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Submit</button>
        </form>
        <br>
        <a href="{{ route('staff.login') }}">Back to login</a>
    </div>
</div>
</body>
</html>
