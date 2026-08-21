<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Students\Services\AlumniEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Alumni::events + add_event + delete_event.
 * Deferred: FullCalendar, mail/SMS notifications, SaaS quota.
 */
class AlumniEventController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected AlumniEventService $events,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('events', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => __('system.event_list'),
            'contentView' => 'students::admin.alumni.events',
            'eventlist' => $this->events->listEvents(),
            'events' => $this->events,
            'canAdd' => $this->permissions->hasPrivilege('events', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('events', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('events', 'can_delete'),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('events', 'can_add'), 403);

        return $this->form($request, null);
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('events', 'can_edit'), 403);

        return $this->form($request, $this->events->find($id));
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('events', 'can_delete'), 403);
        $this->events->delete($this->events->find($id));

        return redirect()
            ->route('students.alumni.events')
            ->with('success', __('system.delete_message'));
    }

    protected function form(Request $request, $editing): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $eventFor = (string) $request->input('event_for', 'all');
            $rules = [
                'event_for' => ['required', 'in:all,class'],
                'event_title' => ['required', 'string'],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
                'note' => ['nullable', 'string'],
                'event_notification_message' => ['nullable', 'string'],
                'file' => ['nullable', 'image', 'max:5120'],
            ];
            if ($eventFor === 'class') {
                $rules['session_id'] = ['required', 'integer'];
                $rules['class_id'] = ['required', 'integer'];
                $rules['user'] = ['required', 'array', 'min:1'];
                $rules['user.*'] = ['integer'];
            }

            $data = $request->validate($rules, [
                'event_title.required' => 'The '.__('system.event_title').' field is required.',
                'from_date.required' => 'The '.__('system.event_from_date').' field is required.',
                'to_date.required' => 'The '.__('system.event_to_date').' field is required.',
                'session_id.required' => 'The '.__('system.pass_out_session').' field is required.',
                'class_id.required' => 'The '.__('system.class').' field is required.',
                'user.required' => 'The '.__('system.section').' field is required.',
            ]);

            $this->events->save($editing, $data, $request->file('file'));

            return redirect()
                ->route('students.alumni.events')
                ->with('success', __('system.success_message'));
        }

        $selectedSections = [];
        if ($editing) {
            $decoded = json_decode((string) $editing->section, true);
            $selectedSections = is_array($decoded) ? array_map('intval', $decoded) : [];
        }

        $classId = (int) old('class_id', $editing->class_id ?? 0);

        return view('shared::layouts.admin', [
            'title' => $editing ? __('system.edit') : __('system.add_event'),
            'contentView' => 'students::admin.alumni.event_form',
            'editing' => $editing,
            'sessionlist' => AcademicSession::query()->orderByDesc('id')->get(),
            'classlist' => SchoolClass::query()->orderBy('class')->get(),
            'sectionOptions' => $this->events->sectionsForClass($classId),
            'selectedSections' => $selectedSections,
            'events' => $this->events,
        ]);
    }
}
