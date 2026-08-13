<?php

namespace App\Modules\Transport\Services;

use App\Modules\Transport\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/vehicle — fleet CRUD.
 * Deferred: SaaS storage quota, routes/pickup assignment.
 */
class VehicleService
{
    public function __construct(
        protected VehicleDocumentService $documents,
    ) {
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function listVehicles(): Collection
    {
        return Vehicle::query()->orderByDesc('id')->get();
    }

    public function find(int $id): Vehicle
    {
        return Vehicle::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $photo = null): Vehicle
    {
        $payload = $this->normalizedPayload($data);
        if ($photo !== null) {
            $payload['vehicle_photo'] = $this->documents->store($photo);
        } else {
            $payload['vehicle_photo'] = '';
        }

        return Vehicle::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Vehicle $vehicle, array $data, ?UploadedFile $photo = null): Vehicle
    {
        $payload = $this->normalizedPayload($data);

        if ($photo !== null) {
            $previous = (string) ($vehicle->vehicle_photo ?? '');
            $payload['vehicle_photo'] = $this->documents->store($photo);
            $this->documents->delete($previous);
        }

        $vehicle->fill($payload);
        $vehicle->save();

        return $vehicle;
    }

    public function delete(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle) {
            DB::table('vehicle_routes')->where('vehicle_id', $vehicle->id)->delete();
            $photo = (string) ($vehicle->vehicle_photo ?? '');
            $vehicle->delete();
            $this->documents->delete($photo);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizedPayload(array $data): array
    {
        $capacity = $data['max_seating_capacity'] ?? null;
        $capacity = ($capacity === null || $capacity === '') ? null : (int) $capacity;

        return [
            'vehicle_no' => (string) $data['vehicle_no'],
            'vehicle_model' => (string) ($data['vehicle_model'] ?? ''),
            'manufacture_year' => (string) ($data['manufacture_year'] ?? ''),
            'registration_number' => (string) ($data['registration_number'] ?? ''),
            'chasis_number' => (string) ($data['chasis_number'] ?? ''),
            'max_seating_capacity' => $capacity,
            'driver_name' => (string) ($data['driver_name'] ?? ''),
            'driver_licence' => (string) ($data['driver_licence'] ?? ''),
            'driver_contact' => (string) ($data['driver_contact'] ?? ''),
            'note' => (string) ($data['note'] ?? ''),
        ];
    }
}
