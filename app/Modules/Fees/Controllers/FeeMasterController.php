<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Requests\StoreFeeMasterRequest;
use App\Modules\Fees\Requests\UpdateFeeMasterRequest;
use App\Modules\Fees\Services\FeeMasterService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Feemaster — fees master list + row CRUD + cumulative fine slabs.
 */
class FeeMasterController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FeeMasterService $masters
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_master', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Fees Master',
            'contentView' => 'fees::admin.fee_masters.index',
            'feeGroups' => FeeGroup::query()->adminList()->get(),
            'feeTypes' => FeeType::query()->adminList()->get(),
            'masters' => $this->masters->listForCurrentSession(),
        ]);
    }

    public function store(StoreFeeMasterRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_master', 'can_add'), 403);

        $this->masters->addRow([
            'fee_groups_id' => (int) $request->validated('fee_groups_id'),
            'feetype_id' => (int) $request->validated('feetype_id'),
            'amount' => $request->validated('amount'),
            'due_date' => $request->input('due_date'),
            'fine_type' => (string) $request->validated('account_type'),
            'fine_percentage' => $request->input('fine_percentage', 0),
            'fine_amount' => $request->input('fine_amount', 0),
            'fine_per_day' => $request->boolean('fine_per_day') ? 1 : 0,
            'overdue_day' => $request->input('overdue_day', []),
            'overdue_fine' => $request->input('overdue_fine', []),
            'cumulative_id' => $request->input('cumulative_id', []),
        ]);

        return redirect()->route('fees.fee_masters.index')->with('success', 'Fees master saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_master', 'can_edit'), 403);

        $row = $this->masters->findRow($id);
        abort_if(! $row, 404);

        return view('shared::layouts.admin', [
            'title' => 'Edit Fees Master',
            'contentView' => 'fees::admin.fee_masters.edit',
            'feeGroups' => FeeGroup::query()->adminList()->get(),
            'feeTypes' => FeeType::query()->adminList()->get(),
            'masters' => $this->masters->listForCurrentSession(),
            'row' => $row,
            'cumulativeFines' => $row->cumulativeFines,
        ]);
    }

    public function update(UpdateFeeMasterRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_master', 'can_edit'), 403);

        $row = $this->masters->findRow($id);
        abort_if(! $row, 404);

        $this->masters->updateRow($row, [
            'feetype_id' => (int) $request->validated('feetype_id'),
            'amount' => $request->validated('amount'),
            'due_date' => $request->input('due_date'),
            'fine_type' => (string) $request->validated('account_type'),
            'fine_percentage' => $request->input('fine_percentage', 0),
            'fine_amount' => $request->input('fine_amount', 0),
            'fine_per_day' => $request->boolean('fine_per_day') ? 1 : 0,
            'overdue_day' => $request->input('overdue_day', []),
            'overdue_fine' => $request->input('overdue_fine', []),
            'cumulative_id' => $request->input('cumulative_id', []),
        ]);

        return redirect()->route('fees.fee_masters.index')->with('success', 'Fees master updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_master', 'can_delete'), 403);

        $this->masters->deleteRow($id);

        return redirect()->route('fees.fee_masters.index')->with('success', 'Fees type row deleted successfully.');
    }

    public function destroyGroup(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_master', 'can_delete'), 403);

        $this->masters->deleteSessionGroup($id);

        return redirect()->route('fees.fee_masters.index')->with('success', 'Fees master group deleted successfully.');
    }

    /**
     * CI Feemaster::remove_row — delete one cumulative_fine slab.
     */
    public function removeRow(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_master', 'can_edit'), 403);

        $data = $request->validate([
            'cumulative_id' => ['required', 'integer'],
        ]);

        $this->masters->removeCumulative((int) $data['cumulative_id']);

        return response()->json(['status' => 1, 'msg' => 'success']);
    }
}
