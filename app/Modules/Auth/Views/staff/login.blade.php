<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Login</title>
    <link rel="stylesheet" href="{{ asset('backend/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/dist/css/AdminLTE.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo"><b>Staff</b> Login</div>
    <div class="login-box-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('staff.login.submit') }}">
            @csrf
            <div class="form-group has-feedback">
                <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required>
            </div>
            <div class="form-group has-feedback">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="row">
                <div class="col-xs-8">
                    <label><input type="checkbox" name="remember" value="1"> Remember Me</label>
                </div>
                <div class="col-xs-4">
                    <button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
                </div>
            </div>
        </form>
        <a href="{{ route('staff.forgot_password') }}">I forgot my password</a>
        <br>
        <a href="{{ route('student_parent.login') }}">Student / Parent Login</a>
    </div>
</div>
</body>
</html>
