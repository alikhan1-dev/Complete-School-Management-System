<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ($schoolContext->schoolName() ?? 'Smart School') }}</title>
    <link rel="stylesheet" href="{{ asset('backend/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/dist/css/AdminLTE.css') }}">
    @stack('styles')
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
    <header class="main-header">
        <a href="{{ route('student_parent.dashboard') }}" class="logo"><span class="logo-lg">{{ $schoolContext->schoolName() }}</span></a>
        <nav class="navbar navbar-static-top">
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    <li>
                        <form action="{{ route('student_parent.logout') }}" method="post" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-link" style="color:#fff;padding:15px">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <aside class="main-sidebar">
        <section class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="{{ route('student_parent.dashboard') }}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
                <li><a href="{{ route('user.onlineexam.index') }}"><i class="fa fa-wifi"></i> <span>Online Exam</span></a></li>
            </ul>
        </section>
    </aside>
    <div class="content-wrapper">
        <section class="content">
            @if(isset($contentView))
                @include($contentView)
            @else
                @yield('content')
            @endif
        </section>
    </div>
</div>
<script src="{{ asset('backend/plugins/jQuery/jQuery-2.1.4.min.js') }}"></script>
<script src="{{ asset('backend/bootstrap/js/bootstrap.min.js') }}"></script>
@stack('scripts')
</body>
</html>
