<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(): View
    {
        return view('shared::layouts.admin', [
            'contentView' => 'auth::admin.dashboard',
        ]);
    }

    public function studentParent(): View
    {
        return view('shared::layouts.student_parent', [
            'contentView' => 'auth::student_parent.dashboard',
        ]);
    }
}
