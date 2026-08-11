<?php

namespace App\Modules\Academics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\MarkDivision;
use App\Modules\Academics\Requests\StoreMarkDivisionRequest;
use App\Modules\Academics\Requests\UpdateMarkDivisionRequest;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarkDivisionController extends Controller
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('marks_division', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Marks Division',
            'contentView' => 'academics::admin.mark_divisions.index',
            'divisions' => MarkDivision::query()->orderBy('id')->get(),
        ]);
    }

    public function store(StoreMarkDivisionRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('marks_division', 'can_add'), 403);

        MarkDivision::query()->create([
            'name' => $request->validated('name'),
            'percentage_from' => $request->validated('percentage_from'),
            'percentage_to' => $request->validated('percentage_to'),
            'is_active' => 1,
        ]);

        return redirect()->route('academics.mark_divisions.index')->with('success', 'Division created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('marks_division', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Marks Division',
            'contentView' => 'academics::admin.mark_divisions.edit',
            'divisions' => MarkDivision::query()->orderBy('id')->get(),
            'division' => MarkDivision::query()->findOrFail($id),
        ]);
    }

    public function update(UpdateMarkDivisionRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('marks_division', 'can_edit'), 403);

        $division = MarkDivision::query()->findOrFail($id);
        $division->name = $request->validated('name');
        $division->percentage_from = $request->validated('percentage_from');
        $division->percentage_to = $request->validated('percentage_to');
        $division->save();

        return redirect()->route('academics.mark_divisions.index')->with('success', 'Division updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('marks_division', 'can_delete'), 403);

        MarkDivision::query()->findOrFail($id)->delete();

        return redirect()->route('academics.mark_divisions.index')->with('success', 'Division deleted successfully.');
    }
}
