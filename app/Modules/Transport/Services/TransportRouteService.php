<?php

namespace App\Modules\Transport\Services;

use App\Modules\Transport\Models\TransportRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/route — route title CRUD.
 * Deferred: vehicle-route assign, pickup points, student transport report.
 */
class TransportRouteService
{
    /**
     * @return Collection<int, TransportRoute>
     */
    public function listRoutes(): Collection
    {
        return TransportRoute::query()->orderBy('route_title')->get();
    }

    public function find(int $id): TransportRoute
    {
        return TransportRoute::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TransportRoute
    {
        return TransportRoute::query()->create($this->normalizedPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TransportRoute $route, array $data): TransportRoute
    {
        $route->fill($this->normalizedPayload($data, forUpdate: true));
        $route->save();

        return $route;
    }

    public function delete(TransportRoute $route): void
    {
        DB::transaction(function () use ($route) {
            DB::table('vehicle_routes')->where('route_id', $route->id)->delete();
            DB::table('route_pickup_point')->where('transport_route_id', $route->id)->delete();
            $route->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizedPayload(array $data, bool $forUpdate = false): array
    {
        $payload = [
            'route_title' => (string) $data['route_title'],
        ];

        if (array_key_exists('no_of_vehicle', $data)) {
            $payload['no_of_vehicle'] = $data['no_of_vehicle'] === null || $data['no_of_vehicle'] === ''
                ? null
                : (int) $data['no_of_vehicle'];
        } elseif (! $forUpdate) {
            $payload['no_of_vehicle'] = null;
        }

        if (! $forUpdate) {
            $payload['note'] = (string) ($data['note'] ?? '');
            $payload['is_active'] = (string) ($data['is_active'] ?? 'no');
        }

        return $payload;
    }
}
