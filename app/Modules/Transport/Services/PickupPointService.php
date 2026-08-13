<?php

namespace App\Modules\Transport\Services;

use App\Modules\Transport\Models\PickupPoint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/pickuppoint — master points CRUD.
 * Deferred: route assign, student fees, Google Maps modal, reorder.
 */
class PickupPointService
{
    /**
     * @return Collection<int, PickupPoint>
     */
    public function listPoints(): Collection
    {
        return PickupPoint::query()->orderByDesc('id')->get();
    }

    public function find(int $id): PickupPoint
    {
        return PickupPoint::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PickupPoint
    {
        return PickupPoint::query()->create($this->normalizedPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PickupPoint $point, array $data): PickupPoint
    {
        $point->fill($this->normalizedPayload($data));
        $point->save();

        return $point;
    }

    public function delete(PickupPoint $point): void
    {
        DB::transaction(function () use ($point) {
            DB::table('route_pickup_point')->where('pickup_point_id', $point->id)->delete();
            $point->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected function normalizedPayload(array $data): array
    {
        return [
            'name' => (string) $data['name'],
            'latitude' => (string) $data['latitude'],
            'longitude' => (string) $data['longitude'],
        ];
    }
}
