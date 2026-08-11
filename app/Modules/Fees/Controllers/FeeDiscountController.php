<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Fees\Requests\StoreFeeDiscountRequest;
use App\Modules\Fees\Requests\UpdateFeeDiscountRequest;
use App\Modules\Fees\Services\FeeDiscountService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Students\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Feediscount — define + assign (applydiscount deferred to collect).
 */
class FeeDiscountController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FeeDiscountService $discounts
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_discount', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Fees Discount',
            'contentView' => 'fees::admin.fee_discounts.index',
            'discounts' => $this->discounts->listAll(),
        ]);
    }

    public function store(StoreFeeDiscountRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_discount', 'can_add'), 403);

        $this->discounts->create([
            'name' => $request->validated('name'),
            'code' => $request->validated('code'),
            'type' => $request->validated('account_type'),
            'amount' => $request->input('amount'),
            'percentage' => $request->input('percentage'),
            'discount_limit' => $request->validated('discount_limit'),
            'expire_date' => $request->input('expire_date'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('fees.fee_discounts.index')->with('success', 'Fees discount created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_discount', 'can_edit'), 403);

        $discount = $this->discounts->find($id);
        abort_if(! $discount, 404);

        return view('shared::layouts.admin', [
            'title' => 'Edit Fees Discount',
            'contentView' => 'fees::admin.fee_discounts.edit',
            'discounts' => $this->discounts->listAll(),
            'discount' => $discount,
        ]);
    }

    public function update(UpdateFeeDiscountRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_discount', 'can_edit'), 403);

        $discount = $this->discounts->find($id);
        abort_if(! $discount, 404);

        $this->discounts->update($discount, [
            'name' => $request->validated('name'),
            'code' => $request->validated('code'),
            'type' => $request->validated('account_type'),
            'amount' => $request->input('amount'),
            'percentage' => $request->input('percentage'),
            'discount_limit' => $request->validated('discount_limit'),
            'expire_date' => $request->input('expire_date'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('fees.fee_discounts.index')->with('success', 'Fees discount updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_discount', 'can_delete'), 403);

        $this->discounts->delete($id);

        return redirect()->route('fees.fee_discounts.index')->with('success', 'Fees discount deleted successfully.');
    }

    public function assign(Request $request, int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_discount_assign', 'can_view'), 403);

        $discount = $this->discounts->find($id);
        abort_if(! $discount, 404);

        $resultList = null;
        if ($request->isMethod('post')) {
            $resultList = $this->discounts->searchStudentsForAssign(
                $id,
                $request->filled('class_id') ? (int) $request->input('class_id') : null,
                $request->filled('section_id') ? (int) $request->input('section_id') : null,
                $request->filled('category_id') ? (int) $request->input('category_id') : null,
                $request->filled('gender') ? (string) $request->input('gender') : null,
                $request->filled('rte') ? (string) $request->input('rte') : null
            );
        }

        return view('shared::layouts.admin', [
            'title' => 'Assign Fees Discount',
            'contentView' => 'fees::admin.fee_discounts.assign',
            'discount' => $discount,
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

    /** CI Feediscount::studentdiscount */
    public function saveAssign(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_discount_assign', 'can_view'), 403);

        $data = $request->validate([
            'feediscount_id' => ['required', 'integer', 'exists:fees_discounts,id'],
            'student_session_id' => ['nullable', 'array'],
            'student_session_id.*' => ['integer'],
            'student_list' => ['required', 'array', 'min:1'],
            'student_list.*' => ['integer'],
        ]);

        $this->discounts->syncAssignments(
            (int) $data['feediscount_id'],
            $data['student_session_id'] ?? [],
            $data['student_list']
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'error' => '',
                'message' => 'Fees discount assigned successfully.',
            ]);
        }

        return redirect()
            ->route('fees.fee_discounts.assign', (int) $data['feediscount_id'])
            ->with('success', 'Fees discount assigned successfully.');
    }
}
