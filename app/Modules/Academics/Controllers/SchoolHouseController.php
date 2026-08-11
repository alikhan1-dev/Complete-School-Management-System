<?php

namespace App\Modules\Academics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolHouse;
use App\Modules\Academics\Requests\StoreSchoolHouseRequest;
use App\Modules\Academics\Requests\UpdateSchoolHouseRequest;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SchoolHouseController extends Controller
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('student_houses', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Student Houses',
            'contentView' => 'academics::admin.school_houses.index',
            'houses' => SchoolHouse::query()->orderBy('id')->get(),
        ]);
    }

    public function store(StoreSchoolHouseRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_houses', 'can_add'), 403);

        SchoolHouse::query()->create([
            'house_name' => $request->validated('house_name'),
            'description' => $request->validated('description') ?? '',
            'is_active' => 'yes',
        ]);

        return redirect()->route('academics.school_houses.index')->with('success', 'House created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('student_houses', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Student House',
            'contentView' => 'academics::admin.school_houses.edit',
            'houses' => SchoolHouse::query()->orderBy('id')->get(),
            'house' => SchoolHouse::query()->findOrFail($id),
        ]);
    }

    public function update(UpdateSchoolHouseRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_houses', 'can_edit'), 403);

        $house = SchoolHouse::query()->findOrFail($id);
        $house->house_name = $request->validated('house_name');
        $house->description = $request->validated('description') ?? '';
        $house->is_active = 'yes';
        $house->save();

        return redirect()->route('academics.school_houses.index')->with('success', 'House updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_houses', 'can_delete'), 403);

        SchoolHouse::query()->findOrFail($id)->delete();

        return redirect()->route('academics.school_houses.index')->with('success', 'House deleted successfully.');
    }
}
