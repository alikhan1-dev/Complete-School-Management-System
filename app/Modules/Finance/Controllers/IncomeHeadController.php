<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\IncomeHead;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Incomehead — income head CRUD.
 */
class IncomeHeadController extends Controller
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('income_head', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Income Head',
            'contentView' => 'finance::admin.income_heads.index',
            'heads' => IncomeHead::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('income_head', 'can_add'), 403);

        $data = $request->validate([
            'incomehead' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
        ]);

        IncomeHead::query()->create([
            'income_category' => $data['incomehead'],
            'description' => $data['description'] ?? '',
            'is_active' => 'yes',
            'is_deleted' => 'no',
        ]);

        return redirect()->route('finance.income_heads.index')->with('success', 'Income head created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('income_head', 'can_edit'), 403);

        $head = IncomeHead::query()->findOrFail($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Income Head',
            'contentView' => 'finance::admin.income_heads.edit',
            'heads' => IncomeHead::query()->orderBy('id')->get(),
            'head' => $head,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('income_head', 'can_edit'), 403);

        $head = IncomeHead::query()->findOrFail($id);
        $data = $request->validate([
            'incomehead' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
        ]);

        $head->income_category = $data['incomehead'];
        $head->description = $data['description'] ?? '';
        $head->save();

        return redirect()->route('finance.income_heads.index')->with('success', 'Income head updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('income_head', 'can_delete'), 403);

        IncomeHead::query()->findOrFail($id)->delete();

        return redirect()->route('finance.income_heads.index')->with('success', 'Income head deleted successfully.');
    }
}
