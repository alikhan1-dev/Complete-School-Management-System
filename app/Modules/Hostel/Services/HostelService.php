<?php

namespace App\Modules\Hostel\Services;

use App\Modules\Hostel\Models\Hostel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/hostel — hostel CRUD.
 */
class HostelService
{
    /**
     * CI Customlib::getHostaltype — stored option values.
     *
     * @return array<string, string>
     */
    public function hostelTypes(): array
    {
        return [
            'Girls' => 'Girls',
            'Boys' => 'Boys',
        ];
    }

    /**
     * @return Collection<int, Hostel>
     */
    public function listHostels(): Collection
    {
        return Hostel::query()->orderByDesc('id')->get();
    }

    public function find(int $id): Hostel
    {
        return Hostel::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Hostel
    {
        return Hostel::query()->create($this->normalizedPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Hostel $hostel, array $data): Hostel
    {
        $hostel->fill($this->normalizedPayload($data));
        $hostel->save();

        return $hostel;
    }

    public function delete(Hostel $hostel): void
    {
        DB::transaction(function () use ($hostel) {
            DB::table('hostel_rooms')->where('hostel_id', $hostel->id)->delete();
            $hostel->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{hostel_name:string,type:string,address:string,intake:string,description:string,is_active:string}
     */
    protected function normalizedPayload(array $data): array
    {
        return [
            'hostel_name' => (string) $data['hostel_name'],
            'type' => (string) $data['type'],
            'address' => (string) ($data['address'] ?? ''),
            'intake' => (string) ($data['intake'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'is_active' => (string) ($data['is_active'] ?? 'yes'),
        ];
    }
}
