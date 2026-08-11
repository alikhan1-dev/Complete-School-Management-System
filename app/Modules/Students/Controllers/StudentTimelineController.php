<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Requests\StoreStudentTimelineRequest;
use App\Modules\Students\Requests\UpdateStudentTimelineRequest;
use App\Modules\Students\Services\StudentTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Timeline student endpoints (add / edit / delete / download).
 */
class StudentTimelineController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentTimelineService $timeline
    ) {
    }

    public function store(StoreStudentTimelineRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_timeline', 'can_add'), 403);

        $studentId = (int) $request->validated('student_id');
        Student::query()->findOrFail($studentId);

        $this->timeline->create($studentId, [
            'title' => (string) $request->validated('timeline_title'),
            'timeline_date' => (string) $request->validated('timeline_date'),
            'description' => (string) ($request->input('timeline_desc') ?? ''),
            'status' => $request->filled('visible_check') ? 'yes' : '',
        ], $request->file('timeline_doc'));

        return redirect()
            ->route('students.view', $studentId)
            ->with('success', 'Timeline added successfully.');
    }

    public function update(UpdateStudentTimelineRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_timeline', 'can_edit'), 403);

        $row = $this->timeline->find((int) $request->validated('id'));
        abort_if(! $row, 404);
        abort_unless((int) $row->student_id === (int) $request->validated('student_id'), 404);

        $this->timeline->update($row, [
            'title' => (string) $request->validated('timeline_title'),
            'timeline_date' => (string) $request->validated('timeline_date'),
            'description' => (string) ($request->input('timeline_desc') ?? ''),
            'status' => $request->filled('visible_check') ? 'yes' : '',
        ], $request->file('timeline_doc'));

        return redirect()
            ->route('students.view', (int) $row->student_id)
            ->with('success', 'Timeline updated successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_timeline', 'can_delete'), 403);

        $id = (int) $request->input('id');
        $row = $this->timeline->find($id);
        abort_if(! $row, 404);

        $studentId = (int) $row->student_id;
        $this->timeline->delete($row);

        return redirect()
            ->route('students.view', $studentId)
            ->with('success', 'Timeline deleted successfully.');
    }

    public function download(int $timelineId): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('student_timeline', 'can_view'), 403);

        $row = $this->timeline->find($timelineId);
        abort_if(! $row || ! $row->document, 404);

        $path = $this->timeline->absolutePath((string) $row->document);
        abort_unless(File::isFile($path), 404);

        return response()->download($path, basename((string) $row->document));
    }
}
