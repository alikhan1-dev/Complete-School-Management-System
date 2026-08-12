<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\ExpenseHead;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Expensehead — expense head CRUD.
 */
class ExpenseHeadController extends Controller
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('expense_head', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Expense Head',
            'contentView' => 'finance::admin.expense_heads.index',
            'heads' => ExpenseHead::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('expense_head', 'can_add'), 403);

        $data = $request->validate([
            'expensehead' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
        ]);

        ExpenseHead::query()->create([
            'exp_category' => $data['expensehead'],
            'description' => $data['description'] ?? '',
            'is_active' => 'yes',
            'is_deleted' => 'no',
        ]);

        return redirect()->route('finance.expense_heads.index')->with('success', 'Expense head created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('expense_head', 'can_edit'), 403);

        $head = ExpenseHead::query()->findOrFail($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Expense Head',
            'contentView' => 'finance::admin.expense_heads.edit',
            'heads' => ExpenseHead::query()->orderBy('id')->get(),
            'head' => $head,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('expense_head', 'can_edit'), 403);

        $head = ExpenseHead::query()->findOrFail($id);
        $data = $request->validate([
            'expensehead' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
        ]);

        $head->exp_category = $data['expensehead'];
        $head->description = $data['description'] ?? '';
        $head->save();

        return redirect()->route('finance.expense_heads.index')->with('success', 'Expense head updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('expense_head', 'can_delete'), 403);

        ExpenseHead::query()->findOrFail($id)->delete();

        return redirect()->route('finance.expense_heads.index')->with('success', 'Expense head deleted successfully.');
    }
}
