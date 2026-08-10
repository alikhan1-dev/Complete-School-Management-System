<?php

use App\Modules\Shared\Middleware\CheckLoginToken;
use App\Modules\Shared\Middleware\CheckModuleEnabled;
use App\Modules\Shared\Middleware\CheckPermission;
use App\Modules\Shared\Middleware\CheckStudentParentPermission;
use App\Modules\Shared\Middleware\RequireSelectedClass;
use App\Modules\Shared\Middleware\StaffAuth;
use App\Modules\Shared\Middleware\StudentParentAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'staff.auth' => StaffAuth::class,
            'student_parent.auth' => StudentParentAuth::class,
            'permission' => CheckPermission::class,
            'module' => CheckModuleEnabled::class,
            'student_parent.permission' => CheckStudentParentPermission::class,
            'student_parent.login_token' => CheckLoginToken::class,
            'student_parent.selected_class' => RequireSelectedClass::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Modules\Shared\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
