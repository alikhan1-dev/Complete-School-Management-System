<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Transport\Services\VehicleRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/vehroute — assign vehicles on routes.
 */
class VehrouteController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected VehicleRouteService $assignments,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('assign_vehicle', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('assign_vehicle', 'can_add'), 403);

            $data = $this->validated($request);
            $this->assignments->assign((int) $data['route_id'], array_map('intval', $data['vehicle']));

            return redirect()
                ->route('transport.vehroute.index')
                ->with('success', 'Vehicles assigned successfully.');
        }

        return view('shared::layouts.admin', [
            'title' => 'Assign Vehicle',
            'contentView' => 'transport::admin.vehroute.index',
            'editing' => null,
            'routelist' => $this->assignments->allRoutes(),
            'vehiclelist' => $this->assignments->allVehicles(),
            'vehroutelist' => $this->assignments->listAssigned(),
            'selectedVehicleIds' => [],
            'canAdd' => $this->permissions->hasPrivilege('assign_vehicle', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('assign_vehicle', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('assign_vehicle', 'can_delete'),
        ]);
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('assign_vehicle', 'can_edit'), 403);

        $editing = $this->assignments->findAssignedRoute($id);

        if ($request->isMethod('post')) {
            $data = $this->validated($request);
            $previousRouteId = (int) $request->input('pre_route_id', $id);

            $this->assignments->sync(
                $previousRouteId,
                (int) $data['route_id'],
                array_map('intval', $data['vehicle'])
            );

            return redirect()
                ->route('transport.vehroute.index')
                ->with('success', 'Vehicle assignment updated successfully.');
        }

        return view('shared::layouts.admin', [
            'title' => 'Edit Vehicle On Route',
            'contentView' => 'transport::admin.vehroute.index',
            'editing' => $editing,
            'routelist' => $this->assignments->allRoutes(),
            'vehiclelist' => $this->assignments->allVehicles(),
            'vehroutelist' => $this->assignments->listAssigned(),
            'selectedVehicleIds' => $editing->vehicles->pluck('id')->map(fn ($v) => (int) $v)->all(),
            'canAdd' => $this->permissions->hasPrivilege('assign_vehicle', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('assign_vehicle', 'can_delete'),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('assign_vehicle', 'can_delete'), 403);

        $this->assignments->findAssignedRoute($id);
        $this->assignments->removeByRoute($id);

        return redirect()
            ->route('transport.vehroute.index')
            ->with('success', 'Vehicle assignment deleted successfully.');
    }

    /**
     * @return array{route_id:int,vehicle:list<int|string>}
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'route_id' => ['required', 'integer', 'exists:transport_route,id'],
            'vehicle' => ['required', 'array', 'min:1'],
            'vehicle.*' => ['integer', 'exists:vehicles,id'],
        ]);
    }
}
