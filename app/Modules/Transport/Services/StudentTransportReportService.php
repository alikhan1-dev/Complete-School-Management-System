<?php

namespace App\Modules\Transport\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/route/studenttransportdetails — student transport report.
 * Deferred: class-teacher class_section scope filtering.
 */
class StudentTransportReportService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected TransportRouteService $routes,
        protected RoutePickupPointService $routePickups,
        protected VehicleRouteService $vehicleRoutes,
    ) {
    }

    public function sessionId(): int
    {
        $id = (int) $this->currentSession->id();
        if ($id <= 0) {
            throw ValidationException::withMessages([
                'session_id' => 'Current academic session is not configured.',
            ]);
        }

        return $id;
    }

    /**
     * @return Collection<int, \App\Modules\Transport\Models\TransportRoute>
     */
    public function listRoutes(): Collection
    {
        return $this->routes->listRoutes();
    }

    /**
     * CI Pickuppoint::getpickuppointsbyroute JSON payload.
     *
     * @return array{vehicle_route_pickups: list<array<string, mixed>>, routes_vehicle: list<object>}
     */
    public function pickupAndVehiclesForRoute(int $transportRouteId): array
    {
        $pickups = $this->routePickups->pointsForRoute($transportRouteId)
            ->map(fn (object $row) => [
                'id' => $row->id,
                'pickup_point_id' => $row->pickup_point_id,
                'pickup_point' => $row->pickup_point,
                'fees' => $row->fees,
                'destination_distance' => $row->destination_distance,
                'pickup_time' => $row->pickup_time,
                'order_number' => $row->order_number,
            ])
            ->values()
            ->all();

        $vehicles = $this->vehicleRoutes->vehiclesForRoute($transportRouteId)
            ->map(fn (object $row) => (object) [
                'id' => $row->id,
                'vehicle_no' => $row->vehicle_no,
                'vec_route_id' => $row->vec_route_id,
            ])
            ->values()
            ->all();

        return [
            'vehicle_route_pickups' => $pickups,
            'routes_vehicle' => $vehicles,
        ];
    }

    /**
     * @param  array{class_id?:mixed,section_id?:mixed,transport_route_id?:mixed,pickup_point_id?:mixed,vehicle_id?:mixed}  $filters
     * @return Collection<int, object>
     */
    public function search(array $filters): Collection
    {
        $sessionId = $this->sessionId();

        $query = DB::table('students')
            ->join('student_session', 'students.id', '=', 'student_session.student_id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->join('classes', 'classes.id', '=', 'student_session.class_id')
            ->join('route_pickup_point', 'student_session.route_pickup_point_id', '=', 'route_pickup_point.id')
            ->join('transport_route', 'transport_route.id', '=', 'route_pickup_point.transport_route_id')
            ->join('pickup_point', 'pickup_point.id', '=', 'route_pickup_point.pickup_point_id')
            ->join('vehicle_routes', 'student_session.vehroute_id', '=', 'vehicle_routes.id')
            ->join('vehicles', 'vehicle_routes.vehicle_id', '=', 'vehicles.id')
            ->where('students.is_active', 'yes')
            ->where('student_session.session_id', $sessionId)
            ->select([
                'students.firstname',
                'students.middlename',
                'students.id',
                'students.admission_no',
                'students.father_name',
                'students.mother_name',
                'students.father_phone',
                'students.mother_phone',
                'classes.class',
                'sections.section',
                'students.lastname',
                'students.mobileno',
                'student_session.route_pickup_point_id',
                'pickup_point.name as pickup_name',
                'transport_route.route_title',
                'route_pickup_point.fees',
                'route_pickup_point.destination_distance',
                'route_pickup_point.pickup_time',
                'vehicles.vehicle_no',
                'vehicles.vehicle_model',
                'vehicles.driver_name',
                'vehicles.driver_contact',
            ])
            ->orderBy('classes.class')
            ->orderBy('sections.section');

        $classId = (int) ($filters['class_id'] ?? 0);
        if ($classId > 0) {
            $query->where('student_session.class_id', $classId);
        }

        $sectionId = (int) ($filters['section_id'] ?? 0);
        if ($sectionId > 0) {
            $query->where('student_session.section_id', $sectionId);
        }

        $routeId = (int) ($filters['transport_route_id'] ?? 0);
        if ($routeId > 0) {
            $query->where('route_pickup_point.transport_route_id', $routeId);
        }

        $pickupPointId = (int) ($filters['pickup_point_id'] ?? 0);
        if ($pickupPointId > 0) {
            $query->where('route_pickup_point.pickup_point_id', $pickupPointId);
        }

        $vehicleId = (int) ($filters['vehicle_id'] ?? 0);
        if ($vehicleId > 0) {
            $query->where('vehicles.id', $vehicleId);
        }

        return $query->get();
    }
}
