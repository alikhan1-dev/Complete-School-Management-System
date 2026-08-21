<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Students\Services\DisableReasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Disable_reason — disable reason master list/add/edit/delete.
 */
class DisableReasonController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DisableReasonService $reasons,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('disable_reason', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('disable_reason', 'can_add'), 403);
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ], [
                'name.required' => 'The '.__('system.disable_reason').' field is required.',
            ]);

            $this->reasons->create(trim((string) $request->input('name')));

            return redirect()
                ->route('students.disable_reasons.index')
                ->with('success', __('system.success_message'));
        }

        return view('shared::layouts.admin', [
            'title' => __('system.disable_reason_list'),
            'contentView' => 'students::admin.disable_reason.index',
            'results' => $this->reasons->all(),
            'editing' => null,
            'canAdd' => $this->permissions->hasPrivilege('disable_reason', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('disable_reason', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('disable_reason', 'can_delete'),
        ]);
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('disable_reason', 'can_edit'), 403);

        $editing = $this->reasons->find($id);

        if ($request->isMethod('post')) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ], [
                'name.required' => 'The '.__('system.disable_reason').' field is required.',
            ]);

            $this->reasons->update($editing, trim((string) $request->input('name')));

            return redirect()
                ->route('students.disable_reasons.index')
                ->with('success', __('system.update_message'));
        }

        return view('shared::layouts.admin', [
            'title' => __('system.edit_disable_reason'),
            'contentView' => 'students::admin.disable_reason.index',
            'results' => $this->reasons->all(),
            'editing' => $editing,
            'canAdd' => false,
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('disable_reason', 'can_delete'),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('disable_reason', 'can_delete'), 403);

        $this->reasons->delete($this->reasons->find($id));

        return redirect()
            ->route('students.disable_reasons.index')
            ->with('success', __('system.delete_message'));
    }
}
