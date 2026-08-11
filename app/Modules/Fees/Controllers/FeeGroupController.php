<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Models\FeeGroup;
use App\Modules\Fees\Requests\StoreFeeGroupRequest;
use App\Modules\Fees\Requests\UpdateFeeGroupRequest;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CI admin/Feegroup — fees group CRUD (excludes is_system + nature=custom).
 */
class FeeGroupController extends Controller
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_group', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Fees Group',
            'contentView' => 'fees::admin.fee_groups.index',
            'feeGroups' => FeeGroup::query()->adminList()->get(),
        ]);
    }

    public function store(StoreFeeGroupRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_group', 'can_add'), 403);

        FeeGroup::query()->create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description') ?? '',
            'is_system' => 0,
            'nature' => '',
            'is_active' => 'no',
        ]);

        return redirect()->route('fees.fee_groups.index')->with('success', 'Fees group created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_group', 'can_edit'), 403);

        $feeGroup = FeeGroup::query()->adminList()->where('id', $id)->firstOrFail();

        return view('shared::layouts.admin', [
            'title' => 'Edit Fees Group',
            'contentView' => 'fees::admin.fee_groups.edit',
            'feeGroups' => FeeGroup::query()->adminList()->get(),
            'feeGroup' => $feeGroup,
        ]);
    }

    public function update(UpdateFeeGroupRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_group', 'can_edit'), 403);

        $feeGroup = FeeGroup::query()->adminList()->where('id', $id)->firstOrFail();
        $feeGroup->name = $request->validated('name');
        $feeGroup->description = $request->validated('description') ?? '';
        $feeGroup->save();

        return redirect()->route('fees.fee_groups.index')->with('success', 'Fees group updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_group', 'can_delete'), 403);

        $feeGroup = FeeGroup::query()->adminList()->where('id', $id)->firstOrFail();
        $feeGroup->delete();

        return redirect()->route('fees.fee_groups.index')->with('success', 'Fees group deleted successfully.');
    }
}
