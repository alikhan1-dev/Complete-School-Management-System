<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Requests\StoreFeeTypeRequest;
use App\Modules\Fees\Requests\UpdateFeeTypeRequest;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CI admin/Feetype — fees type CRUD (excludes is_system + nature=custom).
 */
class FeeTypeController extends Controller
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_type', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Fees Type',
            'contentView' => 'fees::admin.fee_types.index',
            'feeTypes' => FeeType::query()->adminList()->get(),
        ]);
    }

    public function store(StoreFeeTypeRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_type', 'can_add'), 403);

        FeeType::query()->create([
            'type' => $request->validated('name'),
            'code' => $request->validated('code'),
            'description' => $request->validated('description') ?? '',
            'is_system' => 0,
            'nature' => '',
            'is_active' => 'no',
        ]);

        return redirect()->route('fees.fee_types.index')->with('success', 'Fees type created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('fees_type', 'can_edit'), 403);

        $feeType = FeeType::query()->adminList()->where('id', $id)->firstOrFail();

        return view('shared::layouts.admin', [
            'title' => 'Edit Fees Type',
            'contentView' => 'fees::admin.fee_types.edit',
            'feeTypes' => FeeType::query()->adminList()->get(),
            'feeType' => $feeType,
        ]);
    }

    public function update(UpdateFeeTypeRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_type', 'can_edit'), 403);

        $feeType = FeeType::query()->adminList()->where('id', $id)->firstOrFail();
        $feeType->type = $request->validated('name');
        $feeType->code = $request->validated('code');
        $feeType->description = $request->validated('description') ?? '';
        $feeType->save();

        return redirect()->route('fees.fee_types.index')->with('success', 'Fees type updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_type', 'can_delete'), 403);

        $feeType = FeeType::query()->adminList()->where('id', $id)->firstOrFail();
        $feeType->delete();

        return redirect()->route('fees.fee_types.index')->with('success', 'Fees type deleted successfully.');
    }
}
