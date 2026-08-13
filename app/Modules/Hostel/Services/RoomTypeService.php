<?php

namespace App\Modules\Hostel\Services;

use App\Modules\Hostel\Models\RoomType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/roomtype — room type CRUD.
 */
class RoomTypeService
{
    /**
     * @return Collection<int, RoomType>
     */
    public function listTypes(): Collection
    {
        return RoomType::query()->orderByDesc('id')->get();
    }

    public function find(int $id): RoomType
    {
        return RoomType::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): RoomType
    {
        return RoomType::query()->create($this->normalizedPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RoomType $type, array $data): RoomType
    {
        $type->fill($this->normalizedPayload($data));
        $type->save();

        return $type;
    }

    public function delete(RoomType $type): void
    {
        DB::transaction(function () use ($type) {
            DB::table('hostel_rooms')->where('room_type_id', $type->id)->delete();
            $type->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{room_type:string,description:string}
     */
    protected function normalizedPayload(array $data): array
    {
        return [
            'room_type' => (string) $data['room_type'],
            'description' => (string) ($data['description'] ?? ''),
        ];
    }
}
