<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Transport\Services\PickupPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/pickuppoint — master list/create/edit/delete + pointmap modal.
 */
class PickupPointController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected PickupPointService $points,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('pickup_point', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Pickup Points',
            'contentView' => 'transport::admin.pickup_points.index',
            'points' => $this->points->listPoints(),
            'editing' => null,
            'canAdd' => $this->permissions->hasPrivilege('pickup_point', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('pickup_point', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('pickup_point', 'can_delete'),
            'googleMapsApiKey' => (string) config('services.google.maps_api_key', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('pickup_point', 'can_add'), 403);

        $this->points->create($this->validated($request));

        return redirect()
            ->route('transport.pickup_points.index')
            ->with('success', 'Pickup point created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('pickup_point', 'can_edit'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Edit Pickup Point',
            'contentView' => 'transport::admin.pickup_points.index',
            'points' => $this->points->listPoints(),
            'editing' => $this->points->find($id),
            'canAdd' => $this->permissions->hasPrivilege('pickup_point', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('pickup_point', 'can_delete'),
            'googleMapsApiKey' => (string) config('services.google.maps_api_key', ''),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('pickup_point', 'can_edit'), 403);

        $point = $this->points->find($id);
        $this->points->update($point, $this->validated($request));

        return redirect()
            ->route('transport.pickup_points.index')
            ->with('success', 'Pickup point updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('pickup_point', 'can_delete'), 403);

        $this->points->delete($this->points->find($id));

        return redirect()
            ->route('transport.pickup_points.index')
            ->with('success', 'Pickup point deleted successfully.');
    }

    /**
     * CI admin/pickuppoint/pointmap — JSON {status, error, page:{location,page}}.
     */
    public function pointMap(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('pickup_point', 'can_view'), 403);

        $validated = $request->validate([
            'pick_location' => ['required', 'integer'],
        ]);

        $point = $this->points->find((int) $validated['pick_location']);
        $location = [
            'id' => (int) $point->id,
            'name' => (string) $point->name,
            'latitude' => (string) $point->latitude,
            'longitude' => (string) $point->longitude,
        ];

        $pageHtml = view('transport::admin.pickup_points._pointmap', [
            'location' => $point,
        ])->render();

        return response()->json([
            'status' => '1',
            'error' => '',
            'page' => [
                'location' => $location,
                'page' => $pageHtml,
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'latitude' => ['required', 'string', 'max:50'],
            'longitude' => ['required', 'string', 'max:50'],
        ]);
    }
}
