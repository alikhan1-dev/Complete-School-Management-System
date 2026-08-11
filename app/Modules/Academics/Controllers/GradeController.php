<?php

namespace App\Modules\Academics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\Grade;
use App\Modules\Academics\Requests\StoreGradeRequest;
use App\Modules\Academics\Requests\UpdateGradeRequest;
use App\Modules\Academics\Support\ExamTypes;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('marks_grade', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Marks Grade',
            'contentView' => 'academics::admin.grades.index',
            'grades' => Grade::query()->orderBy('id')->get(),
            'examTypes' => ExamTypes::options(),
        ]);
    }

    public function store(StoreGradeRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('marks_grade', 'can_add'), 403);

        Grade::query()->create([
            'exam_type' => $request->validated('exam_type'),
            'name' => $request->validated('name'),
            'mark_from' => $request->validated('mark_from'),
            'mark_upto' => $request->validated('mark_upto'),
            'point' => $request->validated('grade_point'),
            'description' => $request->validated('description') ?? '',
            'is_active' => 'yes',
        ]);

        return redirect()->route('academics.grades.index')->with('success', 'Grade created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('marks_grade', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Grade',
            'contentView' => 'academics::admin.grades.edit',
            'grades' => Grade::query()->orderBy('id')->get(),
            'grade' => Grade::query()->findOrFail($id),
            'examTypes' => ExamTypes::options(),
        ]);
    }

    public function update(UpdateGradeRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('marks_grade', 'can_edit'), 403);

        $grade = Grade::query()->findOrFail($id);
        $grade->exam_type = $request->validated('exam_type');
        $grade->name = $request->validated('name');
        $grade->mark_from = $request->validated('mark_from');
        $grade->mark_upto = $request->validated('mark_upto');
        $grade->point = $request->validated('grade_point');
        $grade->description = $request->validated('description') ?? '';
        $grade->save();

        return redirect()->route('academics.grades.index')->with('success', 'Grade updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('marks_grade', 'can_delete'), 403);

        Grade::query()->findOrFail($id)->delete();

        return redirect()->route('academics.grades.index')->with('success', 'Grade deleted successfully.');
    }
}
