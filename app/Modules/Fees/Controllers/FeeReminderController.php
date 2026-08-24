<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fees\Services\FeeReminderService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Feereminder::setting — fees reminder day rules.
 */
class FeeReminderController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FeeReminderService $reminders,
    ) {
    }

    public function setting(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_reminder', 'can_view'), 403);
        abort_unless($this->reminders->tableReady(), 404);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('fees_reminder', 'can_edit'), 403);

            $data = $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer'],
            ]);

            // days{id} / isactive_{id} mirror CI field names (dynamic keys).
            $this->reminders->updateBatch($data['ids'], $request->all());

            return redirect()
                ->route('fees.feereminder.setting')
                ->with('success', (string) __('system.update_message'));
        }

        return view('shared::layouts.admin', [
            'title' => (string) __('system.fees_reminder'),
            'contentView' => 'fees::admin.feereminder.setting',
            'feereminderlist' => $this->reminders->list(),
            'canEdit' => $this->permissions->hasPrivilege('fees_reminder', 'can_edit'),
        ]);
    }
}
