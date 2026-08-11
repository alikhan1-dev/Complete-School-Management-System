<?php

namespace App\Modules\Academics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Requests\StoreSubjectRequest;
use App\Modules\Academics\Requests\UpdateSubjectRequest;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(protected PermissionService $permissions)
    {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('subject', 'can_view'), 403);

        $subjects = Subject::query()->orderBy('id')->get();

        return view('shared::layouts.admin', [
            'title' => 'Subjects',
            'contentView' => 'academics::admin.subjects.index',
            'subjects' => $subjects,
        ]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('subject', 'can_add'), 403);

        Subject::query()->create([
            'name' => $request->validated('name'),
            'code' => $request->validated('code') ?? '',
            'type' => $request->validated('type'),
            'is_active' => 'yes',
        ]);

        return redirect()->route('academics.subjects.index')->with('success', 'Subject created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('subject', 'can_edit'), 403);

        $subject = Subject::query()->findOrFail($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Subject',
            'contentView' => 'academics::admin.subjects.edit',
            'subjectRow' => $subject,
        ]);
    }

    public function update(UpdateSubjectRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('subject', 'can_edit'), 403);

        $subject = Subject::query()->findOrFail($id);
        $data = $request->validated();
        $subject->name = $data['name'];
        if (array_key_exists('code', $data)) {
            $subject->code = $data['code'] ?? '';
        }
        if (! empty($data['type'])) {
            $subject->type = $data['type'];
        }
        $subject->save();

        return redirect()->route('academics.subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('subject', 'can_delete'), 403);

        Subject::query()->findOrFail($id)->delete();

        return redirect()->route('academics.subjects.index')->with('success', 'Subject deleted successfully.');
    }
}
