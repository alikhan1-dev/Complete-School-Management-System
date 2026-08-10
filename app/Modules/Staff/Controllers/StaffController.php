<?php

namespace App\Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Services\DataTableResponse;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Staff',
            'contentView' => 'staff::admin.index',
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $rows = Staff::query()->orderBy('id')->limit(500)->get()->map(function (Staff $staff) {
            return [
                $staff->id,
                $staff->employee_id,
                trim($staff->name.' '.$staff->surname),
                $staff->email,
                ((int) $staff->is_active === 1) ? 'Active' : 'Inactive',
            ];
        })->all();

        return DataTableResponse::make($draw, count($rows), count($rows), $rows);
    }
}
