<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student / Parent Login</title>
    <link rel="stylesheet" href="{{ asset('backend/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/dist/css/AdminLTE.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo"><b>Student / Parent</b> Login</div>
    <div class="login-box-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('student_parent.login.submit') }}">
            @csrf
            <div class="form-group has-feedback">
                <input type="text" name="username" class="form-control" placeholder="Username" value="{{ old('username') }}" required>
            </div>
            <div class="form-group has-feedback">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
        </form>
        <a href="{{ route('student_parent.forgot_password') }}">I forgot my password</a>
        <br>
        <a href="{{ route('staff.login') }}">Staff Login</a>
    </div>
</div>
</body>
</html>
