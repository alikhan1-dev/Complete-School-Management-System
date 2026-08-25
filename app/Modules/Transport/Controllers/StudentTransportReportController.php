<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Transport\Services\StudentTransportReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/route/studenttransportdetails — student transport report.
 */
class StudentTransportReportController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected StudentTransportReportService $reports,
        protected SchoolContext $school,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('transport_report', 'can_view'), 403);

        $filters = $this->filterInput($request);
        $rows = collect();

        if ($this->shouldSearch($request)) {
            $request->validate([
                'class_id' => ['nullable', 'integer'],
                'section_id' => ['nullable', 'integer'],
                'transport_route_id' => ['nullable', 'integer'],
                'pickup_point_id' => ['nullable', 'integer'],
                'vehicle_id' => ['nullable', 'integer'],
            ]);
            $rows = $this->reports->search($filters);
        }

        return view('shared::layouts.admin', [
            'title' => 'Student Transport Report',
            'contentView' => 'transport::admin.reports.student_transport',
            'classes' => $this->reports->classes(),
            'routes' => $this->reports->listRoutes(),
            'filters' => $filters,
            'rows' => $rows,
            'searched' => $this->shouldSearch($request),
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }

    /**
     * CI admin/pickuppoint/getpickuppointsbyroute — cascading filter options.
     */
    public function pickupPointsByRoute(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('transport_report', 'can_view')
            || $this->permissions->hasPrivilege('route_pickup_point', 'can_view'),
            403
        );

        $validated = $request->validate([
            'transport_route_id' => ['required', 'integer'],
        ]);

        return response()->json(
            $this->reports->pickupAndVehiclesForRoute((int) $validated['transport_route_id'])
        );
    }

    /**
     * @return array{class_id:mixed,section_id:mixed,transport_route_id:mixed,pickup_point_id:mixed,vehicle_id:mixed,search:mixed}
     */
    protected function filterInput(Request $request): array
    {
        return [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
            'transport_route_id' => $request->input('transport_route_id'),
            'pickup_point_id' => $request->input('pickup_point_id'),
            'vehicle_id' => $request->input('vehicle_id'),
            'search' => $request->input('search'),
        ];
    }

    protected function shouldSearch(Request $request): bool
    {
        return $request->filled('search') || ($request->isMethod('post') && $request->has('search'));
    }
}
