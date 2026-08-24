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
 * Deferred: maps.
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

    /**
     * CI Pickuppoint_model::reorder_pickup_point — points for a route ordered by order_number.
     *
     * @return Collection<int, object>
     */
    public function pointsForReorder(int $routeId): Collection
    {
        TransportRoute::query()->findOrFail($routeId);
        $sessionId = $this->sessionId();

        return DB::table('route_pickup_point')
            ->join('pickup_point', 'pickup_point.id', '=', 'route_pickup_point.pickup_point_id')
            ->where('route_pickup_point.transport_route_id', $routeId)
            ->where('route_pickup_point.session_id', $sessionId)
            ->orderBy('route_pickup_point.order_number')
            ->orderBy('route_pickup_point.id')
            ->select([
                'route_pickup_point.id',
                'route_pickup_point.transport_route_id',
                'route_pickup_point.fees',
                'route_pickup_point.destination_distance',
                'route_pickup_point.pickup_time',
                'route_pickup_point.order_number',
                'pickup_point.name as pickup_point_name',
            ])
            ->get();
    }

    /**
     * CI Pickuppoint_model::reorder — persist drag order; returns transport_route_id of first id.
     *
     * @param  list<int|string>  $orderedIds  route_pickup_point.id in new order
     */
    public function reorder(array $orderedIds): int
    {
        $ids = array_values(array_filter(array_map('intval', $orderedIds), fn (int $id) => $id > 0));
        if ($ids === []) {
            throw ValidationException::withMessages([
                'position' => 'No pickup points to reorder.',
            ]);
        }

        $sessionId = $this->sessionId();
        $rows = DB::table('route_pickup_point')
            ->whereIn('id', $ids)
            ->where('session_id', $sessionId)
            ->get(['id', 'transport_route_id']);

        if ($rows->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'position' => 'One or more pickup points are invalid for the current session.',
            ]);
        }

        $routeIds = $rows->pluck('transport_route_id')->unique()->values();
        if ($routeIds->count() !== 1) {
            throw ValidationException::withMessages([
                'position' => 'Pickup points must belong to a single route.',
            ]);
        }

        $routeId = (int) $routeIds->first();
        $expectedIds = DB::table('route_pickup_point')
            ->where('transport_route_id', $routeId)
            ->where('session_id', $sessionId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
        $sortedPosted = $ids;
        sort($sortedPosted);
        if ($sortedPosted !== $expectedIds) {
            throw ValidationException::withMessages([
                'position' => 'Reorder list must include all pickup points for the route.',
            ]);
        }

        DB::transaction(function () use ($ids) {
            $order = 1;
            foreach ($ids as $id) {
                DB::table('route_pickup_point')->where('id', $id)->update([
                    'order_number' => $order++,
                    'updated_at' => now(),
                ]);
            }
        });

        return $routeId;
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
