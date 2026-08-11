<?php

namespace App\Modules\Academics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Requests\StoreSessionRequest;
use App\Modules\Academics\Requests\UpdateSessionRequest;
use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected CurrentSessionResolver $currentSession
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('session_setting', 'can_view'), 403);

        $activeId = $this->currentSession->id();
        $sessions = AcademicSession::query()
            ->orderBy('id')
            ->get()
            ->map(function (AcademicSession $session) use ($activeId) {
                $session->setAttribute('active', (int) $session->id === $activeId ? $activeId : 0);

                return $session;
            });

        return view('shared::layouts.admin', [
            'title' => 'Sessions',
            'contentView' => 'academics::admin.sessions.index',
            'sessions' => $sessions,
        ]);
    }

    public function store(StoreSessionRequest $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('session_setting', 'can_add'), 403);

        // Match CI: insert name only; DB default is_active='no'
        AcademicSession::query()->create([
            'session' => $request->validated('session'),
        ]);

        return redirect()->route('academics.sessions.index')->with('success', 'Session created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('session_setting', 'can_edit'), 403);

        $session = AcademicSession::query()->findOrFail($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Session',
            'contentView' => 'academics::admin.sessions.edit',
            'sessionRow' => $session,
        ]);
    }

    public function update(UpdateSessionRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('session_setting', 'can_edit'), 403);

        $session = AcademicSession::query()->findOrFail($id);
        $session->session = $request->validated('session');
        $session->save();

        return redirect()->route('academics.sessions.index')->with('success', 'Session updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('session_setting', 'can_delete'), 403);

        $session = AcademicSession::query()->findOrFail($id);

        if ((int) $session->id === $this->currentSession->id()) {
            return redirect()->route('academics.sessions.index')
                ->with('error', 'Cannot delete the active academic session.');
        }

        // Preserve CI UI protection: first 15 list rows cannot be deleted.
        $orderedIds = AcademicSession::query()->orderBy('id')->pluck('id');
        $index = $orderedIds->search($id);
        if ($index !== false && $index <= 14) {
            return redirect()->route('academics.sessions.index')
                ->with('error', 'This seeded session cannot be deleted.');
        }

        try {
            DB::transaction(function () use ($session) {
                $session->delete();
            });
        } catch (\Throwable $e) {
            return redirect()->route('academics.sessions.index')
                ->with('error', 'Session could not be deleted due to related records.');
        }

        return redirect()->route('academics.sessions.index')->with('success', 'Session deleted successfully.');
    }
}
