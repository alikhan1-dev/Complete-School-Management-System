<?php

namespace App\Modules\Transport\Services;

use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/vehroute — assign vehicles to routes.
 */
class VehicleRouteService
{
    /**
     * Routes that already have at least one assigned vehicle.
     *
     * @return Collection<int, object>
     */
    public function listAssigned(): Collection
    {
        $routeIds = DB::table('vehicle_routes')
            ->select('route_id')
            ->distinct()
            ->pluck('route_id');

        if ($routeIds->isEmpty()) {
            return collect();
        }

        return TransportRoute::query()
            ->whereIn('id', $routeIds->all())
            ->orderBy('route_title')
            ->get()
            ->map(function (TransportRoute $route) {
                return (object) [
                    'id' => $route->id,
                    'route_title' => $route->route_title,
                    'vehicles' => $this->vehiclesForRoute((int) $route->id),
                ];
            });
    }

    /**
     * @return Collection<int, object>
     */
    public function vehiclesForRoute(int $routeId): Collection
    {
        return DB::table('vehicle_routes')
            ->join('vehicles', 'vehicles.id', '=', 'vehicle_routes.vehicle_id')
            ->where('vehicle_routes.route_id', $routeId)
            ->orderByDesc('vehicle_routes.id')
            ->select([
                'vehicles.id',
                'vehicles.vehicle_no',
                'vehicle_routes.id as vec_route_id',
            ])
            ->get();
    }

    public function findAssignedRoute(int $routeId): object
    {
        $route = TransportRoute::query()->findOrFail($routeId);
        $vehicles = $this->vehiclesForRoute($routeId);
        abort_unless($vehicles->isNotEmpty(), 404);

        return (object) [
            'id' => $route->id,
            'route_title' => $route->route_title,
            'vehicles' => $vehicles,
        ];
    }

    /**
     * @param  list<int>  $vehicleIds
     */
    public function assign(int $routeId, array $vehicleIds): void
    {
        TransportRoute::query()->findOrFail($routeId);
        $this->assertVehiclesExist($vehicleIds);

        if ($this->routeHasVehicles($routeId)) {
            throw ValidationException::withMessages([
                'route_id' => 'Record already exists',
            ]);
        }

        $this->insertBatch($routeId, $vehicleIds);
    }

    /**
     * @param  list<int>  $vehicleIds
     */
    public function sync(int $previousRouteId, int $routeId, array $vehicleIds): void
    {
        TransportRoute::query()->findOrFail($previousRouteId);
        TransportRoute::query()->findOrFail($routeId);
        $this->assertVehiclesExist($vehicleIds);

        if ($previousRouteId !== $routeId && $this->routeHasVehicles($routeId)) {
            throw ValidationException::withMessages([
                'route_id' => 'Record already exists',
            ]);
        }

        DB::transaction(function () use ($previousRouteId, $routeId, $vehicleIds) {
            $this->removeByRoute($previousRouteId);
            $this->insertBatch($routeId, $vehicleIds);
        });
    }

    public function removeByRoute(int $routeId): void
    {
        DB::table('vehicle_routes')->where('route_id', $routeId)->delete();
    }

    public function routeHasVehicles(int $routeId): bool
    {
        return DB::table('vehicle_routes')->where('route_id', $routeId)->exists();
    }

    /**
     * @return Collection<int, TransportRoute>
     */
    public function allRoutes(): Collection
    {
        return TransportRoute::query()->orderBy('route_title')->get();
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function allVehicles(): Collection
    {
        return Vehicle::query()->orderByDesc('id')->get();
    }

    /**
     * @param  list<int>  $vehicleIds
     */
    protected function assertVehiclesExist(array $vehicleIds): void
    {
        $count = Vehicle::query()->whereIn('id', $vehicleIds)->count();
        if ($count !== count(array_unique($vehicleIds))) {
            throw ValidationException::withMessages([
                'vehicle' => 'One or more selected vehicles are invalid.',
            ]);
        }
    }

    /**
     * @param  list<int>  $vehicleIds
     */
    protected function insertBatch(int $routeId, array $vehicleIds): void
    {
        $now = now();
        $rows = [];
        foreach (array_values(array_unique($vehicleIds)) as $vehicleId) {
            $rows[] = [
                'route_id' => $routeId,
                'vehicle_id' => (int) $vehicleId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('vehicle_routes')->insert($rows);
        }
    }
}
