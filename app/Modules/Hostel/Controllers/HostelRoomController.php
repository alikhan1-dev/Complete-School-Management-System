<?php

namespace App\Modules\Hostel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hostel\Services\HostelRoomService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/hostelroom — hostel room form CRUD.
 * Student hostel report: StudentHostelReportController.
 */
class HostelRoomController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected HostelRoomService $rooms,
        protected SchoolContext $school,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('hostel_rooms', 'can_view'), 403);

        return $this->formPage(null);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('hostel_rooms', 'can_add'), 403);

        $this->rooms->create($this->validated($request));

        return redirect()
            ->route('hostel.rooms.index')
            ->with('success', 'Hostel room created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('hostel_rooms', 'can_edit'), 403);

        return $this->formPage($this->rooms->find($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('hostel_rooms', 'can_edit'), 403);

        $room = $this->rooms->find($id);
        $this->rooms->update($room, $this->validated($request));

        return redirect()
            ->route('hostel.rooms.index')
            ->with('success', 'Hostel room updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('hostel_rooms', 'can_delete'), 403);

        $this->rooms->delete($this->rooms->find($id));

        return redirect()
            ->route('hostel.rooms.index')
            ->with('success', 'Hostel room deleted successfully.');
    }

    /**
     * CI admin/hostelroom/getRoom — rooms by hostel for cascading selects.
     */
    public function getRoom(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('hostel_rooms', 'can_view')
            || $this->permissions->hasPrivilege('hostel', 'can_view'),
            403
        );

        $validated = $request->validate([
            'hostel_id' => ['required', 'integer'],
        ]);

        return response()->json(
            $this->rooms->roomsByHostel((int) $validated['hostel_id'])->values()->all()
        );
    }

    protected function formPage(mixed $editing): View
    {
        return view('shared::layouts.admin', [
            'title' => $editing ? 'Edit Hostel Room' : 'Hostel Rooms',
            'contentView' => 'hostel::admin.rooms.index',
            'rooms' => $this->rooms->listRooms(),
            'hostels' => $this->rooms->hostelsForSelect(),
            'roomTypes' => $this->rooms->roomTypesForSelect(),
            'editing' => $editing,
            'currencySymbol' => $this->school->currencySymbol(),
            'canAdd' => $this->permissions->hasPrivilege('hostel_rooms', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('hostel_rooms', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('hostel_rooms', 'can_delete'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'room_no' => ['required', 'string', 'max:100'],
            'hostel_id' => ['required', 'integer'],
            'room_type_id' => ['required', 'integer'],
            'no_of_bed' => ['required', 'numeric', 'min:0'],
            'cost_per_bed' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
