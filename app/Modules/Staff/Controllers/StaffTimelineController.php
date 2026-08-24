<?php

namespace App\Modules\Staff\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Requests\StoreStaffTimelineRequest;
use App\Modules\Staff\Requests\UpdateStaffTimelineRequest;
use App\Modules\Staff\Services\StaffTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Timeline staff endpoints.
 */
class StaffTimelineController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StaffTimelineService $timeline,
    ) {
    }

    public function store(StoreStaffTimelineRequest $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_timeline', 'can_add'), 403);

        $staffId = (int) $request->validated('staff_id');
        Staff::query()->findOrFail($staffId);

        $this->timeline->create($staffId, [
            'title' => (string) $request->validated('timeline_title'),
            'timeline_date' => (string) $request->validated('timeline_date'),
            'description' => (string) ($request->input('timeline_desc') ?? ''),
            'status' => $request->filled('visible_check') ? 'yes' : '',
        ], $request->file('timeline_doc'));

        return $this->respond($request, $staffId, true);
    }

    public function update(UpdateStaffTimelineRequest $request): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_timeline', 'can_edit'), 403);

        $row = $this->timeline->find((int) $request->validated('id'));
        abort_if(! $row, 404);
        abort_unless((int) $row->staff_id === (int) $request->validated('edit_staff_id'), 404);

        $this->timeline->update($row, [
            'title' => (string) $request->validated('timeline_title'),
            'timeline_date' => (string) $request->validated('timeline_date'),
            'description' => (string) ($request->input('timeline_desc') ?? ''),
            'status' => $request->filled('visible_check') ? 'yes' : '',
        ], $request->file('timeline_doc'));

        return $this->respond($request, (int) $row->staff_id, true);
    }

    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_timeline', 'can_delete'), 403);

        $row = $this->timeline->find($id);
        abort_if(! $row, 404);

        $staffId = (int) $row->staff_id;
        $this->timeline->delete($row);

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => (string) __('system.delete_message'),
            ]);
        }

        return redirect()
            ->route('staff.profile', $staffId)
            ->with('success', (string) __('system.delete_message'));
    }

    public function download(int $timelineId): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('staff_timeline', 'can_view'), 403);

        $row = $this->timeline->find($timelineId);
        abort_if(! $row || ! $row->document, 404);

        $path = $this->timeline->absolutePath((string) $row->document);
        abort_unless(File::isFile($path), 404);

        return response()->download($path, basename((string) $row->document));
    }

    public function listPartial(int $id): View
    {
        $this->assertCanViewTimeline($id);

        return view('staff::admin.partials.timeline_list', $this->timelineViewData($id));
    }

    protected function assertCanViewTimeline(int $staffId): void
    {
        $actor = Auth::guard('staff')->user();
        abort_if($actor === null, 403);

        if ((int) $actor->id !== $staffId) {
            abort_unless($this->permissions->hasPrivilege('staff', 'can_view'), 403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function timelineViewData(int $staffId): array
    {
        $actor = Auth::guard('staff')->user();
        $visibleOnly = $actor !== null && (int) $actor->id === $staffId;

        return [
            'staffId' => $staffId,
            'timelineList' => $this->timeline->listFor($staffId, $visibleOnly),
            'canEditTimeline' => $this->permissions->hasPrivilege('staff_timeline', 'can_edit'),
            'canDeleteTimeline' => $this->permissions->hasPrivilege('staff_timeline', 'can_delete'),
        ];
    }

    protected function respond(Request $request, int $staffId, bool $success): JsonResponse|RedirectResponse
    {
        $message = (string) __('system.success_message');

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $success ? 'success' : 'fail',
                'error' => '',
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('staff.profile', $staffId)
            ->with('success', $message);
    }
}
