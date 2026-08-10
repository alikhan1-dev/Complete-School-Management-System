<?php

namespace App\Modules\Roles\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Models\PermissionStudent;
use App\Modules\Shared\Services\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPermissionController extends Controller
{
    public function index(): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Student / Parent Permissions',
            'contentView' => 'roles::admin.student_permissions',
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $rows = PermissionStudent::query()->orderBy('id')->get()->map(function (PermissionStudent $row) {
            return [
                $row->id,
                $row->name,
                $row->short_code,
                $row->student ? 'Yes' : 'No',
                $row->parent ? 'Yes' : 'No',
            ];
        })->all();

        return DataTableResponse::make($draw, count($rows), count($rows), $rows);
    }
}
