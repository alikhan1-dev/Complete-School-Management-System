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
    <div class="login-logo"><b>Student / Parent</b> Forgot Password</div>
    <div class="login-box-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('student_parent.forgot_password.submit') }}">
            @csrf
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" value="{{ old('username') }}" required>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="user[]" value="student" @checked(collect(old('user', ['student']))->contains('student'))> Student</label>
                <label><input type="checkbox" name="user[]" value="parent" @checked(collect(old('user'))->contains('parent'))> Parent</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Submit</button>
        </form>
        <br>
        <a href="{{ route('student_parent.login') }}">Back to login</a>
    </div>
</div>
</body>
</html>
