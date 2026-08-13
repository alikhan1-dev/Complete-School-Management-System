<?php

namespace App\Modules\Hostel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hostel\Services\HostelService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/hostel — hostel form CRUD.
 * Hostel rooms: HostelRoomController. Deferred: student hostel report.
 */
class HostelController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected HostelService $hostels,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('hostel', 'can_view'), 403);

        return $this->formPage(null);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('hostel', 'can_add'), 403);

        $this->hostels->create($this->validated($request));

        return redirect()
            ->route('hostel.hostels.index')
            ->with('success', 'Hostel created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('hostel', 'can_edit'), 403);

        return $this->formPage($this->hostels->find($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('hostel', 'can_edit'), 403);

        $hostel = $this->hostels->find($id);
        $this->hostels->update($hostel, $this->validated($request));

        return redirect()
            ->route('hostel.hostels.index')
            ->with('success', 'Hostel updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('hostel', 'can_delete'), 403);

        $this->hostels->delete($this->hostels->find($id));

        return redirect()
            ->route('hostel.hostels.index')
            ->with('success', 'Hostel deleted successfully.');
    }

    protected function formPage(mixed $editing): View
    {
        return view('shared::layouts.admin', [
            'title' => $editing ? 'Edit Hostel' : 'Hostel',
            'contentView' => 'hostel::admin.hostels.index',
            'hostels' => $this->hostels->listHostels(),
            'hostelTypes' => $this->hostels->hostelTypes(),
            'editing' => $editing,
            'canAdd' => $this->permissions->hasPrivilege('hostel', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('hostel', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('hostel', 'can_delete'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'hostel_name' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:Girls,Boys'],
            'address' => ['nullable', 'string', 'max:500'],
            'intake' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
