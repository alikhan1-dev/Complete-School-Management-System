<?php

namespace App\Modules\Transport\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Transport\Models\PickupPoint;
use App\Modules\Transport\Models\TransportRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/pickuppoint/assign — route pickup points for current session.
 * Deferred: drag-drop reorder, student fees.
 */
class RoutePickupPointService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
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
     * @return Collection<int, object>
     */
    public function listAssigned(): Collection
    {
        $sessionId = $this->sessionId();

        $routeIds = DB::table('route_pickup_point')
            ->where('session_id', $sessionId)
            ->distinct()
            ->pluck('transport_route_id');

        if ($routeIds->isEmpty()) {
            return collect();
        }

        return TransportRoute::query()
            ->whereIn('id', $routeIds->all())
            ->orderBy('route_title')
            ->get()
            ->map(function (TransportRoute $route) use ($sessionId) {
                return (object) [
                    'transport_route_id' => $route->id,
                    'route_title' => $route->route_title,
                    'point_list' => $this->pointsForRoute((int) $route->id, $sessionId),
                ];
            });
    }

    /**
     * @return Collection<int, object>
     */
    public function pointsForRoute(int $routeId, ?int $sessionId = null): Collection
    {
        $sessionId ??= $this->sessionId();

        return DB::table('route_pickup_point')
            ->join('pickup_point', 'pickup_point.id', '=', 'route_pickup_point.pickup_point_id')
            ->where('route_pickup_point.transport_route_id', $routeId)
            ->where('route_pickup_point.session_id', $sessionId)
            ->orderBy('route_pickup_point.order_number')
            ->select([
                'route_pickup_point.id',
                'route_pickup_point.pickup_point_id',
                'route_pickup_point.fees',
                'route_pickup_point.destination_distance',
                'route_pickup_point.pickup_time',
                'route_pickup_point.order_number',
                'pickup_point.name as pickup_point',
            ])
            ->get();
    }

    public function findAssignedRoute(int $routeId): object
    {
        $route = TransportRoute::query()->findOrFail($routeId);
        $points = $this->pointsForRoute($routeId);
        abort_unless($points->isNotEmpty(), 404);

        return (object) [
            'transport_route_id' => $route->id,
            'route_title' => $route->route_title,
            'point_list' => $points,
        ];
    }

    /**
     * @param  list<array{pickup_point_id:int,fees:float|int|string,destination_distance:?string,pickup_time:string}>  $points
     */
    public function assign(int $routeId, array $points): void
    {
        TransportRoute::query()->findOrFail($routeId);
        $sessionId = $this->sessionId();
        $this->assertValidPoints($points);

        if ($this->routeHasPoints($routeId, $sessionId)) {
            throw ValidationException::withMessages([
                'route_id' => 'Record already exists',
            ]);
        }

        $this->insertPoints($routeId, $sessionId, $points);
    }

    /**
     * @param  list<array{pickup_point_id:int,fees:float|int|string,destination_distance:?string,pickup_time:string}>  $points
     */
    public function sync(int $routeId, array $points): void
    {
        TransportRoute::query()->findOrFail($routeId);
        $sessionId = $this->sessionId();
        $this->assertValidPoints($points);

        DB::transaction(function () use ($routeId, $sessionId, $points) {
            $this->removeByRoute($routeId, $sessionId);
            $this->insertPoints($routeId, $sessionId, $points);
        });
    }

    public function removeByRoute(int $routeId, ?int $sessionId = null): void
    {
        $sessionId ??= $this->sessionId();
        DB::table('route_pickup_point')
            ->where('transport_route_id', $routeId)
            ->where('session_id', $sessionId)
            ->delete();
    }

    public function routeHasPoints(int $routeId, ?int $sessionId = null): bool
    {
        $sessionId ??= $this->sessionId();

        return DB::table('route_pickup_point')
            ->where('transport_route_id', $routeId)
            ->where('session_id', $sessionId)
            ->exists();
    }

    /**
     * @return Collection<int, TransportRoute>
     */
    public function allRoutes(): Collection
    {
        return TransportRoute::query()->orderBy('route_title')->get();
    }

    /**
     * @return Collection<int, PickupPoint>
     */
    public function allPickupPoints(): Collection
    {
        return PickupPoint::query()->orderBy('name')->get();
    }

    /**
     * @param  list<array{pickup_point_id:int,fees:float|int|string,destination_distance:?string,pickup_time:string}>  $points
     */
    protected function assertValidPoints(array $points): void
    {
        if ($points === []) {
            throw ValidationException::withMessages([
                'points' => 'At least one pickup point is required.',
            ]);
        }

        $ids = array_map(static fn (array $row) => (int) $row['pickup_point_id'], $points);
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'points' => 'Duplicate pickup point found.',
            ]);
        }

        $count = PickupPoint::query()->whereIn('id', $ids)->count();
        if ($count !== count($ids)) {
            throw ValidationException::withMessages([
                'points' => 'One or more selected pickup points are invalid.',
            ]);
        }
    }

    /**
     * @param  list<array{pickup_point_id:int,fees:float|int|string,destination_distance:?string,pickup_time:string}>  $points
     */
    protected function insertPoints(int $routeId, int $sessionId, array $points): void
    {
        $now = now();
        $rows = [];
        $order = 1;
        foreach ($points as $point) {
            $time = (string) $point['pickup_time'];
            if (preg_match('/^\d{2}:\d{2}$/', $time)) {
                $time .= ':00';
            }

            $rows[] = [
                'session_id' => $sessionId,
                'transport_route_id' => $routeId,
                'pickup_point_id' => (int) $point['pickup_point_id'],
                'fees' => (float) str_replace(',', '', (string) $point['fees']),
                'destination_distance' => (string) ($point['destination_distance'] ?? ''),
                'pickup_time' => $time,
                'order_number' => $order++,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('route_pickup_point')->insert($rows);
    }
}
