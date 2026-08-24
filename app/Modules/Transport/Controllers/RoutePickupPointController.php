<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Transport\Services\RoutePickupPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/pickuppoint/assign — assign pickup points to routes (form POST) + reorder.
 * Deferred: maps.
 */
class RoutePickupPointController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected RoutePickupPointService $assignments,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('route_pickup_point', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Route Pickup Point',
            'contentView' => 'transport::admin.route_pickup.index',
            'assignments' => $this->assignments->listAssigned(),
            'canAdd' => $this->permissions->hasPrivilege('route_pickup_point', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('route_pickup_point', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('route_pickup_point', 'can_delete'),
            'currencySymbol' => app(\App\Modules\Shared\Services\SchoolContext::class)->currencySymbol(),
        ]);
    }

    /**
     * CI admin/pickuppoint/reorder — JSON-encoded HTML rows for sortable modal.
     */
    public function reorder(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('route_pickup_point', 'can_view'), 403);

        $validated = $request->validate([
            'route_id' => ['required', 'integer'],
        ]);

        $points = $this->assignments->pointsForReorder((int) $validated['route_id']);
        $html = view('transport::admin.route_pickup._reorder', [
            'points' => $points,
        ])->render();

        return response()->json($html);
    }

    /**
     * CI admin/pickuppoint/reorder_pointid — persist order; returns transport_route_id.
     */
    public function reorderPointId(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('route_pickup_point', 'can_edit')
            || $this->permissions->hasPrivilege('route_pickup_point', 'can_add'),
            403
        );

        $validated = $request->validate([
            'position' => ['required', 'array', 'min:1'],
            'position.*' => ['integer'],
        ]);

        $routeId = $this->assignments->reorder($validated['position']);

        return response()->json($routeId);
    }

    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('route_pickup_point', 'can_add'), 403);

        if ($request->isMethod('post')) {
            $data = $this->validated($request);
            $this->assignments->assign((int) $data['route_id'], $data['points']);

            return redirect()
                ->route('transport.route_pickup.index')
                ->with('success', 'Pickup points assigned successfully.');
        }

        return view('shared::layouts.admin', [
            'title' => 'Assign Route Pickup Point',
            'contentView' => 'transport::admin.route_pickup.form',
            'editing' => null,
            'routelist' => $this->assignments->allRoutes(),
            'pickupPoints' => $this->assignments->allPickupPoints(),
            'rows' => [['pickup_point_id' => '', 'fees' => '', 'destination_distance' => '', 'pickup_time' => '']],
        ]);
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('route_pickup_point', 'can_edit'), 403);

        $editing = $this->assignments->findAssignedRoute($id);

        if ($request->isMethod('post')) {
            $data = $this->validated($request, requireRouteMatch: true, routeId: $id);
            $this->assignments->sync($id, $data['points']);

            return redirect()
                ->route('transport.route_pickup.index')
                ->with('success', 'Route pickup points updated successfully.');
        }

        $rows = $editing->point_list->map(static function (object $row): array {
            $time = (string) ($row->pickup_time ?? '');
            if (strlen($time) >= 5) {
                $time = substr($time, 0, 5);
            }

            return [
                'pickup_point_id' => (string) $row->pickup_point_id,
                'fees' => (string) $row->fees,
                'destination_distance' => (string) ($row->destination_distance ?? ''),
                'pickup_time' => $time,
            ];
        })->all();

        return view('shared::layouts.admin', [
            'title' => 'Edit Route Pickup Point',
            'contentView' => 'transport::admin.route_pickup.form',
            'editing' => $editing,
            'routelist' => $this->assignments->allRoutes(),
            'pickupPoints' => $this->assignments->allPickupPoints(),
            'rows' => $rows,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('route_pickup_point', 'can_delete'), 403);

        $this->assignments->findAssignedRoute($id);
        $this->assignments->removeByRoute($id);

        return redirect()
            ->route('transport.route_pickup.index')
            ->with('success', 'Route pickup points deleted successfully.');
    }

    /**
     * @return array{route_id:int,points:list<array{pickup_point_id:int,fees:mixed,destination_distance:?string,pickup_time:string}>}
     */
    protected function validated(Request $request, bool $requireRouteMatch = false, ?int $routeId = null): array
    {
        $rules = [
            'route_id' => ['required', 'integer', 'exists:transport_route,id'],
            'points' => ['required', 'array', 'min:1'],
            'points.*.pickup_point_id' => ['required', 'integer', 'exists:pickup_point,id'],
            'points.*.fees' => ['required', 'numeric', 'min:0'],
            'points.*.destination_distance' => ['nullable', 'string', 'max:50'],
            'points.*.pickup_time' => ['required', 'date_format:H:i'],
        ];

        $data = $request->validate($rules);

        if ($requireRouteMatch && $routeId !== null && (int) $data['route_id'] !== $routeId) {
            abort(422, 'Route mismatch.');
        }

        $points = [];
        foreach ($data['points'] as $row) {
            $points[] = [
                'pickup_point_id' => (int) $row['pickup_point_id'],
                'fees' => $row['fees'],
                'destination_distance' => $row['destination_distance'] ?? '',
                'pickup_time' => (string) $row['pickup_time'],
            ];
        }

        return [
            'route_id' => (int) $data['route_id'],
            'points' => $points,
        ];
    }
}
