<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Transport\Services\VehicleDocumentService;
use App\Modules\Transport\Services\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

/**
 * CI admin/vehicle — list/create/edit/delete/view (form POST, not AJAX modals).
 * Deferred: SaaS storage quota.
 */
class VehicleController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected VehicleService $vehicles,
        protected VehicleDocumentService $documents,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('vehicle', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Vehicles',
            'contentView' => 'transport::admin.vehicles.index',
            'vehicles' => $this->vehicles->listVehicles(),
            'editing' => null,
            'canAdd' => $this->permissions->hasPrivilege('vehicle', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('vehicle', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('vehicle', 'can_delete'),
            'photoUrl' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('vehicle', 'can_add'), 403);

        $data = $this->validated($request);
        $photo = $request->file('vehicle_photo');
        $photo = $photo instanceof UploadedFile ? $photo : null;

        $this->vehicles->create($data, $photo);

        return redirect()
            ->route('transport.vehicles.index')
            ->with('success', 'Vehicle created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('vehicle', 'can_edit'), 403);

        $vehicle = $this->vehicles->find($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Vehicle',
            'contentView' => 'transport::admin.vehicles.index',
            'vehicles' => $this->vehicles->listVehicles(),
            'editing' => $vehicle,
            'canAdd' => $this->permissions->hasPrivilege('vehicle', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('vehicle', 'can_delete'),
            'photoUrl' => $this->documents->publicUrl($vehicle->vehicle_photo),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('vehicle', 'can_edit'), 403);

        $vehicle = $this->vehicles->find($id);
        $data = $this->validated($request);
        $photo = $request->file('vehicle_photo');
        $photo = $photo instanceof UploadedFile ? $photo : null;

        $this->vehicles->update($vehicle, $data, $photo);

        return redirect()
            ->route('transport.vehicles.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    public function show(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('vehicle', 'can_view'), 403);

        $vehicle = $this->vehicles->find($id);

        return view('shared::layouts.admin', [
            'title' => 'Vehicle Details',
            'contentView' => 'transport::admin.vehicles.show',
            'vehicle' => $vehicle,
            'photoUrl' => $this->documents->publicUrl($vehicle->vehicle_photo),
            'canEdit' => $this->permissions->hasPrivilege('vehicle', 'can_edit'),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('vehicle', 'can_delete'), 403);

        $this->vehicles->delete($this->vehicles->find($id));

        return redirect()
            ->route('transport.vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'vehicle_no' => ['required', 'string', 'max:100'],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'manufacture_year' => ['nullable', 'string', 'max:20'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'chasis_number' => ['nullable', 'string', 'max:100'],
            'max_seating_capacity' => ['nullable', 'integer', 'min:0'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'driver_licence' => ['nullable', 'string', 'max:100'],
            'driver_contact' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
            'vehicle_photo' => [
                'nullable',
                'file',
                File::image()->max(4096),
            ],
        ]);
    }
}
