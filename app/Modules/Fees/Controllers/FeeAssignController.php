<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Fees\Services\FeeAssignService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Students\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/feemaster/assign + studentfee/addfeegroup.
 */
class FeeAssignController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FeeAssignService $assign
    ) {
    }

    public function assign(Request $request, int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_group_assign', 'can_view'), 403);

        $sessionGroup = $this->assign->findSessionGroup($id);
        abort_if(! $sessionGroup, 404);

        $resultList = null;
        if ($request->isMethod('post')) {
            $request->validate([
                'class_id' => ['nullable', 'integer', 'exists:classes,id'],
                'section_id' => ['nullable', 'integer', 'exists:sections,id'],
                'category_id' => ['nullable', 'integer'],
                'gender' => ['nullable', 'string'],
                'rte' => ['nullable', 'string'],
            ]);

            $resultList = $this->assign->searchStudents(
                $id,
                $request->filled('class_id') ? (int) $request->input('class_id') : null,
                $request->filled('section_id') ? (int) $request->input('section_id') : null,
                $request->filled('category_id') ? (int) $request->input('category_id') : null,
                $request->filled('gender') ? (string) $request->input('gender') : null,
                $request->filled('rte') ? (string) $request->input('rte') : null
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Assign Fees Group',
            'contentView' => 'fees::admin.fee_masters.assign',
            'feeSessionGroupId' => $id,
            'sessionGroup' => $sessionGroup,
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'categories' => Category::query()->orderBy('id')->get(),
            'resultList' => $resultList,
            'filters' => [
                'class_id' => $request->input('class_id'),
                'section_id' => $request->input('section_id'),
                'category_id' => $request->input('category_id'),
                'gender' => $request->input('gender'),
                'rte' => $request->input('rte'),
            ],
        ]);
    }

    /**
     * CI Studentfee::addfeegroup
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_group_assign', 'can_view'), 403);

        $data = $request->validate([
            'fee_session_groups' => ['required', 'integer', 'exists:fee_session_groups,id'],
            'student_session_id' => ['nullable', 'array'],
            'student_session_id.*' => ['integer'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
        ]);

        $feeSessionGroupId = (int) $data['fee_session_groups'];
        abort_if(! $this->assign->findSessionGroup($feeSessionGroupId), 404);

        $this->assign->syncAssignments(
            $feeSessionGroupId,
            $data['student_session_id'] ?? [],
            $data['student_ids']
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => 'Fees group assigned successfully.',
            ]);
        }

        return redirect()
            ->route('fees.fee_masters.assign', $feeSessionGroupId)
            ->with('success', 'Fees group assigned successfully.');
    }
}
