<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsEventService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/front/Events — event persist (media picker / leftover ajax upload deferred).
 */
class FrontCmsEventController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FrontCmsEventService $events,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('event', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Event List',
            'contentView' => 'frontcms::admin.event_index',
            'pageTitle' => 'Event List',
            'listResult' => $this->events->listAll(),
            'canAdd' => $this->permissions->hasPrivilege('event', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('event', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('event', 'can_delete'),
            'events' => $this->events,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('event', 'can_add'), 403);

        if ($request->isMethod('post')) {
            $errors = $this->validateEvent($request);
            if ($errors === []) {
                $this->events->create($request->all());

                return redirect('admin/front/events')->with('success', 'Record saved successfully.');
            }

            return $this->formView('frontcms::admin.event_create', 'Add Event', $errors, $request->all());
        }

        return $this->formView('frontcms::admin.event_create', 'Add Event');
    }

    public function edit(Request $request, string $slug): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('event', 'can_edit'), 403);
        $row = $this->events->findBySlug(urldecode($slug));
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateEvent($request);
            if ($errors === []) {
                $this->events->update((int) $row['id'], $request->all());

                return redirect('admin/front/events')->with('success', 'Record updated successfully.');
            }

            return $this->formView('frontcms::admin.event_edit', 'Edit Event', $errors, $request->all(), $row);
        }

        return $this->formView('frontcms::admin.event_edit', 'Edit Event', [], [], $row);
    }

    public function delete(string $slug): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('event', 'can_delete'), 403);
        $this->events->deleteBySlug(urldecode($slug));

        return redirect('admin/front/events');
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>|null  $result
     */
    protected function formView(string $contentView, string $title, array $errors = [], array $old = [], ?array $result = null): View
    {
        return view('shared::layouts.admin', [
            'title' => $title,
            'contentView' => $contentView,
            'pageTitle' => $title,
            'formErrors' => $errors,
            'old' => $old,
            'result' => $result,
            'events' => $this->events,
            'today' => $this->events->formatDate(date('Y-m-d')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateEvent(Request $request): array
    {
        $errors = [];
        if (trim((string) $request->input('title', '')) === '') {
            $errors['title'] = 'The Title field is required.';
        }
        if (trim((string) $request->input('start_date', '')) === '') {
            $errors['start_date'] = 'The Start Date field is required.';
        }
        if (trim((string) $request->input('end_date', '')) === '') {
            $errors['end_date'] = 'The Event Date field is required.';
        }
        if (trim((string) $request->input('description', '')) === '') {
            $errors['description'] = 'The Description field is required.';
        }

        return $errors;
    }
}
