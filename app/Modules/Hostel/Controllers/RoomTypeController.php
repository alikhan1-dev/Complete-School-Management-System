<?php

namespace App\Modules\Hostel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hostel\Services\RoomTypeService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/roomtype — room type form CRUD.
 */
class RoomTypeController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected RoomTypeService $types,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('room_type', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Room Type',
            'contentView' => 'hostel::admin.room_types.index',
            'types' => $this->types->listTypes(),
            'editing' => null,
            'canAdd' => $this->permissions->hasPrivilege('room_type', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('room_type', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('room_type', 'can_delete'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('room_type', 'can_add'), 403);

        $this->types->create($this->validated($request));

        return redirect()
            ->route('hostel.room_types.index')
            ->with('success', 'Room type created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('room_type', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Room Type',
            'contentView' => 'hostel::admin.room_types.index',
            'types' => $this->types->listTypes(),
            'editing' => $this->types->find($id),
            'canAdd' => $this->permissions->hasPrivilege('room_type', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('room_type', 'can_delete'),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('room_type', 'can_edit'), 403);

        $type = $this->types->find($id);
        $this->types->update($type, $this->validated($request));

        return redirect()
            ->route('hostel.room_types.index')
            ->with('success', 'Room type updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('room_type', 'can_delete'), 403);

        $this->types->delete($this->types->find($id));

        return redirect()
            ->route('hostel.room_types.index')
            ->with('success', 'Room type deleted successfully.');
    }

    /**
     * @return array<string, string>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'room_type' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
